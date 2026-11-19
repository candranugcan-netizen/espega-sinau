<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\BankSoal;
use App\Models\Question;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BankSoalController extends Controller
{
    public function index(Request $request)
    {
        $guruId = Auth::id();

        $query = BankSoal::with('subject')
            ->withCount('questions')
            ->where('guru_id', $guruId);

        if ($request->filled('q')) {
            $q = $request->input('q');
            $query->where('nama_bank', 'LIKE', "%$q%");
        }

        if ($request->filled('subject_id')) {
            $query->where('subject_id', $request->input('subject_id'));
        }

        $banks = $query->orderBy('id', 'desc')->paginate(12)->withQueryString();
        $subjects = Subject::where('guru_id', $guruId)->get();

        $classesList = User::where('role_id', 3)
            ->whereNotNull('kelas')
            ->where('kelas', '!=', '')
            ->distinct()
            ->orderBy('kelas')
            ->pluck('kelas')
            ->toArray();

        return view('guru.bank_soal.index', compact('banks', 'subjects', 'classesList'));
    }

    public function store(Request $request)
    {
        $guruId = Auth::id();

        $request->validate([
            'nama_bank' => 'required|string|max:150',
            'subject_id' => 'required|exists:subjects,id',
            'kelas_rilis' => 'nullable|array',
        ]);

        $kelasStr = $request->filled('kelas_rilis') ? implode(', ', $request->input('kelas_rilis')) : null;

        BankSoal::create([
            'nama_bank' => $request->input('nama_bank'),
            'subject_id' => $request->input('subject_id'),
            'guru_id' => $guruId,
            'kelas_rilis' => $kelasStr,
            'status_rilis' => !empty($kelasStr) ? 'rilis' : 'draft',
        ]);

        return redirect()->route('guru.bank_soal.index')->with('success', 'Paket Bank Soal berhasil dibuat.');
    }

    public function update(Request $request, BankSoal $bank)
    {
        if ($bank->guru_id !== Auth::id()) {
            abort(403);
        }

        $request->validate([
            'nama_bank' => 'required|string|max:150',
            'subject_id' => 'required|exists:subjects,id',
            'kelas_rilis' => 'nullable|array',
            'status_rilis' => 'required|in:draft,rilis',
        ]);

        $status = $request->input('status_rilis');
        $kelasStr = $request->filled('kelas_rilis') ? implode(', ', $request->input('kelas_rilis')) : null;

        $bank->update([
            'nama_bank' => $request->input('nama_bank'),
            'subject_id' => $request->input('subject_id'),
            'kelas_rilis' => $status === 'rilis' ? $kelasStr : null,
            'status_rilis' => $status,
        ]);

        // Sinkronisasi kelas soal di dalamnya
        Question::where('bank_soal_id', $bank->id)->update([
            'kelas' => $status === 'rilis' ? $kelasStr : null,
        ]);

        return redirect()->route('guru.bank_soal.index')->with('success', 'Paket Bank Soal berhasil diperbarui.');
    }

    public function destroy(BankSoal $bank)
    {
        if ($bank->guru_id !== Auth::id()) {
            abort(403);
        }

        $bank->delete();
        return redirect()->route('guru.bank_soal.index')->with('success', 'Paket Bank Soal berhasil dihapus.');
    }
}

