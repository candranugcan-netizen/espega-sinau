<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\BankSoal;
use App\Models\Exam;
use App\Models\ExamSession;
use App\Models\Question;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $guru = Auth::user();
        $guruId = $guru->id;

        $subjects = Subject::where('guru_id', $guruId)->get();
        $totalBank = BankSoal::where('guru_id', $guruId)->count();
        $totalBankRilis = BankSoal::where('guru_id', $guruId)->where('status_rilis', 'rilis')->count();

        $totalSoal = Question::whereHas('subject', fn($s) => $s->where('guru_id', $guruId))->count();

        $ujianMapelGuru = Exam::whereHas('subject', fn($s) => $s->where('guru_id', $guruId))->pluck('id');
        $totalPeserta = ExamSession::whereIn('exam_id', $ujianMapelGuru)->count();

        $bankTerbaru = BankSoal::with('subject')
            ->where('guru_id', $guruId)
            ->withCount('questions')
            ->orderBy('id', 'desc')
            ->take(5)
            ->get();

        $ujianTerkait = Exam::with(['subject', 'sessions'])
            ->whereIn('id', $ujianMapelGuru)
            ->orderBy('id', 'desc')
            ->take(5)
            ->get();

        return view('guru.dashboard', compact(
            'guru',
            'subjects',
            'totalBank',
            'totalBankRilis',
            'totalSoal',
            'totalPeserta',
            'bankTerbaru',
            'ujianTerkait'
        ));
    }
}

