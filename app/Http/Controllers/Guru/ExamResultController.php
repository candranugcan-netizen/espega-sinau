<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\ExamSession;
use App\Services\ExcelService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ExamResultController extends Controller
{
    public function index(Request $request)
    {
        $guruId = Auth::id();

        $exams = Exam::whereHas('subject', fn($s) => $s->where('guru_id', $guruId))
            ->with('subject')
            ->orderBy('id', 'desc')
            ->get();

        $selectedExamId = $request->input('exam_id', $exams->first()?->id);
        $selectedExam = $exams->firstWhere('id', $selectedExamId);
        $selectedKelas = $request->input('kelas');

        $results = collect();
        $classesList = [];

        if ($selectedExam) {
            $query = ExamSession::with('user')
                ->where('exam_id', $selectedExamId);

            $classesList = ExamSession::where('exam_id', $selectedExamId)
                ->whereHas('user', fn($uq) => $uq->whereNotNull('kelas')->where('kelas', '!=', ''))
                ->join('users', 'exam_sessions.user_id', '=', 'users.id')
                ->distinct()
                ->pluck('users.kelas')
                ->sort()
                ->values()
                ->toArray();

            if (!empty($selectedKelas)) {
                $query->whereHas('user', fn($uq) => $uq->where('kelas', $selectedKelas));
            }

            $results = $query->orderBy('skor', 'desc')->get();
        }

        $avgScore = $results->count() > 0 ? round($results->avg('skor'), 1) : 0;
        $maxScore = $results->count() > 0 ? $results->max('skor') : 0;
        $minScore = $results->count() > 0 ? $results->min('skor') : 0;

        return view('guru.results.index', compact('exams', 'selectedExam', 'selectedExamId', 'selectedKelas', 'classesList', 'results', 'avgScore', 'maxScore', 'minScore'));
    }

    public function exportExcel(Request $request)
    {
        $guruId = Auth::id();
        $examId = (int)$request->input('exam_id');

        // Pastikan exam milik guru ini
        $exam = Exam::where('id', $examId)
            ->whereHas('subject', fn($s) => $s->where('guru_id', $guruId))
            ->firstOrFail();

        return ExcelService::exportHasilUjianLengkap($exam->id, $request->input('kelas'));
    }
}
