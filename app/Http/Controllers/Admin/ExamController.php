<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\BankSoal;
use App\Models\Exam;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ExamController extends Controller
{
    public function index(Request $request)
    {
        $query = Exam::with(['subject', 'bankSoal.guru', 'academicYear'])->withCount('sessions');

        if ($request->filled('q')) {
            $q = $request->input('q');
            $query->where('nama_ujian', 'LIKE', "%$q%")
                  ->orWhere('target_kelas', 'LIKE', "%$q%")
                  ->orWhere('token_ujian', 'LIKE', "%$q%");
        }

        if ($request->filled('subject_id')) {
            $query->where('subject_id', $request->input('subject_id'));
        }

        if ($request->filled('kelas')) {
            $query->where('target_kelas', $request->input('kelas'));
        }

        if ($request->filled('academic_year_id')) {
            $query->where('academic_year_id', $request->input('academic_year_id'));
        }

        $exams = $query->orderBy('waktu_mulai', 'desc')->paginate(15)->withQueryString();
        $subjects = Subject::orderBy('nama_mapel')->get();
        $academicYears = AcademicYear::orderBy('nama', 'desc')->get();
        $activeYear = AcademicYear::getActive();

        $bankSoals = BankSoal::with(['subject', 'guru'])
            ->withCount('questions')
            ->orderBy('id', 'desc')
            ->get();

        $classesList = User::where('role_id', 3)
            ->whereNotNull('kelas')
            ->where('kelas', '!=', '')
            ->distinct()
            ->orderBy('kelas')
            ->pluck('kelas')
            ->toArray();

        if (empty($classesList)) {
            $classesList = ['7A', '7B', '8A', '8B', '9A', '9B'];
        }

        return view('admin.exams.index', compact('exams', 'subjects', 'classesList', 'bankSoals', 'academicYears', 'activeYear'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama_ujian' => 'required|string|max:150',
            'subject_id' => 'required|exists:subjects,id',
            'bank_soal_id' => 'nullable|exists:bank_soal,id',
            'target_kelas' => 'required|string|max:50',
            'waktu_mulai' => 'required|date',
            'durasi' => 'required|integer|min:5',
            'waktu_selesai' => 'nullable|date|after:waktu_mulai',
        ]);

        $subjectId = (int)$request->input('subject_id');
        $targetKelas = trim($request->input('target_kelas'));
        $bankSoalId = $request->input('bank_soal_id');

        // Validasi Aturan: Mapel hanya bisa diaktifkan pada kelas tempat soal tersebut dirilis
        if ($bankSoalId) {
            $bank = BankSoal::find($bankSoalId);
            if ($bank) {
                $subjectId = $bank->subject_id;
                if (!$bank->matchesClass($targetKelas)) {
                    $rilisInfo = $bank->kelas_rilis ?: 'Belum ada kelas yang dirilis';
                    return back()->withInput()->with('error', "Gagal mengaktifkan ujian! Paket '{$bank->nama_bank}' hanya dirilis untuk: {$rilisInfo}. Mapel hanya bisa diaktifkan pada kelas tempat soal tersebut dirilis.");
                }
            }
        } else {
            // Cari paket soal yang dirilis untuk mapel dan kelas ini
            $releasedBanks = BankSoal::where('subject_id', $subjectId)
                ->where('status_rilis', 'rilis')
                ->get();

            if ($releasedBanks->isNotEmpty()) {
                $matchedBank = $releasedBanks->first(fn($b) => $b->matchesClass($targetKelas));
                if (!$matchedBank) {
                    $available = $releasedBanks->map(fn($b) => "{$b->nama_bank} (Kelas {$b->kelas_rilis})")->implode(', ');
                    return back()->withInput()->with('error', "Gagal mengaktifkan ujian! Tidak ada paket soal untuk kelas {$targetKelas} yang dirilis pada mapel ini. Paket soal yang aktif saat ini: {$available}. Mapel hanya bisa diaktifkan pada kelas tempat soal dirilis.");
                }
                $bankSoalId = $matchedBank->id;
            } else {
                return back()->withInput()->with('error', "Gagal mengaktifkan ujian! Belum ada paket soal yang dirilis oleh guru pengampu untuk mata pelajaran ini. Silakan minta guru pengampu merilis paket soal terlebih dahulu.");
            }
        }

        $token = strtoupper(Str::random(6));

        $waktuMulai = $request->input('waktu_mulai');
        $durasi = (int)$request->input('durasi');
        $waktuSelesai = $request->input('waktu_selesai');

        if (empty($waktuSelesai)) {
            $waktuSelesai = date('Y-m-d H:i:s', strtotime($waktuMulai) + ($durasi * 60));
        }

        Exam::create([
            'nama_ujian' => $request->input('nama_ujian'),
            'subject_id' => $subjectId,
            'bank_soal_id' => $bankSoalId,
            'target_kelas' => $targetKelas,
            'waktu_mulai' => $waktuMulai,
            'waktu_selesai' => $waktuSelesai,
            'durasi' => $durasi,
            'token_ujian' => $token,
        ]);

        return redirect()->route('admin.exams.index')->with('success', "Jadwal ujian berhasil ditambahkan dengan Token: $token");
    }

    public function update(Request $request, Exam $exam)
    {
        $request->validate([
            'nama_ujian' => 'required|string|max:150',
            'subject_id' => 'required|exists:subjects,id',
            'bank_soal_id' => 'nullable|exists:bank_soal,id',
            'target_kelas' => 'required|string|max:50',
            'waktu_mulai' => 'required|date',
            'durasi' => 'required|integer|min:5',
            'waktu_selesai' => 'nullable|date',
            'token_ujian' => 'nullable|string|max:10',
        ]);

        $subjectId = (int)$request->input('subject_id');
        $targetKelas = trim($request->input('target_kelas'));
        $bankSoalId = $request->input('bank_soal_id');

        // Validasi Aturan: Mapel hanya bisa diaktifkan pada kelas tempat soal tersebut dirilis
        if ($bankSoalId) {
            $bank = BankSoal::find($bankSoalId);
            if ($bank) {
                $subjectId = $bank->subject_id;
                if (!$bank->matchesClass($targetKelas)) {
                    $rilisInfo = $bank->kelas_rilis ?: 'Belum ada kelas yang dirilis';
                    return back()->withInput()->with('error', "Gagal memperbarui ujian! Paket '{$bank->nama_bank}' hanya dirilis untuk: {$rilisInfo}. Mapel hanya bisa diaktifkan pada kelas tempat soal tersebut dirilis.");
                }
            }
        } else {
            $releasedBanks = BankSoal::where('subject_id', $subjectId)
                ->where('status_rilis', 'rilis')
                ->get();

            if ($releasedBanks->isNotEmpty()) {
                $matchedBank = $releasedBanks->first(fn($b) => $b->matchesClass($targetKelas));
                if (!$matchedBank) {
                    $available = $releasedBanks->map(fn($b) => "{$b->nama_bank} (Kelas {$b->kelas_rilis})")->implode(', ');
                    return back()->withInput()->with('error', "Gagal memperbarui ujian! Tidak ada paket soal untuk kelas {$targetKelas} yang dirilis pada mapel ini. Paket soal yang aktif: {$available}.");
                }
                $bankSoalId = $matchedBank->id;
            }
        }

        $data = [
            'nama_ujian' => $request->input('nama_ujian'),
            'subject_id' => $subjectId,
            'bank_soal_id' => $bankSoalId,
            'target_kelas' => $targetKelas,
            'waktu_mulai' => $request->input('waktu_mulai'),
            'durasi' => (int)$request->input('durasi'),
        ];

        if ($request->filled('waktu_selesai')) {
            $data['waktu_selesai'] = $request->input('waktu_selesai');
        } else {
            $data['waktu_selesai'] = date('Y-m-d H:i:s', strtotime($request->input('waktu_mulai')) + ((int)$request->input('durasi') * 60));
        }

        if ($request->filled('token_ujian')) {
            $data['token_ujian'] = strtoupper($request->input('token_ujian'));
        }

        $exam->update($data);

        return redirect()->route('admin.exams.index')->with('success', 'Jadwal ujian berhasil diperbarui.');
    }

    public function regenerateToken(Exam $exam)
    {
        $newToken = strtoupper(Str::random(6));
        $exam->update(['token_ujian' => $newToken]);

        return redirect()->route('admin.exams.index')->with('success', "Token ujian untuk {$exam->nama_ujian} ({$exam->target_kelas}) telah direset menjadi: $newToken");
    }

    public function destroy(Exam $exam)
    {
        $exam->delete();
        return redirect()->route('admin.exams.index')->with('success', 'Ujian beserta riwayat sesi berhasil dihapus.');
    }
}

