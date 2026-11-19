<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Exam;
use App\Models\ExamSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $siswa = Auth::user();
        $kelas = trim($siswa->kelas ?? '');
        $activeYear = AcademicYear::getActive();

        // Jika siswa berstatus Alumni, tidak ada ujian aktif baru
        if ($siswa->isAlumni()) {
            $activeExams = collect();
            $upcomingExams = collect();
            
            $completedExams = Exam::with(['subject', 'sessions' => function ($query) use ($siswa) {
                $query->where('user_id', $siswa->id);
            }])
            ->whereHas('sessions', function ($query) use ($siswa) {
                $query->where('user_id', $siswa->id);
            })
            ->get();

            return view('siswa.dashboard', compact('siswa', 'activeExams', 'upcomingExams', 'completedExams', 'activeYear'));
        }

        // Ambil ujian berdasarkan tahun ajaran aktif & rombel kelas
        $query = Exam::with(['subject', 'sessions' => function ($query) use ($siswa) {
            $query->where('user_id', $siswa->id);
        }]);

        if ($activeYear) {
            $query->where(function ($q) use ($activeYear) {
                $q->where('academic_year_id', $activeYear->id)
                  ->orWhereNull('academic_year_id');
            });
        }

        $exams = $query->orderBy('waktu_mulai', 'asc')
            ->get()
            ->filter(function ($exam) use ($kelas) {
                return $exam->matchesClass($kelas);
            });

        // Pisahkan menjadi ujian aktif, selesai, dan akan datang
        $activeExams = collect();
        $upcomingExams = collect();
        $completedExams = collect();

        foreach ($exams as $exam) {
            $session = $exam->sessions->first();
            if ($session && $session->status_siswa === 'selesai') {
                $completedExams->push($exam);
            } elseif ($session && $session->status_siswa === 'pengerjaan') {
                $activeExams->push($exam);
            } elseif ($exam->isActive()) {
                $activeExams->push($exam);
            } elseif ($exam->isUpcoming()) {
                $upcomingExams->push($exam);
            } else {
                // Lewat batas waktu pelaksanaan
                $completedExams->push($exam);
            }
        }

        return view('siswa.dashboard', compact('siswa', 'activeExams', 'upcomingExams', 'completedExams', 'activeYear'));
    }
}

