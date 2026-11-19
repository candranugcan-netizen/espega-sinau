<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\ExamSession;
use App\Models\User;
use App\Services\ExcelService;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $subjects = \App\Models\Subject::orderBy('nama_mapel')->get();

        $selectedSubjectId = $request->input('subject_id');
        $selectedKelas = $request->input('kelas');
        $selectedExamId = $request->input('exam_id');

        $examsQuery = Exam::with('subject')->orderBy('id', 'desc');
        if ($selectedSubjectId) {
            $examsQuery->where('subject_id', $selectedSubjectId);
        }
        $exams = $examsQuery->get();

        // Jika exam_id tidak ada di daftar exams (misal setelah ganti filter subject), reset exam_id
        if ($selectedExamId && !$exams->contains('id', $selectedExamId)) {
            $selectedExamId = null;
        }

        // Jika belum ada exam_id yang dipilih dan filter subject belum diset, default ke exam pertama jika ada
        if (!$request->has('exam_id') && !$selectedSubjectId && $exams->isNotEmpty()) {
            $selectedExamId = $exams->first()->id;
        }

        $selectedExam = $selectedExamId ? $exams->firstWhere('id', $selectedExamId) : null;
        $selectedSubject = $selectedSubjectId ? $subjects->firstWhere('id', $selectedSubjectId) : null;

        $query = ExamSession::with(['user', 'exam.subject']);

        if ($selectedExamId) {
            $query->where('exam_id', $selectedExamId);
        } elseif ($selectedSubjectId) {
            $query->whereHas('exam', function ($eq) use ($selectedSubjectId) {
                $eq->where('subject_id', $selectedSubjectId);
            });
        }

        if ($selectedKelas) {
            $query->whereHas('user', function ($uq) use ($selectedKelas) {
                $uq->where('kelas', $selectedKelas);
            });
        }

        $results = $query->orderBy('skor', 'desc')->get();

        $classesList = User::where('role_id', 3)
            ->whereNotNull('kelas')
            ->where('kelas', '!=', '')
            ->distinct()
            ->orderBy('kelas')
            ->pluck('kelas')
            ->toArray();

        $avgScore = $results->count() > 0 ? round($results->avg('skor'), 1) : 0;
        $maxScore = $results->count() > 0 ? $results->max('skor') : 0;
        $minScore = $results->count() > 0 ? $results->min('skor') : 0;
        $tuntasCount = $results->where('skor', '>=', 75)->count();

        return view('admin.reports.index', compact(
            'subjects',
            'exams',
            'selectedSubjectId',
            'selectedSubject',
            'selectedExam',
            'selectedExamId',
            'selectedKelas',
            'classesList',
            'results',
            'avgScore',
            'maxScore',
            'minScore',
            'tuntasCount'
        ));
    }

    public function exportExcel(Request $request)
    {
        $examId = $request->filled('exam_id') ? (int)$request->input('exam_id') : null;
        $subjectId = $request->filled('subject_id') ? (int)$request->input('subject_id') : null;
        $kelas = $request->input('kelas');

        if (!$examId && $subjectId) {
            $examId = Exam::where('subject_id', $subjectId)->first()?->id;
        }

        return ExcelService::exportHasilUjianLengkap($examId, $kelas);
    }
}
