<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StudentDocument;
use App\Models\StudentDocumentType;
use App\Models\StudentProfile;
use App\Models\User;
use App\Services\StudentDocumentOptimizer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class StudentAdministrationController extends Controller
{
    public function index(Request $request)
    {
        $query = User::where('role_id', 3)->with('studentProfile')->withCount('studentDocuments');

        if ($request->filled('q')) {
            $search = $request->input('q');
            $query->where(function ($studentQuery) use ($search) {
                $studentQuery->where('nama_lengkap', 'LIKE', "%{$search}%")
                    ->orWhere('username', 'LIKE', "%{$search}%")
                    ->orWhere('nisn', 'LIKE', "%{$search}%")
                    ->orWhere('nis', 'LIKE', "%{$search}%");
            });
        }

        if ($request->filled('kelas')) {
            $query->where('kelas', $request->input('kelas'));
        }

        $students = $query->orderBy('kelas')->orderBy('nama_lengkap')->paginate(25)->withQueryString();
        $classes = User::where('role_id', 3)
            ->whereNotNull('kelas')
            ->where('kelas', '!=', '')
            ->distinct()
            ->orderBy('kelas')
            ->pluck('kelas');

        return view('admin.student_administration.index', compact('students', 'classes'));
    }

    public function show(User $student)
    {
        $this->ensureStudent($student);
        $student->load(['studentProfile', 'studentDocuments.documentType']);
        $documentTypes = StudentDocumentType::where('is_active', true)->orderBy('name')->get();
        $allDocumentTypes = StudentDocumentType::orderBy('name')->get();
        $profile = $student->studentProfile ?: new StudentProfile;

        return view('admin.student_administration.show', compact('student', 'profile', 'documentTypes', 'allDocumentTypes'));
    }

    public function update(Request $request, User $student)
    {
        $this->ensureStudent($student);

        $data = $request->validate([
            'nama_lengkap' => 'required|string|max:100',
            'username' => 'required|string|max:50|unique:users,username,'.$student->id,
            'nisn' => 'nullable|string|max:30',
            'kelas' => 'nullable|string|max:50',
            'jenis_kelamin' => 'nullable|in:L,P',
            'telepon' => 'nullable|string|max:30',
            'alamat' => 'nullable|string|max:1000',
            'nik' => 'nullable|string|max:30',
            'nomor_kk' => 'nullable|string|max:30',
            'tempat_lahir' => 'nullable|string|max:100',
            'tanggal_lahir' => 'nullable|date|before_or_equal:today',
            'agama' => 'nullable|string|max:40',
            'anak_ke' => 'nullable|integer|min:1|max:50',
            'jumlah_saudara' => 'nullable|integer|min:0|max:50',
            'alamat_detail' => 'nullable|string|max:2000',
            'kode_pos' => 'nullable|string|max:10',
            'asal_sekolah' => 'nullable|string|max:150',
            'tanggal_masuk' => 'nullable|date',
            'nama_ayah' => 'nullable|string|max:100',
            'pekerjaan_ayah' => 'nullable|string|max:100',
            'telepon_ayah' => 'nullable|string|max:30',
            'nama_ibu' => 'nullable|string|max:100',
            'pekerjaan_ibu' => 'nullable|string|max:100',
            'telepon_ibu' => 'nullable|string|max:30',
            'nama_wali' => 'nullable|string|max:100',
            'pekerjaan_wali' => 'nullable|string|max:100',
            'telepon_wali' => 'nullable|string|max:30',
            'alamat_wali' => 'nullable|string|max:1000',
            'catatan' => 'nullable|string|max:3000',
        ]);

        DB::transaction(function () use ($student, $data) {
            $student->update(collect($data)->only([
                'nama_lengkap', 'username', 'nisn', 'kelas', 'jenis_kelamin', 'telepon', 'alamat',
            ])->all());

            StudentProfile::updateOrCreate(
                ['user_id' => $student->id],
                collect($data)->except([
                    'nama_lengkap', 'username', 'nisn', 'kelas', 'jenis_kelamin', 'telepon', 'alamat',
                ])->all()
            );
        });

        return back()->with('success', 'Data administrasi siswa berhasil disimpan.');
    }

    public function storeDocument(Request $request, User $student, StudentDocumentOptimizer $optimizer)
    {
        $this->ensureStudent($student);

        $validated = $request->validate([
            'document_type_id' => 'required|exists:student_document_types,id',
            'file' => 'required|file|mimes:pdf,jpg,jpeg,png,webp|max:2048',
        ]);
        $documentType = StudentDocumentType::where('is_active', true)->findOrFail($validated['document_type_id']);

        try {
            $optimized = $optimizer->store($validated['file'], $student, $documentType);
        } catch (RuntimeException $exception) {
            return back()->withErrors(['file' => $exception->getMessage()])->withInput();
        }

        StudentDocument::create([
            'user_id' => $student->id,
            'document_type_id' => $documentType->id,
            ...$optimized,
        ]);

        return back()->with('success', 'Dokumen berhasil disimpan secara privat.');
    }

    public function downloadDocument(User $student, StudentDocument $document)
    {
        $this->ensureStudent($student);
        abort_unless($document->user_id === $student->id, 404);
        abort_unless(Storage::disk('local')->exists($document->path), 404);

        return response()->download(
            Storage::disk('local')->path($document->path),
            $document->original_name,
            ['Content-Type' => $document->mime_type]
        );
    }

    public function destroyDocument(User $student, StudentDocument $document)
    {
        $this->ensureStudent($student);
        abort_unless($document->user_id === $student->id, 404);

        Storage::disk('local')->delete($document->path);
        $document->delete();

        return back()->with('success', 'Dokumen siswa berhasil dihapus.');
    }

    public function storeDocumentType(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'is_required' => 'nullable|boolean',
        ]);

        $slug = Str::slug($validated['name']);
        if (StudentDocumentType::where('slug', $slug)->exists()) {
            $slug .= '-'.Str::lower(Str::random(5));
        }

        StudentDocumentType::create([
            'name' => $validated['name'],
            'slug' => $slug,
            'is_required' => $request->boolean('is_required'),
            'is_active' => true,
        ]);

        return back()->with('success', 'Jenis dokumen berhasil ditambahkan.');
    }

    private function ensureStudent(User $student): void
    {
        abort_unless($student->role_id === 3, 404);
    }
}
