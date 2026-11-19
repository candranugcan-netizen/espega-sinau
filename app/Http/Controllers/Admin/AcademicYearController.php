<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\BankSoal;
use App\Models\Exam;
use App\Models\StudentClassHistory;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AcademicYearController extends Controller
{
    /**
     * Tampilkan Halaman Manajemen Tahun Ajaran & Ringkasan SIAKAD
     */
    public function index(Request $request)
    {
        $academicYears = AcademicYear::withCount(['exams', 'bankSoals', 'classHistories'])
            ->orderBy('nama', 'desc')
            ->orderBy('semester', 'desc')
            ->get();

        $activeYear = AcademicYear::getActive();

        // Statistik Sekolah Keseluruhan
        $totalSiswaAktif = User::where('role_id', 3)
            ->where(function ($q) {
                $q->where('status_siswa', 'aktif')
                  ->orWhereNull('status_siswa');
            })
            ->where('kelas', '!=', 'Alumni')
            ->count();

        $totalAlumni = User::where('role_id', 3)
            ->where(function ($q) {
                $q->where('status_siswa', 'alumni')
                  ->orWhere('kelas', 'Alumni')
                  ->orWhereNotNull('tahun_lulus_id');
            })
            ->count();

        $totalUjianTahunIni = $activeYear ? Exam::where('academic_year_id', $activeYear->id)->count() : Exam::count();
        $totalBankSoalTahunIni = $activeYear ? BankSoal::where('academic_year_id', $activeYear->id)->count() : BankSoal::count();

        // Daftar Rombel Kelas Aktif
        $classesList = User::where('role_id', 3)
            ->whereNotNull('kelas')
            ->where('kelas', '!=', '')
            ->where('kelas', '!=', 'Alumni')
            ->where(function ($q) {
                $q->where('status_siswa', 'aktif')->orWhereNull('status_siswa');
            })
            ->distinct()
            ->orderBy('kelas')
            ->pluck('kelas')
            ->toArray();

        // Hitung persebaran siswa per tingkat (7, 8, 9)
        $siswaKelas7 = User::where('role_id', 3)->where('kelas', 'LIKE', '7%')->count();
        $siswaKelas8 = User::where('role_id', 3)->where('kelas', 'LIKE', '8%')->count();
        $siswaKelas9 = User::where('role_id', 3)->where('kelas', 'LIKE', '9%')->count();

        return view('admin.academic_years.index', compact(
            'academicYears',
            'activeYear',
            'totalSiswaAktif',
            'totalAlumni',
            'totalUjianTahunIni',
            'totalBankSoalTahunIni',
            'classesList',
            'siswaKelas7',
            'siswaKelas8',
            'siswaKelas9'
        ));
    }

    /**
     * Simpan Tahun Ajaran Baru
     */
    public function store(Request $request)
    {
        $request->validate([
            'nama' => 'required|string|max:50',
            'semester' => 'required|in:ganjil,genap',
            'tanggal_mulai' => 'nullable|date',
            'tanggal_selesai' => 'nullable|date|after_or_equal:tanggal_mulai',
            'keterangan' => 'nullable|string',
        ]);

        $isActive = $request->boolean('is_active');

        if ($isActive) {
            AcademicYear::where('is_active', true)->update(['is_active' => false]);
        }

        $academicYear = AcademicYear::create([
            'nama' => trim($request->input('nama')),
            'semester' => $request->input('semester'),
            'is_active' => $isActive,
            'tanggal_mulai' => $request->input('tanggal_mulai'),
            'tanggal_selesai' => $request->input('tanggal_selesai'),
            'keterangan' => $request->input('keterangan'),
        ]);

        return back()->with('success', "Tahun Ajaran {$academicYear->full_label} berhasil ditambahkan.");
    }

    /**
     * Update Data Tahun Ajaran
     */
    public function update(Request $request, AcademicYear $academicYear)
    {
        $request->validate([
            'nama' => 'required|string|max:50',
            'semester' => 'required|in:ganjil,genap',
            'tanggal_mulai' => 'nullable|date',
            'tanggal_selesai' => 'nullable|date|after_or_equal:tanggal_mulai',
            'keterangan' => 'nullable|string',
        ]);

        $isActive = $request->boolean('is_active');

        if ($isActive && !$academicYear->is_active) {
            AcademicYear::where('id', '!=', $academicYear->id)->update(['is_active' => false]);
        }

        $academicYear->update([
            'nama' => trim($request->input('nama')),
            'semester' => $request->input('semester'),
            'is_active' => $isActive,
            'tanggal_mulai' => $request->input('tanggal_mulai'),
            'tanggal_selesai' => $request->input('tanggal_selesai'),
            'keterangan' => $request->input('keterangan'),
        ]);

        return back()->with('success', "Tahun Ajaran {$academicYear->full_label} berhasil diperbarui.");
    }

    /**
     * Tetapkan Tahun Ajaran Berjalan / Aktif
     */
    public function setActive(AcademicYear $academicYear)
    {
        $academicYear->activate();

        return back()->with('success', "Tahun Ajaran aktif sekarang adalah: {$academicYear->full_label}. Seluruh aktivitas akademik baru akan terpusat di periode ini.");
    }

    /**
     * Hapus Tahun Ajaran
     */
    public function destroy(AcademicYear $academicYear)
    {
        if ($academicYear->is_active) {
            return back()->with('error', 'Tahun ajaran yang sedang aktif tidak dapat dihapus.');
        }

        $label = $academicYear->full_label;
        $academicYear->delete();

        return back()->with('success', "Tahun Ajaran {$label} berhasil dihapus.");
    }

    /**
     * Wizard Kenaikan Kelas Massal & Kelulusan Alumni
     */
    public function kenaikanKelas(Request $request)
    {
        $mode = $request->input('mode', 'otomatis'); // 'otomatis', 'per_kelas', 'pilih_siswa'
        $activeYear = AcademicYear::getActive();

        if (!$activeYear) {
            return back()->with('error', 'Silakan aktifkan salah satu Tahun Ajaran terlebih dahulu.');
        }

        DB::beginTransaction();
        try {
            $countLulus = 0;
            $countNaik = 0;

            if ($mode === 'otomatis') {
                // 1. Siswa Kelas 9 Lulus menjadi Alumni
                $siswaKelas9 = User::where('role_id', 3)
                    ->where('kelas', 'LIKE', '9%')
                    ->where(function ($q) {
                        $q->where('status_siswa', 'aktif')->orWhereNull('status_siswa');
                    })
                    ->get();

                foreach ($siswaKelas9 as $s9) {
                    // Catat ke riwayat kelas
                    StudentClassHistory::create([
                        'user_id' => $s9->id,
                        'academic_year_id' => $activeYear->id,
                        'kelas' => $s9->kelas,
                        'status' => 'lulus',
                        'catatan' => 'Lulus dari SMPN 3 Ngawen (Menjadi Alumni)',
                    ]);

                    $s9->update([
                        'status_siswa' => 'alumni',
                        'kelas' => 'Alumni',
                        'tahun_lulus_id' => $activeYear->id,
                    ]);
                    $countLulus++;
                }

                // 2. Siswa Kelas 8 Naik ke Kelas 9 (contoh: 8A -> 9A, 8B -> 9B)
                $siswaKelas8 = User::where('role_id', 3)
                    ->where('kelas', 'LIKE', '8%')
                    ->where(function ($q) {
                        $q->where('status_siswa', 'aktif')->orWhereNull('status_siswa');
                    })
                    ->get();

                foreach ($siswaKelas8 as $s8) {
                    $kelasLama = $s8->kelas;
                    $kelasBaru = preg_replace('/^8/', '9', $kelasLama);

                    StudentClassHistory::create([
                        'user_id' => $s8->id,
                        'academic_year_id' => $activeYear->id,
                        'kelas' => $kelasLama,
                        'status' => 'naik_kelas',
                        'catatan' => "Naik dari Kelas {$kelasLama} ke Kelas {$kelasBaru}",
                    ]);

                    $s8->update([
                        'kelas' => $kelasBaru,
                        'academic_year_id' => $activeYear->id,
                    ]);
                    $countNaik++;
                }

                // 3. Siswa Kelas 7 Naik ke Kelas 8 (contoh: 7A -> 8A, 7B -> 8B)
                $siswaKelas7 = User::where('role_id', 3)
                    ->where('kelas', 'LIKE', '7%')
                    ->where(function ($q) {
                        $q->where('status_siswa', 'aktif')->orWhereNull('status_siswa');
                    })
                    ->get();

                foreach ($siswaKelas7 as $s7) {
                    $kelasLama = $s7->kelas;
                    $kelasBaru = preg_replace('/^7/', '8', $kelasLama);

                    StudentClassHistory::create([
                        'user_id' => $s7->id,
                        'academic_year_id' => $activeYear->id,
                        'kelas' => $kelasLama,
                        'status' => 'naik_kelas',
                        'catatan' => "Naik dari Kelas {$kelasLama} ke Kelas {$kelasBaru}",
                    ]);

                    $s7->update([
                        'kelas' => $kelasBaru,
                        'academic_year_id' => $activeYear->id,
                    ]);
                    $countNaik++;
                }
            } elseif ($mode === 'per_kelas') {
                $kelasAsal = trim($request->input('kelas_asal'));
                $aksi = $request->input('aksi'); // 'naik', 'lulus'
                $kelasTujuan = trim($request->input('kelas_tujuan'));

                if (empty($kelasAsal)) {
                    return back()->with('error', 'Silakan pilih kelas asal.');
                }

                $students = User::where('role_id', 3)
                    ->where('kelas', $kelasAsal)
                    ->where(function ($q) {
                        $q->where('status_siswa', 'aktif')->orWhereNull('status_siswa');
                    })
                    ->get();

                foreach ($students as $stu) {
                    if ($aksi === 'lulus') {
                        StudentClassHistory::create([
                            'user_id' => $stu->id,
                            'academic_year_id' => $activeYear->id,
                            'kelas' => $kelasAsal,
                            'status' => 'lulus',
                            'catatan' => "Kelulusan Kelas {$kelasAsal} (Alumni)",
                        ]);
                        $stu->update([
                            'status_siswa' => 'alumni',
                            'kelas' => 'Alumni',
                            'tahun_lulus_id' => $activeYear->id,
                        ]);
                        $countLulus++;
                    } else {
                        if (empty($kelasTujuan)) {
                            return back()->with('error', 'Silakan isi kelas tujuan.');
                        }
                        StudentClassHistory::create([
                            'user_id' => $stu->id,
                            'academic_year_id' => $activeYear->id,
                            'kelas' => $kelasAsal,
                            'status' => 'naik_kelas',
                            'catatan' => "Pindah/Naik dari {$kelasAsal} ke {$kelasTujuan}",
                        ]);
                        $stu->update([
                            'kelas' => $kelasTujuan,
                            'academic_year_id' => $activeYear->id,
                        ]);
                        $countNaik++;
                    }
                }
            }

            DB::commit();

            $msg = "Proses Berhasil! ";
            if ($countNaik > 0) $msg .= "{$countNaik} siswa berhasil naik kelas. ";
            if ($countLulus > 0) $msg .= "{$countLulus} siswa kelas 9 berhasil diluluskan dan tercatat di Buku Induk Alumni. ";

            return back()->with('success', $msg);
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->with('error', 'Terjadi kesalahan saat memproses kenaikan kelas: ' . $e->getMessage());
        }
    }

    /**
     * Buku Induk Alumni / Daftar Lulusan
     */
    public function alumni(Request $request)
    {
        $query = User::where('role_id', 3)
            ->where(function ($q) {
                $q->where('status_siswa', 'alumni')
                  ->orWhere('kelas', 'Alumni')
                  ->orWhereNotNull('tahun_lulus_id');
            })
            ->with(['tahunLulus', 'classHistories.academicYear']);

        if ($request->filled('q')) {
            $q = $request->input('q');
            $query->where(function ($sq) use ($q) {
                $sq->where('nama_lengkap', 'LIKE', "%$q%")
                   ->orWhere('username', 'LIKE', "%$q%")
                   ->orWhere('nisn', 'LIKE', "%$q%")
                   ->orWhere('nis', 'LIKE', "%$q%");
            });
        }

        if ($request->filled('tahun_lulus_id')) {
            $query->where('tahun_lulus_id', $request->input('tahun_lulus_id'));
        }

        $alumni = $query->orderBy('nama_lengkap', 'asc')->paginate(25)->withQueryString();

        $academicYears = AcademicYear::orderBy('nama', 'desc')->get();

        return view('admin.academic_years.alumni', compact('alumni', 'academicYears'));
    }
}

