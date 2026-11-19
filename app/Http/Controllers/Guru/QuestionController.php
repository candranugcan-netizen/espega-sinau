<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\BankSoal;
use App\Models\Question;
use App\Services\ExcelService;
use App\Services\ImageOptimizerService;
use App\Services\PdfService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;

class QuestionController extends Controller
{
    public function index(BankSoal $bank)
    {
        if ($bank->guru_id !== Auth::id() && Auth::user()->role_id !== 1) {
            abort(403);
        }

        $questions = Question::where('bank_soal_id', $bank->id)
            ->orderByRaw('(CASE WHEN urutan > 0 THEN urutan ELSE 9999999 END) ASC, id ASC')
            ->get();

        return view('guru.questions.index', compact('bank', 'questions'));
    }

    public function store(Request $request, BankSoal $bank)
    {
        if ($bank->guru_id !== Auth::id() && Auth::user()->role_id !== 1) {
            abort(403);
        }

        $request->validate([
            'pertanyaan' => 'required|string',
            'pil_a' => 'nullable|string',
            'pil_b' => 'nullable|string',
            'pil_c' => 'nullable|string',
            'pil_d' => 'nullable|string',
            'pil_e' => 'nullable|string',
            'kunci_jawaban' => 'required|in:A,B,C,D,E,a,b,c,d,e',
            'img_pertanyaan' => 'nullable|image|max:3072',
            'img_a' => 'nullable|image|max:2048',
            'img_b' => 'nullable|image|max:2048',
            'img_c' => 'nullable|image|max:2048',
            'img_d' => 'nullable|image|max:2048',
            'img_e' => 'nullable|image|max:2048',
        ]);

        $data = $request->only(['pertanyaan', 'pil_a', 'pil_b', 'pil_c', 'pil_d', 'pil_e']);
        $data['kunci_jawaban'] = strtoupper($request->input('kunci_jawaban'));
        $data['bank_soal_id'] = $bank->id;
        $data['subject_id'] = $bank->subject_id;
        $data['kelas'] = $bank->kelas_rilis;

        $maxUrutan = Question::where('bank_soal_id', $bank->id)->max('urutan') ?: 0;
        $data['urutan'] = $maxUrutan + 1;

        // Upload gambar pertanyaan dan setiap pilihan
        $uploadDir = public_path('assets/img/soal');
        if (!File::isDirectory($uploadDir)) {
            File::makeDirectory($uploadDir, 0755, true);
        }

        foreach (['img_pertanyaan', 'img_a', 'img_b', 'img_c', 'img_d', 'img_e'] as $field) {
            if ($request->hasFile($field)) {
                $filename = ImageOptimizerService::uploadAndConvertToWebp($request->file($field), $uploadDir, 1600, 82, 'soal_');
                $data[$field] = $filename;
            }
        }

        Question::create($data);

        return redirect()->route('guru.questions.index', $bank)->with('success', 'Butir soal berhasil ditambahkan ke bank soal.');
    }

    public function update(Request $request, Question $question)
    {
        $bank = $question->bankSoal;
        if ($bank && $bank->guru_id !== Auth::id() && Auth::user()->role_id !== 1) {
            abort(403);
        }

        $request->validate([
            'pertanyaan' => 'required|string',
            'pil_a' => 'nullable|string',
            'pil_b' => 'nullable|string',
            'pil_c' => 'nullable|string',
            'pil_d' => 'nullable|string',
            'pil_e' => 'nullable|string',
            'kunci_jawaban' => 'required|in:A,B,C,D,E,a,b,c,d,e',
            'urutan' => 'nullable|integer',
            'img_pertanyaan' => 'nullable|image|max:3072',
            'img_a' => 'nullable|image|max:2048',
            'img_b' => 'nullable|image|max:2048',
            'img_c' => 'nullable|image|max:2048',
            'img_d' => 'nullable|image|max:2048',
            'img_e' => 'nullable|image|max:2048',
        ]);

        $data = $request->only(['pertanyaan', 'pil_a', 'pil_b', 'pil_c', 'pil_d', 'pil_e']);
        $data['kunci_jawaban'] = strtoupper($request->input('kunci_jawaban'));

        if ($request->filled('urutan')) {
            $data['urutan'] = (int)$request->input('urutan');
        }

        $uploadDir = public_path('assets/img/soal');
        if (!File::isDirectory($uploadDir)) {
            File::makeDirectory($uploadDir, 0755, true);
        }

        foreach (['img_pertanyaan', 'img_a', 'img_b', 'img_c', 'img_d', 'img_e'] as $field) {
            // Hapus gambar jika checkbox hapus dicentang
            if ($request->boolean('delete_' . $field)) {
                if (!empty($question->$field) && File::exists($uploadDir . '/' . $question->$field)) {
                    File::delete($uploadDir . '/' . $question->$field);
                }
                $data[$field] = null;
            }

            // Ganti dengan file baru jika diupload
            if ($request->hasFile($field)) {
                if (!empty($question->$field) && File::exists($uploadDir . '/' . $question->$field)) {
                    File::delete($uploadDir . '/' . $question->$field);
                }
                $filename = ImageOptimizerService::uploadAndConvertToWebp($request->file($field), $uploadDir, 1600, 82, 'soal_');
                $data[$field] = $filename;
            }
        }

        $question->update($data);

        return redirect()->route('guru.questions.index', $bank)->with('success', 'Butir soal berhasil diperbarui.');
    }

