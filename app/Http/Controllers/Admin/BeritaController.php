<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Berita;
use App\Services\ImageOptimizerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class BeritaController extends Controller
{
    public function index(Request $request)
    {
        $query = Berita::with(['user', 'academicYear']);

        if ($request->filled('q')) {
            $q = $request->input('q');
            $query->where(function ($sq) use ($q) {
                $sq->where('judul', 'LIKE', "%$q%")
                   ->orWhere('konten', 'LIKE', "%$q%")
                   ->orWhereHas('user', function ($uq) use ($q) {
                       $uq->where('nama_lengkap', 'LIKE', "%$q%");
                   });
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

        if ($request->filled('tampil_di_web')) {
            $query->where('tampil_di_web', $request->input('tampil_di_web') === '1');
        }

        $beritas = $query->orderBy('tanggal', 'desc')->orderBy('id', 'desc')->paginate(15)->withQueryString();
        $academicYears = AcademicYear::orderBy('nama', 'desc')->get();
        $activeYear = AcademicYear::getActive();

        $totalBerita = Berita::count();
        $totalTampilWeb = Berita::where('tampil_di_web', true)->count();
        $totalBukuHarian = Berita::where('kategori', 'buku_harian')->count();

        return view('admin.berita.index', compact(
            'beritas',
            'academicYears',
            'activeYear',
            'totalBerita',
            'totalTampilWeb',
            'totalBukuHarian'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'judul' => 'required|string|max:255',
            'kategori' => 'required|string|max:50',
            'konten' => 'required|string',
            'tanggal' => 'required|date',
            'academic_year_id' => 'nullable|exists:academic_years,id',
            'gambar' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:3072',
            'tampil_di_web' => 'nullable|boolean',
        ]);

        $activeYear = AcademicYear::getActive();
        $academicYearId = $request->input('academic_year_id') ?: ($activeYear?->id);

        $gambarPath = null;
        if ($request->hasFile('gambar')) {
            $dest = public_path('uploads/berita');
            $filename = ImageOptimizerService::uploadAndConvertToWebp($request->file('gambar'), $dest, 1600, 82, 'berita_');
            $gambarPath = 'uploads/berita/' . $filename;
        }

        $berita = Berita::create([
            'academic_year_id' => $academicYearId,
            'user_id' => auth()->id(),
            'judul' => trim($request->input('judul')),
            'kategori' => $request->input('kategori'),
            'ringkasan' => $request->input('ringkasan') ?: Str::limit(strip_tags($request->input('konten')), 150),
            'konten' => $request->input('konten'),
            'gambar' => $gambarPath,
            'tanggal' => $request->input('tanggal'),
            'tampil_di_web' => $request->boolean('tampil_di_web'),
        ]);

        return back()->with('success', "Berita / Catatan '{$berita->judul}' berhasil diterbitkan.");
    }

    public function update(Request $request, Berita $berita)
    {
        $request->validate([
            'judul' => 'required|string|max:255',
            'kategori' => 'required|string|max:50',
            'konten' => 'required|string',
            'tanggal' => 'required|date',
            'academic_year_id' => 'nullable|exists:academic_years,id',
            'gambar' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
        ]);

        $gambarPath = $berita->gambar;
        if ($request->hasFile('gambar')) {
            // Hapus file lama jika ada
            if ($berita->gambar && File::exists(public_path($berita->gambar))) {
                File::delete(public_path($berita->gambar));
            }

            $dest = public_path('uploads/berita');
            $filename = ImageOptimizerService::uploadAndConvertToWebp($request->file('gambar'), $dest, 1600, 82, 'berita_');
            $gambarPath = 'uploads/berita/' . $filename;
        }

        $berita->update([
            'judul' => trim($request->input('judul')),
            'kategori' => $request->input('kategori'),
            'ringkasan' => $request->input('ringkasan') ?: Str::limit(strip_tags($request->input('konten')), 150),
            'konten' => $request->input('konten'),
            'gambar' => $gambarPath,
            'tanggal' => $request->input('tanggal'),
            'academic_year_id' => $request->input('academic_year_id') ?: $berita->academic_year_id,
            'tampil_di_web' => $request->boolean('tampil_di_web'),
        ]);

        return back()->with('success', "Data berita '{$berita->judul}' berhasil diperbarui.");
    }

    public function toggleWeb(Berita $berita)
    {
        $newStatus = !$berita->tampil_di_web;
        $berita->update(['tampil_di_web' => $newStatus]);

        $msg = $newStatus ? "Berita sekarang TAMPIL di website utama." : "Berita DIHAPUS dari tampilan website utama (arsip internal).";
        return back()->with('success', $msg);
    }

    public function destroy(Berita $berita)
    {
        if ($berita->gambar && File::exists(public_path($berita->gambar))) {
            File::delete(public_path($berita->gambar));
        }

        $judul = $berita->judul;
        $berita->delete();

        return back()->with('success', "Berita '{$judul}' berhasil dihapus.");
    }
}

