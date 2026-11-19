<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Rombel;
use App\Models\User;
use Illuminate\Http\Request;

class RombelController extends Controller
{
    public function index(Request $request)
    {
        $query = Rombel::with(['waliKelas', 'academicYear'])->withCount('students');

        if ($request->filled('q')) {
            $q = $request->input('q');
            $query->where(function ($sq) use ($q) {
                $sq->where('nama_rombel', 'LIKE', "%$q%")
                   ->orWhere('ruang', 'LIKE', "%$q%")
                   ->orWhereHas('waliKelas', fn($wq) => $wq->where('nama_lengkap', 'LIKE', "%$q%"));
            });
        }

        if ($request->filled('tingkat')) {
            $query->where('tingkat', $request->input('tingkat'));
        }

        $rombels = $query->orderBy('tingkat')->orderBy('nama_rombel')->paginate(15)->withQueryString();

        $teachers = User::where('role_id', 2)->orderBy('nama_lengkap')->get();
        $academicYears = AcademicYear::orderBy('nama', 'desc')->get();
        $activeYear = AcademicYear::getActive();

        // Statistik Rombel
        $totalRombel = Rombel::count();
        $totalSiswaRombel = User::where('role_id', 3)
            ->whereNotNull('kelas')
            ->where('kelas', '!=', '')
            ->where('kelas', '!=', 'Alumni')
            ->where(function ($q) {
                $q->where('status_siswa', 'aktif')->orWhereNull('status_siswa');
            })
            ->count();

        $rombelTingkat7 = Rombel::where('tingkat', '7')->count();
        $rombelTingkat8 = Rombel::where('tingkat', '8')->count();
        $rombelTingkat9 = Rombel::where('tingkat', '9')->count();

        return view('admin.rombels.index', compact(
            'rombels',
            'teachers',
            'academicYears',
            'activeYear',
            'totalRombel',
            'totalSiswaRombel',
            'rombelTingkat7',
            'rombelTingkat8',
            'rombelTingkat9'
        ));
    }

    public function show(Rombel $rombel)
    {
        $rombel->load(['waliKelas', 'academicYear']);
        
        $students = $rombel->students()->orderBy('nama_lengkap')->get();
        $exams = $rombel->exams;

        return view('admin.rombels.show', compact('rombel', 'students', 'exams'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama_rombel' => 'required|string|max:50|unique:rombels,nama_rombel',
            'tingkat' => 'required|string|max:10',
            'wali_kelas_id' => 'nullable|exists:users,id',
            'ruang' => 'nullable|string|max:50',
            'keterangan' => 'nullable|string',
        ]);

        $activeYear = AcademicYear::getActive();

        $rombel = Rombel::create([
            'nama_rombel' => strtoupper(trim($request->input('nama_rombel'))),
            'tingkat' => $request->input('tingkat'),
            'wali_kelas_id' => $request->input('wali_kelas_id'),
            'academic_year_id' => $activeYear?->id,
            'ruang' => $request->input('ruang'),
            'keterangan' => $request->input('keterangan'),
        ]);

        return back()->with('success', "Rombel {$rombel->nama_rombel} berhasil ditambahkan.");
    }

    public function update(Request $request, Rombel $rombel)
    {
        $request->validate([
            'nama_rombel' => 'required|string|max:50|unique:rombels,nama_rombel,' . $rombel->id,
            'tingkat' => 'required|string|max:10',
            'wali_kelas_id' => 'nullable|exists:users,id',
            'ruang' => 'nullable|string|max:50',
            'keterangan' => 'nullable|string',
        ]);

        $oldName = $rombel->nama_rombel;
        $newName = strtoupper(trim($request->input('nama_rombel')));

        $rombel->update([
            'nama_rombel' => $newName,
            'tingkat' => $request->input('tingkat'),
            'wali_kelas_id' => $request->input('wali_kelas_id'),
            'ruang' => $request->input('ruang'),
            'keterangan' => $request->input('keterangan'),
        ]);

        // Jika nama rombel berubah, update juga kelas siswa terkait
        if ($oldName !== $newName) {
            User::where('role_id', 3)->where('kelas', $oldName)->update(['kelas' => $newName]);
        }

        return back()->with('success', "Data Rombel {$rombel->nama_rombel} berhasil diperbarui.");
    }

    public function destroy(Rombel $rombel)
    {
        $count = $rombel->students()->count();
        if ($count > 0) {
            return back()->with('error', "Rombel {$rombel->nama_rombel} tidak dapat dihapus karena masih memiliki {$count} siswa aktif. Pindahkan siswa terlebih dahulu.");
        }

        $nama = $rombel->nama_rombel;
        $rombel->delete();

        return back()->with('success', "Rombel {$nama} berhasil dihapus.");
    }

    public function syncFromStudents()
    {
        $activeAY = AcademicYear::getActive();
        $classes = User::where('role_id', 3)
            ->whereNotNull('kelas')
            ->where('kelas', '!=', '')
            ->where('kelas', '!=', 'Alumni')
            ->distinct()
            ->pluck('kelas');

        $added = 0;
        foreach ($classes as $cls) {
            $tingkat = preg_replace('/[^0-9]/', '', $cls) ?: substr($cls, 0, 1);
            $rombel = Rombel::firstOrCreate(
                ['nama_rombel' => $cls],
                [
                    'tingkat' => $tingkat ?: '7',
                    'academic_year_id' => $activeAY?->id,
                    'keterangan' => 'Rombel ' . $cls . ' SMPN 3 Ngawen',
                ]
            );
            if ($rombel->wasRecentlyCreated) {
                $added++;
            }
        }

        return back()->with('success', "Sinkronisasi selesai. {$added} Rombel baru berhasil dideteksi dan dicatat dari data siswa.");
    }
}

