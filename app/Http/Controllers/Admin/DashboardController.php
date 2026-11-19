<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\BankSoal;
use App\Models\Exam;
use App\Models\ExamSession;
use App\Models\Question;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $activeYear = AcademicYear::getActive();

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

        $totalSiswa = $totalSiswaAktif;
        $totalGuru = User::where('role_id', 2)->count();
        $totalUjian = Exam::count();
        $totalTerblokir = ExamSession::where('status_siswa', 'terblokir')->count();

        $totalBankSoal = BankSoal::count();
        $totalButirSoal = Question::count();
        $totalMapel = Subject::count();

        // Ujian aktif hari ini
        $ujianAktif = Exam::with('subject')
            ->orderBy('waktu_mulai', 'desc')
            ->take(5)
            ->get();

        // Sesi ujian terbaru
        $sesiTerbaru = ExamSession::with(['exam.subject', 'user'])
            ->orderBy('id', 'desc')
            ->take(6)
            ->get();

        return view('admin.dashboard', compact(
            'totalSiswa',
            'totalSiswaAktif',
            'totalAlumni',
            'totalGuru',
            'totalUjian',
            'totalTerblokir',
            'totalBankSoal',
            'totalButirSoal',
            'totalMapel',
            'ujianAktif',
            'sesiTerbaru',
            'activeYear'
        ));
    }
}