    public function import(Request $request, BankSoal $bank)
    {
        if ($bank->guru_id !== Auth::id() && Auth::user()->role_id !== 1) {
            abort(403);
        }

        $request->validate([
            'file_excel' => 'required|file|mimes:xlsx,xls,csv,txt',
        ]);

        try {
            $count = ExcelService::importSoal($bank->id, $request->file('file_excel'));
            return redirect()->route('guru.questions.index', $bank)->with('success', "$count butir soal berhasil diimpor dari file Excel.");
        } catch (\Exception $e) {
            return redirect()->route('guru.questions.index', $bank)->with('error', "Gagal mengimpor soal: " . $e->getMessage());
        }
    }

    public function exportExcel(BankSoal $bank)
    {
        if ($bank->guru_id !== Auth::id() && Auth::user()->role_id !== 1) {
            abort(403);
        }

        return ExcelService::exportSoalExcel($bank->id);
    }

    public function exportPdf(BankSoal $bank, Request $request)
    {
        if ($bank->guru_id !== Auth::id() && Auth::user()->role_id !== 1) {
            abort(403);
        }

        $withKunci = $request->boolean('with_kunci', true);
        return PdfService::exportBankSoalPdf($bank->id, $withKunci);
    }

    public function template()
    {
        return ExcelService::templateSoal();
    }

    public function bulkAction(Request $request, BankSoal $bank)
    {
        if ($bank->guru_id !== Auth::id() && Auth::user()->role_id !== 1) {
            abort(403);
        }

        $action = $request->input('action_type');
        $ids = $request->input('selected_ids', []);

        if (empty($ids) || !is_array($ids)) {
            return redirect()->route('guru.questions.index', $bank)->with('error', 'Pilih minimal satu soal untuk melakukan aksi massal.');
        }

        $questions = Question::where('bank_soal_id', $bank->id)->whereIn('id', $ids)->get();

        if ($action === 'delete') {
            $uploadDir = public_path('assets/img/soal');
            foreach ($questions as $q) {
                foreach (['img_pertanyaan', 'img_a', 'img_b', 'img_c', 'img_d', 'img_e'] as $field) {
                    if (!empty($q->$field) && File::exists($uploadDir . '/' . $q->$field)) {
                        File::delete($uploadDir . '/' . $q->$field);
                    }
                }
                $q->delete();
            }
            return redirect()->route('guru.questions.index', $bank)->with('success', count($questions) . ' butir soal berhasil dihapus.');
        }

        if ($action === 'acak') {
            if (count($questions) < 2) {
                return redirect()->route('guru.questions.index', $bank)->with('error', 'Pilih minimal 2 butir soal untuk diacak urutannya.');
            }

            $currentOrders = $questions->pluck('urutan')->toArray();
            $shuffledOrders = $currentOrders;
            shuffle($shuffledOrders);

            foreach ($questions as $idx => $q) {
                $q->update(['urutan' => $shuffledOrders[$idx]]);
            }

            return redirect()->route('guru.questions.index', $bank)->with('success', count($questions) . ' butir soal terpilih berhasil diacak urutannya.');
        }

        return redirect()->route('guru.questions.index', $bank)->with('error', 'Aksi massal tidak dikenali.');
    }

    public function acakSemua(BankSoal $bank)
    {
        if ($bank->guru_id !== Auth::id() && Auth::user()->role_id !== 1) {
            abort(403);
        }

        $questions = Question::where('bank_soal_id', $bank->id)->get();
        if ($questions->isEmpty()) {
            return redirect()->route('guru.questions.index', $bank)->with('error', 'Tidak ada butir soal untuk diacak.');
        }

        $orders = range(1, $questions->count());
        shuffle($orders);

        foreach ($questions as $idx => $q) {
            $q->update(['urutan' => $orders[$idx]]);
        }

        return redirect()->route('guru.questions.index', $bank)->with('success', "Seluruh butir soal (" . $questions->count() . " soal) berhasil diacak.");
    }

    public function resetUrutan(BankSoal $bank)
    {
        if ($bank->guru_id !== Auth::id() && Auth::user()->role_id !== 1) {
            abort(403);
        }

        Question::where('bank_soal_id', $bank->id)->update(['urutan' => 0]);

        return redirect()->route('guru.questions.index', $bank)->with('success', 'Urutan butir soal berhasil direset ke urutan nomor ID standar.');
    }

    public function destroy(Question $question)
    {
        $bank = $question->bankSoal;
        if ($bank && $bank->guru_id !== Auth::id() && Auth::user()->role_id !== 1) {
            abort(403);
        }

        $uploadDir = public_path('assets/img/soal');
        foreach (['img_pertanyaan', 'img_a', 'img_b', 'img_c', 'img_d', 'img_e'] as $field) {
            if (!empty($question->$field) && File::exists($uploadDir . '/' . $question->$field)) {
                File::delete($uploadDir . '/' . $question->$field);
            }
        }

        $question->delete();
        return redirect()->route('guru.questions.index', $bank)->with('success', 'Butir soal berhasil dihapus.');
    }
}
