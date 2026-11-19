<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Berita;
use App\Models\Exam;
use App\Models\PermohonanInformasi;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Http\Request;

class LandingController extends Controller
{
    public function index(Request $request)
    {
        // Data statistik sekolah untuk landing page
        $totalSiswa = User::where('role_id', 3)->where('kelas', '!=', 'Alumni')->count();
        $totalAlumni = User::where('role_id', 3)->where(function ($q) {
            $q->where('status_siswa', 'alumni')->orWhere('kelas', 'Alumni')->orWhereNotNull('tahun_lulus_id');
        })->count();
        $totalGuru = User::where('role_id', 2)->count();
        $totalMapel = Subject::count();
        $totalUjian = Exam::count();

        // Data Rombel & Mapel untuk Landing Page
        $totalRombel = \App\Models\Rombel::count();
        if ($totalRombel === 0) {
            $totalRombel = User::where('role_id', 3)
                ->whereNotNull('kelas')
                ->where('kelas', '!=', '')
                ->where('kelas', '!=', 'Alumni')
                ->distinct()
                ->count('kelas');
        }

        $rombels = \App\Models\Rombel::with(['waliKelas'])->withCount('students')->orderBy('tingkat')->orderBy('nama_rombel')->get();
        $subjects = Subject::with('guru')->orderBy('kelompok')->orderBy('nama_mapel')->get();

        // Query Berita Harian & Buku Harian Sekolah yang Tampil di Web
        $query = Berita::where('tampil_di_web', true)->with(['user', 'academicYear']);

        if ($request->filled('q')) {
            $q = $request->input('q');
            $query->where(function ($sq) use ($q) {
                $sq->where('judul', 'LIKE', "%$q%")
                   ->orWhere('konten', 'LIKE', "%$q%")
                   ->orWhere('ringkasan', 'LIKE', "%$q%");
            });
        }

        if ($request->filled('academic_year_id')) {
            $query->where('academic_year_id', $request->input('academic_year_id'));
        }

        if ($request->filled('bulan')) {
            $query->whereMonth('tanggal', $request->input('bulan'));
        }

        if ($request->filled('kategori')) {
            $query->where('kategori', $request->input('kategori'));
        }

        $beritaList = $query->orderBy('tanggal', 'desc')->orderBy('id', 'desc')->paginate(6)->withQueryString();
        $academicYears = AcademicYear::orderBy('nama', 'desc')->get();
        $activeYear = AcademicYear::getActive();

        // Bulan list untuk dropdown filter
        $bulanList = [
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember',
        ];

        return view('landing', compact(
            'totalSiswa',
            'totalAlumni',
            'totalGuru',
            'totalMapel',
            'totalRombel',
            'rombels',
            'subjects',
            'totalUjian',
            'beritaList',
            'academicYears',
            'activeYear',
            'bulanList'
        ));
    }

    /**
     * Halaman Detail Berita / Catatan Sekolah
     */
    public function show(string $slug)
    {
        $berita = Berita::where('slug', $slug)
            ->where('tampil_di_web', true)
            ->with(['user', 'academicYear'])
            ->firstOrFail();

        $berita->increment('dibaca');

        $beritaLainnya = Berita::where('tampil_di_web', true)
            ->where('id', '!=', $berita->id)
            ->orderBy('tanggal', 'desc')
            ->take(4)
            ->get();

        return view('berita.show', compact('berita', 'beritaLainnya'));
    }

    /**
     * Simpan Permohonan Informasi / Pengaduan Baru dari Landing Page
     */
    public function storePermohonan(Request $request)
    {
        $validated = $request->validate([
            'nama_lengkap' => 'required|string|max:100',
            'kontak' => 'required|string|max:100',
            'subjek' => 'required|string|max:150',
            'pesan' => 'required|string|max:3000',
        ], [
            'nama_lengkap.required' => 'Nama lengkap wajib diisi.',
            'kontak.required' => 'Nomor WhatsApp atau Email wajib diisi.',
            'subjek.required' => 'Keperluan atau subjek wajib diisi.',
            'pesan.required' => 'Isi pesan permohonan wajib diisi.',
        ]);

        $permohonan = PermohonanInformasi::create($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Permohonan informasi Anda berhasil kami terima.',
            'nomor_laporan' => $permohonan->nomor_laporan,
            'data' => [
                'nomor_laporan' => $permohonan->nomor_laporan,
                'nama_lengkap' => $permohonan->nama_lengkap,
                'subjek' => $permohonan->subjek,
                'status' => $permohonan->status,
                'status_label' => $permohonan->status_label,
                'created_at' => $permohonan->created_at?->format('d M Y, H:i') . ' WIB',
            ],
        ]);
    }

    /**
     * Cek Status Permohonan Informasi Berdasarkan Nomor Laporan
     */
    public function cekPermohonan(Request $request)
    {
        $request->validate([
            'nomor_laporan' => 'required|string|max:50',
        ], [
            'nomor_laporan.required' => 'Silakan masukkan nomor laporan.',
        ]);

        $nomor = trim(strtoupper($request->input('nomor_laporan')));
        $permohonan = PermohonanInformasi::where('nomor_laporan', $nomor)->with('petugas')->first();

        if (!$permohonan) {
            return response()->json([
                'status' => 'not_found',
                'message' => "Nomor laporan '{$nomor}' tidak ditemukan. Mohon pastikan nomor tiket laporan yang Anda masukkan sudah benar.",
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'nomor_laporan' => $permohonan->nomor_laporan,
                'nama_lengkap' => $permohonan->nama_lengkap,
                'kontak' => $permohonan->kontak,
                'subjek' => $permohonan->subjek,
                'pesan' => $permohonan->pesan,
                'status' => $permohonan->status,
                'status_label' => $permohonan->status_label,
                'status_badge_class' => $permohonan->status_badge_class,
                'tanggal_kirim' => $permohonan->created_at?->format('d M Y, H:i') . ' WIB',
                'tanggapan' => $permohonan->tanggapan,
                'tanggal_tanggapan' => $permohonan->tanggal_tanggapan ? $permohonan->tanggal_tanggapan->format('d M Y, H:i') . ' WIB' : null,
                'petugas' => $permohonan->petugas?->nama ?? 'Tim Informasi & Pelayanan SMPN 3 Ngawen',
            ],
        ]);
    }
}
