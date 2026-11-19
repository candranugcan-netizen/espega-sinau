<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\ExamSession;
use App\Models\User;
use Illuminate\Http\Request;

class ExamSessionController extends Controller
{
    public function index(Request $request)
    {
        $query = ExamSession::with(['exam.subject', 'user'])->withCount('answers');

        if ($request->filled('exam_id')) {
            $query->where('exam_id', $request->input('exam_id'));
        }

        if ($request->filled('status')) {
            $query->where('status_siswa', $request->input('status'));
        }

        if ($request->filled('q')) {
            $q = $request->input('q');
            $query->whereHas('user', function ($u) use ($q) {
                $u->where('nama_lengkap', 'LIKE', "%$q%")
                  ->orWhere('username', 'LIKE', "%$q%")
                  ->orWhere('kelas', 'LIKE', "%$q%");
            });
        }

        $sessions = $query->orderBy('id', 'desc')->paginate(20)->withQueryString();
        $exams = Exam::orderBy('id', 'desc')->take(30)->get();

        $stats = [
            'total' => ExamSession::count(),
            'pengerjaan' => ExamSession::where('status_siswa', 'pengerjaan')->count(),
            'selesai' => ExamSession::where('status_siswa', 'selesai')->count(),
            'terblokir' => ExamSession::where('status_siswa', 'terblokir')->count(),
            'total_pelanggaran' => (int) ExamSession::sum('jumlah_blokir'),
        ];

        return view('admin.sessions.index', compact('sessions', 'exams', 'stats'));
    }

    public function unblock(ExamSession $session)
    {
        $session->update(['status_siswa' => 'pengerjaan']);

        if ($session->user) {
            $session->user->update(['status_blokir' => '0']);
        }

        return back()->with('success', "Blokir untuk siswa {$session->user->nama_lengkap} berhasil dibuka. Siswa dapat melanjutkan ujian.");
    }

    public function forceSubmit(ExamSession $session)
    {
        $session->finalize();
        return back()->with('success', "Sesi siswa {$session->user->nama_lengkap} berhasil diselesaikan paksa dengan skor {$session->skor}.");
    }

    public function resetSession(ExamSession $session)
    {
        $session->answers()->delete();
        $session->update([
            'skor' => 0,
            'status_siswa' => 'belum',
            'jumlah_blokir' => 0,
            'mulai_ujian' => null,
            'selesai_ujian' => null,
            'sisa_waktu' => null,
        ]);

        if ($session->user) {
            $session->user->update(['status_blokir' => '0']);
        }

        return back()->with('success', "Sesi siswa {$session->user->nama_lengkap} telah direset total. Siswa dapat mengulang dari awal.");
    }

    public function bulkAction(Request $request)
    {
        $action = $request->input('action_type');
        $ids = $request->input('selected_ids', []);

        if (empty($ids) || !is_array($ids)) {
            return back()->with('error', 'Pilih minimal satu sesi siswa untuk melakukan aksi massal.');
        }

        $sessions = ExamSession::with(['user', 'exam'])->whereIn('id', $ids)->get();
        $count = 0;

        switch ($action) {
            case 'force_submit':
                foreach ($sessions as $session) {
                    if ($session->status_siswa !== 'selesai') {
                        $session->finalize();
                        $count++;
                    }
                }
                return back()->with('success', "$count sesi ujian siswa berhasil diselesaikan secara paksa.");

            case 'reset':
                foreach ($sessions as $session) {
                    $session->answers()->delete();
                    $session->update([
                        'skor' => 0,
                        'status_siswa' => 'belum',
                        'jumlah_blokir' => 0,
                        'mulai_ujian' => null,
                        'selesai_ujian' => null,
                        'sisa_waktu' => null,
                    ]);
                    if ($session->user) {
                        $session->user->update(['status_blokir' => '0']);
                    }
                    $count++;
                }
                return back()->with('success', "$count sesi ujian berhasil direset ke status awal.");

            case 'unblock':
                foreach ($sessions as $session) {
                    $session->update(['status_siswa' => 'pengerjaan']);
                    if ($session->user) {
                        $session->user->update(['status_blokir' => '0']);
                    }
                    $count++;
                }
                return back()->with('success', "$count siswa yang terblokir berhasil dibuka blokirnya.");

            case 'delete':
                foreach ($sessions as $session) {
                    $session->answers()->delete();
                    $session->delete();
                    $count++;
                }
                return back()->with('success', "$count sesi pengerjaan ujian berhasil dihapus.");

            default:
                return back()->with('error', 'Aksi massal tidak dikenali.');
        }
    }

    public function destroy(ExamSession $session)
    {
        $session->answers()->delete();
        $session->delete();
        return back()->with('success', 'Riwayat sesi siswa berhasil dihapus.');
    }
}
