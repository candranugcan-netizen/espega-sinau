<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Role;
use App\Models\StudentClassHistory;
use App\Models\User;
use App\Services\ExcelService;
use App\Services\PdfService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class UserController extends Controller
{
    public function siswa(Request $request)
    {
        $statusSiswa = $request->input('status_siswa', 'aktif');
        $query = User::where('role_id', 3)->with(['academicYear', 'tahunLulus', 'classHistories']);

        if ($statusSiswa === 'aktif') {
            $query->where(function ($q) {
                $q->where('status_siswa', 'aktif')
                  ->orWhereNull('status_siswa');
            })->where('kelas', '!=', 'Alumni');
        } elseif ($statusSiswa === 'alumni') {
            $query->where(function ($q) {
                $q->where('status_siswa', 'alumni')
                  ->orWhere('kelas', 'Alumni')
                  ->orWhereNotNull('tahun_lulus_id');
            });
        }

        if ($request->filled('q')) {
            $q = $request->input('q');
            $query->where(function ($sq) use ($q) {
                $sq->where('nama_lengkap', 'LIKE', "%$q%")
                   ->orWhere('username', 'LIKE', "%$q%")
                   ->orWhere('nisn', 'LIKE', "%$q%")
                   ->orWhere('nis', 'LIKE', "%$q%");
            });
        }

        if ($request->filled('kelas')) {
            $query->where('kelas', $request->input('kelas'));
        }

        if ($request->filled('status_blokir')) {
            $query->where('status_blokir', $request->input('status_blokir'));
        }

        if ($request->filled('academic_year_id')) {
            $query->where('academic_year_id', $request->input('academic_year_id'));
        }

        $users = $query->orderBy('kelas')->orderBy('nama_lengkap')->paginate(25)->withQueryString();

        $classesList = User::where('role_id', 3)
            ->whereNotNull('kelas')
            ->where('kelas', '!=', '')
            ->where('kelas', '!=', 'Alumni')
            ->distinct()
            ->orderBy('kelas')
            ->pluck('kelas')
            ->toArray();

        $academicYears = AcademicYear::orderBy('nama', 'desc')->get();
        $activeYear = AcademicYear::getActive();

        $totalAktif = User::where('role_id', 3)
            ->where(function ($q) {
                $q->where('status_siswa', 'aktif')->orWhereNull('status_siswa');
            })
            ->where('kelas', '!=', 'Alumni')
            ->count();

        $totalAlumni = User::where('role_id', 3)
            ->where(function ($q) {
                $q->where('status_siswa', 'alumni')->orWhere('kelas', 'Alumni')->orWhereNotNull('tahun_lulus_id');
            })
            ->count();

        return view('admin.users.siswa', compact('users', 'classesList', 'academicYears', 'activeYear', 'statusSiswa', 'totalAktif', 'totalAlumni'));
    }

    public function guru(Request $request)
    {
        $query = User::where('role_id', 2)->with(['subjects', 'rombelWali'])->withCount(['subjects', 'bankSoals']);

        if ($request->filled('q')) {
            $q = $request->input('q');
            $query->where(function ($sq) use ($q) {
                $sq->where('nama_lengkap', 'LIKE', "%$q%")
                   ->orWhere('username', 'LIKE', "%$q%")
                   ->orWhere('nip', 'LIKE', "%$q%")
                   ->orWhere('email', 'LIKE', "%$q%");
            });
        }

        $teachers = $query->orderBy('nama_lengkap')->paginate(20)->withQueryString();

        $allSubjects = \App\Models\Subject::orderBy('nama_mapel')->get();

        $totalGuru = User::where('role_id', 2)->count();
        $totalPengampu = User::where('role_id', 2)->has('subjects')->count();
        $totalWaliKelas = \App\Models\Rombel::whereNotNull('wali_kelas_id')->distinct('wali_kelas_id')->count('wali_kelas_id');
        $totalAktif = User::where('role_id', 2)->where('status_blokir', '0')->count();

        return view('admin.users.guru', compact(
            'teachers',
            'allSubjects',
            'totalGuru',
            'totalPengampu',
            'totalWaliKelas',
            'totalAktif'
        ));
    }

    public function storeGuru(Request $request)
    {
        $request->validate([
            'nama_lengkap' => 'required|string|max:100',
            'username' => 'required|string|max:50|unique:users,username',
            'nip' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:100|unique:users,email',
            'password' => 'nullable|string|min:4',
            'telepon' => 'nullable|string|max:30',
            'alamat' => 'nullable|string',
            'subject_ids' => 'nullable|array',
            'subject_ids.*' => 'exists:subjects,id',
        ]);

        $passwordInput = $request->filled('password') ? $request->input('password') : trim($request->input('username'));
        $token = strtoupper(Str::random(6));

        $guru = User::create([
            'nama_lengkap' => trim($request->input('nama_lengkap')),
            'username' => trim($request->input('username')),
            'nip' => $request->input('nip') ? trim($request->input('nip')) : null,
            'email' => $request->input('email') ? trim($request->input('email')) : null,
            'password' => $passwordInput, // Otomatis di-hash oleh mutator User::setPasswordAttribute
            'token' => $token,
            'role_id' => 2,
            'status_blokir' => '0',
            'telepon' => $request->input('telepon'),
            'alamat' => $request->input('alamat'),
        ]);

        // Hubungkan ke mata pelajaran yang dipilih jika ada
        if ($request->filled('subject_ids')) {
            \App\Models\Subject::whereIn('id', $request->input('subject_ids'))->update(['guru_id' => $guru->id]);
        }

        return back()->with('success', "Akun guru {$guru->nama_lengkap} berhasil ditambahkan. Password tersimpan aman (ter-hash).");
    }

    public function updateGuru(Request $request, User $user)
    {
        if ($user->role_id !== 2) {
            abort(403, 'Pengguna bukan berstatus guru.');
        }

        $request->validate([
            'nama_lengkap' => 'required|string|max:100',
            'username' => 'required|string|max:50|unique:users,username,' . $user->id,
            'nip' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:100|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:4',
            'telepon' => 'nullable|string|max:30',
            'alamat' => 'nullable|string',
            'status_blokir' => 'required|in:0,1',
            'subject_ids' => 'nullable|array',
            'subject_ids.*' => 'exists:subjects,id',
        ]);

        $updateData = [
            'nama_lengkap' => trim($request->input('nama_lengkap')),
            'username' => trim($request->input('username')),
            'nip' => $request->input('nip') ? trim($request->input('nip')) : null,
            'email' => $request->input('email') ? trim($request->input('email')) : null,
            'telepon' => $request->input('telepon'),
            'alamat' => $request->input('alamat'),
            'status_blokir' => $request->input('status_blokir'),
        ];

        // Jika password diisi, update password (otomatis di-hash oleh mutator User)
        if ($request->filled('password')) {
            $updateData['password'] = $request->input('password');
        }

        $user->update($updateData);

        // Update relasi mata pelajaran
        if ($request->has('subject_ids')) {
            $newSubjectIds = $request->input('subject_ids', []);
            // Lepas mapel yang sebelumnya diampu oleh guru ini tapi tidak dipilih lagi
            \App\Models\Subject::where('guru_id', $user->id)
                ->whereNotIn('id', $newSubjectIds)
                ->update(['guru_id' => null]);

            // Tetapkan mapel yang dipilih ke guru ini
            if (!empty($newSubjectIds)) {
                \App\Models\Subject::whereIn('id', $newSubjectIds)->update(['guru_id' => $user->id]);
            }
        }

        return back()->with('success', "Data guru {$user->nama_lengkap} berhasil diperbarui.");
    }

    public function destroyGuru(User $user)
    {
        if ($user->role_id !== 2) {
            abort(403, 'Pengguna bukan berstatus guru.');
        }

        $nama = $user->nama_lengkap;

        // Lepaskan relasi mapel dan wali kelas secara aman sebelum dihapus
        \App\Models\Subject::where('guru_id', $user->id)->update(['guru_id' => null]);
        \App\Models\Rombel::where('wali_kelas_id', $user->id)->update(['wali_kelas_id' => null]);

        $user->delete();

        return back()->with('success', "Data guru {$nama} berhasil dihapus dari sistem.");
    }

    public function storeSiswa(Request $request)
    {
        $request->validate([
            'username' => 'required|string|max:50|unique:users,username',
            'nama_lengkap' => 'required|string|max:100',
            'kelas' => 'required|string|max:20',
            'token' => 'nullable|string|max:20',
            'nisn' => 'nullable|string|max:30',
            'nis' => 'nullable|string|max:30',
            'jenis_kelamin' => 'nullable|in:L,P',
            'telepon' => 'nullable|string|max:30',
            'alamat' => 'nullable|string',
        ]);

        $token = $request->filled('token') ? strtoupper(trim($request->input('token'))) : strtoupper(Str::random(6));
        $activeYear = AcademicYear::getActive();

        $siswa = User::create([
            'username' => trim($request->input('username')),
            'nama_lengkap' => trim($request->input('nama_lengkap')),
            'kelas' => trim($request->input('kelas')),
            'token' => $token,
            'password' => $token, // Otomatis di-hash oleh mutator User::setPasswordAttribute
            'role_id' => 3,
            'status_blokir' => '0',
            'status_siswa' => 'aktif',
            'nisn' => $request->input('nisn'),
            'nis' => $request->input('nis'),
            'jenis_kelamin' => $request->input('jenis_kelamin'),
            'telepon' => $request->input('telepon'),
            'alamat' => $request->input('alamat'),
            'academic_year_id' => $activeYear?->id,
            'tahun_masuk_id' => $activeYear?->id,
        ]);

        if ($activeYear) {
            StudentClassHistory::create([
                'user_id' => $siswa->id,
                'academic_year_id' => $activeYear->id,
                'kelas' => $siswa->kelas,
                'status' => 'aktif',
                'catatan' => 'Pendaftaran Siswa Baru',
            ]);
        }

        return back()->with('success', "Akun siswa {$request->input('nama_lengkap')} berhasil ditambahkan dengan Token: $token");
    }

    public function updateSiswa(Request $request, User $user)
    {
        $request->validate([
            'username' => 'required|string|max:50|unique:users,username,' . $user->id,
            'nama_lengkap' => 'required|string|max:100',
            'kelas' => 'required|string|max:20',
            'nisn' => 'nullable|string|max:30',
            'nis' => 'nullable|string|max:30',
            'jenis_kelamin' => 'nullable|in:L,P',
            'status_siswa' => 'nullable|string',
            'telepon' => 'nullable|string|max:30',
            'alamat' => 'nullable|string',
        ]);

        $oldKelas = $user->kelas;
        $newKelas = trim($request->input('kelas'));

        $user->update([
            'username' => trim($request->input('username')),
            'nama_lengkap' => trim($request->input('nama_lengkap')),
            'kelas' => $newKelas,
            'nisn' => $request->input('nisn'),
            'nis' => $request->input('nis'),
            'jenis_kelamin' => $request->input('jenis_kelamin'),
            'status_siswa' => $request->input('status_siswa', $user->status_siswa ?: 'aktif'),
            'telepon' => $request->input('telepon'),
            'alamat' => $request->input('alamat'),
        ]);

        // Jika kelas berubah, rekam perubahan kelas
        if ($oldKelas !== $newKelas) {
            $activeYear = AcademicYear::getActive();
            if ($activeYear) {
                StudentClassHistory::create([
                    'user_id' => $user->id,
                    'academic_year_id' => $activeYear->id,
                    'kelas' => $newKelas,
                    'status' => 'pindah_kelas',
                    'catatan' => "Perubahan kelas dari {$oldKelas} ke {$newKelas}",
                ]);
            }
        }

        return back()->with('success', "Data siswa {$user->nama_lengkap} berhasil diperbarui.");
    }

    public function importSiswa(Request $request)
    {
        $request->validate([
            'file_excel' => 'required|file|mimes:xlsx,xls,csv,txt',
        ]);

        try {
            $count = ExcelService::importSiswa($request->file('file_excel'));
            return back()->with('success', "Berhasil mengimpor $count data siswa ke sistem.");
        } catch (\Exception $e) {
            return back()->with('error', "Gagal mengimpor file: " . $e->getMessage());
        }
    }

    public function exportSiswa(Request $request)
    {
        return ExcelService::exportSiswa($request->input('kelas'));
    }

    public function templateSiswa()
    {
        return ExcelService::templateSiswa();
    }

    public function cetakKartu(Request $request)
    {
        return PdfService::cetakKartuPeserta($request->input('kelas'), $request->input('q'));
    }

    public function bulkAction(Request $request)
    {
        $action = $request->input('action_type');
        $ids = $request->input('selected_ids', []);

        if (empty($ids) || !is_array($ids)) {
            return back()->with('error', 'Pilih minimal satu siswa untuk melakukan aksi massal.');
        }

        switch ($action) {
            case 'reset_token':
                $users = User::whereIn('id', $ids)->where('role_id', 3)->get();
                foreach ($users as $u) {
                    $tok = strtoupper(Str::random(6));
                    $u->update([
                        'token' => $tok,
                        'password' => $tok, // Otomatis di-hash oleh mutator User::setPasswordAttribute
                    ]);
                }
                return back()->with('success', "Token berhasil direset secara massal untuk " . count($users) . " siswa (password otomatis ter-hash).");

            case 'blokir':
                $count = User::whereIn('id', $ids)->where('role_id', 3)->update(['status_blokir' => '1']);
                return back()->with('success', "$count siswa berhasil diblokir akses ujiannya.");

            case 'buka_blokir':
                $count = User::whereIn('id', $ids)->where('role_id', 3)->update(['status_blokir' => '0']);
                return back()->with('success', "$count siswa berhasil dibuka blokirnya.");

            case 'luluskan':
                $activeYear = AcademicYear::getActive();
                $users = User::whereIn('id', $ids)->where('role_id', 3)->get();
                $lulusCount = 0;
                foreach ($users as $u) {
                    if ($activeYear) {
                        StudentClassHistory::create([
                            'user_id' => $u->id,
                            'academic_year_id' => $activeYear->id,
                            'kelas' => $u->kelas,
                            'status' => 'lulus',
                            'catatan' => 'Kelulusan Massal Siswa (Menjadi Alumni)',
                        ]);
                    }
                    $u->update([
                        'status_siswa' => 'alumni',
                        'kelas' => 'Alumni',
                        'tahun_lulus_id' => $activeYear?->id,
                    ]);
                    $lulusCount++;
                }
                return back()->with('success', "$lulusCount siswa berhasil diluluskan dan dicatat ke Buku Induk Alumni.");

            case 'pindah_kelas':
                $targetClass = trim($request->input('target_kelas', ''));
                if (empty($targetClass)) {
                    return back()->with('error', 'Silakan pilih kelas tujuan untuk pemindahan kelas.');
                }
                $activeYear = AcademicYear::getActive();
                $users = User::whereIn('id', $ids)->where('role_id', 3)->get();
                foreach ($users as $u) {
                    $old = $u->kelas;
                    if ($activeYear) {
                        StudentClassHistory::create([
                            'user_id' => $u->id,
                            'academic_year_id' => $activeYear->id,
                            'kelas' => $old,
                            'status' => 'naik_kelas',
                            'catatan' => "Pemindahan kelas massal ke {$targetClass}",
                        ]);
                    }
                    $u->update([
                        'kelas' => $targetClass,
                        'academic_year_id' => $activeYear?->id,
                    ]);
                }
                return back()->with('success', count($users) . " siswa berhasil dipindahkan ke kelas {$targetClass}.");

            case 'delete':
                $count = User::whereIn('id', $ids)->where('role_id', 3)->delete();
                return back()->with('success', "$count data siswa berhasil dihapus secara massal.");

            default:
                return back()->with('error', 'Aksi massal tidak dikenali.');
        }
    }

    public function resetToken(User $user)
    {
        $newToken = strtoupper(Str::random(6));
        $user->update([
            'token' => $newToken,
            'password' => $newToken, // Otomatis di-hash oleh mutator User::setPasswordAttribute
        ]);

        return back()->with('success', "Akses token / password untuk {$user->nama_lengkap} berhasil direset menjadi: $newToken (tersimpan aman ter-hash).");
    }

    public function toggleBlokir(User $user)
    {
        $newStatus = $user->status_blokir === '1' ? '0' : '1';
        $user->update(['status_blokir' => $newStatus]);

        $statusText = $newStatus === '1' ? 'diblokir' : 'dibuka blokirnya';
        return back()->with('success', "Akun {$user->nama_lengkap} berhasil {$statusText}.");
    }

    public function destroy(User $user)
    {
        $name = $user->nama_lengkap;
        $user->delete();
        return back()->with('success', "Data pengguna $name berhasil dihapus.");
    }
}
