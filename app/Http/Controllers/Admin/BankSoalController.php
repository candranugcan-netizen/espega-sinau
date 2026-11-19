<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BankSoal;
use App\Models\Question;
use App\Models\Subject;
use App\Models\User;
use App\Services\ImageOptimizerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class BankSoalController extends Controller
{
    public function index(Request $request)
    {
        $tab = $request->input('tab', 'bank');

        // Tab 1: Bank Soal
        $bankQuery = BankSoal::with(['subject', 'guru'])->withCount('questions');

        if ($request->filled('q_bank')) {
            $q = $request->input('q_bank');
            $bankQuery->where('nama_bank', 'LIKE', "%$q%")
                ->orWhereHas('subject', fn($s) => $s->where('nama_mapel', 'LIKE', "%$q%"))
                ->orWhereHas('guru', fn($g) => $g->where('nama_lengkap', 'LIKE', "%$q%"));
        }

        if ($request->filled('subject_id')) {
            $bankQuery->where('subject_id', $request->input('subject_id'));
        }

        if ($request->filled('guru_id')) {
            $bankQuery->where('guru_id', $request->input('guru_id'));
        }

        if ($request->filled('status_rilis') && $request->input('status_rilis') !== 'all') {
            $bankQuery->where('status_rilis', $request->input('status_rilis'));
        }

        $banks = $bankQuery->orderBy('id', 'desc')->paginate(15, ['*'], 'bank_page')->withQueryString();

        // Tab 2: Butir Soal
        $soalQuery = Question::with(['bankSoal', 'subject']);

        if ($request->filled('bank_id')) {
            $soalQuery->where('bank_soal_id', $request->input('bank_id'));
        }

        if ($request->filled('q_soal')) {
            $q = $request->input('q_soal');
            $soalQuery->where('pertanyaan', 'LIKE', "%$q%");
        }

        $questions = $soalQuery->orderBy('id', 'desc')->paginate(20, ['*'], 'soal_page')->withQueryString();

        $subjects = Subject::orderBy('nama_mapel')->get();
        $teachers = User::where('role_id', 2)->orderBy('nama_lengkap')->get();
        $allBanks = BankSoal::orderBy('nama_bank')->get();

        $classesList = User::where('role_id', 3)
            ->whereNotNull('kelas')
            ->where('kelas', '!=', '')
            ->distinct()
            ->orderBy('kelas')
            ->pluck('kelas')
            ->toArray();

        if (empty($classesList)) {
            $classesList = ['7A', '7B', '8A', '8B', '9A', '9B'];
        }

        return view('admin.bank_soal.index', compact('banks', 'questions', 'subjects', 'teachers', 'allBanks', 'classesList', 'tab'));
    }

    public function storeBank(Request $request)
    {
        $request->validate([
            'nama_bank' => 'required|string|max:150',
            'subject_id' => 'required|exists:subjects,id',
            'guru_id' => 'required|exists:users,id',
            'kelas_rilis' => 'nullable|array',
        ]);

        $kelasStr = $request->filled('kelas_rilis') ? implode(', ', $request->input('kelas_rilis')) : null;

        BankSoal::create([
            'nama_bank' => $request->input('nama_bank'),
            'subject_id' => $request->input('subject_id'),
            'guru_id' => $request->input('guru_id'),
            'kelas_rilis' => $kelasStr,
            'status_rilis' => !empty($kelasStr) ? 'rilis' : 'draft',
        ]);

        return redirect()->route('admin.bank_soal.index', ['tab' => 'bank'])->with('success', 'Paket Bank Soal baru berhasil dibuat.');
    }

    public function toggleRilis(Request $request, BankSoal $bank)
    {
        $status = $request->input('status', 'rilis');
        $kelas = $request->input('kelas_rilis', []);

        $kelasStr = is_array($kelas) ? implode(', ', $kelas) : $kelas;

        $bank->update([
            'status_rilis' => $status,
            'kelas_rilis' => $status === 'rilis' ? $kelasStr : null,
        ]);

        // Sinkronisasi kelas soal di dalamnya
        Question::where('bank_soal_id', $bank->id)->update([
            'kelas' => $status === 'rilis' ? $kelasStr : null,
        ]);

        return back()->with('success', "Status paket {$bank->nama_bank} berhasil diperbarui.");
    }

    public function destroyBank(BankSoal $bank)
    {
        $bank->delete();
        return back()->with('success', 'Paket bank soal beserta butir soal di dalamnya berhasil dihapus.');
    }

    public function acakSemuaSoal(BankSoal $bank)
    {
        $questions = Question::where('bank_soal_id', $bank->id)->get();
        if ($questions->isEmpty()) {
            return back()->with('error', "Paket {$bank->nama_bank} belum memiliki butir soal untuk diacak.");
        }

        $orders = range(1, $questions->count());
        shuffle($orders);

        foreach ($questions as $idx => $q) {
            $q->update(['urutan' => $orders[$idx]]);
        }

        return back()->with('success', "Seluruh butir soal pada paket '{$bank->nama_bank}' (" . $questions->count() . " butir soal) berhasil diacak nomor urutnya.");
    }

    public function resetUrutanSoal(BankSoal $bank)
    {
        Question::where('bank_soal_id', $bank->id)->update(['urutan' => 0]);
        return back()->with('success', "Urutan nomor butir soal pada paket '{$bank->nama_bank}' berhasil direset ke standar.");
    }

    public function bulkActionQuestions(Request $request)
    {
        $action = $request->input('action_type');
        $ids = $request->input('selected_ids', []);

        if (empty($ids) || !is_array($ids)) {
            return back()->with('error', 'Pilih minimal satu butir soal untuk melakukan aksi massal.');
        }

        $questions = Question::whereIn('id', $ids)->get();
        if ($questions->isEmpty()) {
            return back()->with('error', 'Butir soal tidak ditemukan.');
        }

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
            return back()->with('success', count($questions) . ' butir soal terpilih berhasil dihapus.');
        }

        if ($action === 'acak') {
            if ($questions->count() < 2) {
                return back()->with('error', 'Pilih minimal 2 butir soal untuk diacak nomor urutannya.');
            }

            // Kelompokkan per bank_soal_id agar nomor urut konsisten per paket
            $grouped = $questions->groupBy('bank_soal_id');
            foreach ($grouped as $bankId => $qGroup) {
                $count = $qGroup->count();
                $currentOrders = $qGroup->pluck('urutan')->map(fn($u) => (int)$u)->toArray();
                if (count(array_filter($currentOrders)) < $count) {
                    $shuffledOrders = range(1, $count);
                } else {
                    $shuffledOrders = $currentOrders;
                }
                shuffle($shuffledOrders);

                foreach ($qGroup->values() as $idx => $q) {
                    $q->update(['urutan' => $shuffledOrders[$idx]]);
                }
            }

            return back()->with('success', count($questions) . ' butir soal terpilih berhasil diacak nomor urutannya.');
        }

        if ($action === 'move') {
            $targetBankId = $request->input('target_bank_id');
            $targetBank = BankSoal::find($targetBankId);
            if (!$targetBank) {
                return back()->with('error', 'Paket Bank Soal tujuan pemindahan tidak valid.');
            }

            $maxUrutan = Question::where('bank_soal_id', $targetBank->id)->max('urutan') ?: 0;
            foreach ($questions as $q) {
                $maxUrutan++;
                $q->update([
                    'bank_soal_id' => $targetBank->id,
                    'subject_id' => $targetBank->subject_id,
                    'kelas' => $targetBank->kelas_rilis,
                    'urutan' => $maxUrutan,
                ]);
            }

            return back()->with('success', count($questions) . ' butir soal berhasil dipindahkan ke paket "' . $targetBank->nama_bank . '".');
        }

        return back()->with('error', 'Aksi massal tidak dikenali.');
    }

    public function storeQuestion(Request $request)
    {
        $request->validate([
            'bank_soal_id' => 'required|exists:bank_soal,id',
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

        $bank = BankSoal::findOrFail($request->input('bank_soal_id'));

        $data = $request->only(['pertanyaan', 'pil_a', 'pil_b', 'pil_c', 'pil_d', 'pil_e']);
        $data['kunci_jawaban'] = strtoupper($request->input('kunci_jawaban'));
        $data['bank_soal_id'] = $bank->id;
        $data['subject_id'] = $bank->subject_id;
        $data['kelas'] = $bank->kelas_rilis;

        $maxUrutan = Question::where('bank_soal_id', $bank->id)->max('urutan') ?: 0;
        $data['urutan'] = $request->filled('urutan') ? (int)$request->input('urutan') : ($maxUrutan + 1);

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

        return back()->with('success', 'Butir soal baru berhasil ditambahkan.');
    }

    public function updateQuestion(Request $request, Question $question)
    {
        $request->validate([
            'pertanyaan' => 'required|string',
            'pil_a' => 'nullable|string',
            'pil_b' => 'nullable|string',
            'pil_c' => 'nullable|string',
            'pil_d' => 'nullable|string',
            'pil_e' => 'nullable|string',
            'kunci_jawaban' => 'required|in:A,B,C,D,E,a,b,c,d,e',
            'bank_soal_id' => 'nullable|exists:bank_soal,id',
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

        if ($request->filled('bank_soal_id')) {
            $newBank = BankSoal::find($request->input('bank_soal_id'));
            if ($newBank) {
                $data['bank_soal_id'] = $newBank->id;
                $data['subject_id'] = $newBank->subject_id;
                $data['kelas'] = $newBank->kelas_rilis;
            }
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

            // Ganti file gambar baru jika diupload
            if ($request->hasFile($field)) {
                if (!empty($question->$field) && File::exists($uploadDir . '/' . $question->$field)) {
                    File::delete($uploadDir . '/' . $question->$field);
                }
                $filename = ImageOptimizerService::uploadAndConvertToWebp($request->file($field), $uploadDir, 1600, 82, 'soal_');
                $data[$field] = $filename;
            }
        }

        $question->update($data);

        return back()->with('success', 'Butir soal berhasil diperbarui.');
    }

    public function destroyQuestion(Question $question)
    {
        $uploadDir = public_path('assets/img/soal');
        foreach (['img_pertanyaan', 'img_a', 'img_b', 'img_c', 'img_d', 'img_e'] as $field) {
            if (!empty($question->$field) && File::exists($uploadDir . '/' . $question->$field)) {
                File::delete($uploadDir . '/' . $question->$field);
            }
        }

        $question->delete();

        return back()->with('success', 'Butir soal berhasil dihapus.');
    }
}

