<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Http\Request;

class SubjectController extends Controller
{
    public function index(Request $request)
    {
        $query = Subject::with('guru')->withCount(['exams', 'bankSoals', 'questions']);

        if ($request->filled('q')) {
            $q = $request->input('q');
            $query->where(function ($sq) use ($q) {
                $sq->where('nama_mapel', 'LIKE', "%$q%")
                   ->orWhere('kode_mapel', 'LIKE', "%$q%")
                   ->orWhereHas('guru', fn($gq) => $gq->where('nama_lengkap', 'LIKE', "%$q%"));
            });
        }

        if ($request->filled('kelompok')) {
            $query->where('kelompok', $request->input('kelompok'));
        }

        $subjects = $query->orderBy('kelompok')->orderBy('nama_mapel')->paginate(15)->withQueryString();
        $teachers = User::where('role_id', 2)->orderBy('nama_lengkap')->get();

        $totalMapel = Subject::count();
        $totalGuruPengampu = User::where('role_id', 2)->has('subjects')->count();

        return view('admin.subjects.index', compact('subjects', 'teachers', 'totalMapel', 'totalGuruPengampu'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama_mapel' => 'required|string|max:100',
            'kode_mapel' => 'nullable|string|max:20',
            'kelompok' => 'required|string|max:50',
            'guru_id' => 'nullable|exists:users,id',
            'kkm' => 'nullable|integer|min:0|max:100',
            'keterangan' => 'nullable|string',
        ]);

        $kode = $request->input('kode_mapel');
        if (empty($kode)) {
            $words = explode(' ', trim($request->input('nama_mapel')));
            $code = '';
            if (count($words) === 1) {
                $code = strtoupper(substr($words[0], 0, 4));
            } else {
                foreach ($words as $w) {
                    $code .= strtoupper(substr($w, 0, 1));
                }
            }
            $kode = substr($code, 0, 10);
        }

        $subject = Subject::create([
            'nama_mapel' => trim($request->input('nama_mapel')),
            'kode_mapel' => strtoupper(trim($kode)),
            'kelompok' => $request->input('kelompok'),
            'guru_id' => $request->input('guru_id'),
            'kkm' => $request->input('kkm') ?: 75,
            'keterangan' => $request->input('keterangan'),
        ]);

        return back()->with('success', "Mata Pelajaran {$subject->nama_mapel} berhasil ditambahkan.");
    }

    public function update(Request $request, Subject $subject)
    {
        $request->validate([
            'nama_mapel' => 'required|string|max:100',
            'kode_mapel' => 'nullable|string|max:20',
            'kelompok' => 'required|string|max:50',
            'guru_id' => 'nullable|exists:users,id',
            'kkm' => 'nullable|integer|min:0|max:100',
            'keterangan' => 'nullable|string',
        ]);

        $subject->update([
            'nama_mapel' => trim($request->input('nama_mapel')),
            'kode_mapel' => strtoupper(trim($request->input('kode_mapel') ?: $subject->kode_mapel)),
            'kelompok' => $request->input('kelompok'),
            'guru_id' => $request->input('guru_id'),
            'kkm' => $request->input('kkm') ?: 75,
            'keterangan' => $request->input('keterangan'),
        ]);

        return back()->with('success', "Data Mata Pelajaran {$subject->nama_mapel} berhasil diperbarui.");
    }

    public function destroy(Subject $subject)
    {
        $nama = $subject->nama_mapel;
        if ($subject->exams()->count() > 0 || $subject->bankSoals()->count() > 0) {
            return back()->with('error', "Mata pelajaran {$nama} tidak dapat dihapus karena sudah memiliki jadwal ujian atau bank soal terkait.");
        }

        $subject->delete();
        return back()->with('success', "Mata Pelajaran {$nama} berhasil dihapus.");
    }
}

