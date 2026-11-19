<?php

namespace App\Http\Controllers\Guru;

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
        $guruId = auth()->id();
        $query = Berita::where('user_id', $guruId)->with('academicYear');

        if ($request->filled('q')) {
            $q = $request->input('q');
            $query->where(function ($sq) use ($q) {
                $sq->where('judul', 'LIKE', "%$q%")
                   ->orWhere('konten', 'LIKE', "%$q%");
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

        $beritas = $query->orderBy('tanggal', 'desc')->orderBy('id', 'desc')->paginate(12)->withQueryString();
        $academicYears = AcademicYear::orderBy('nama', 'desc')->get();
        $activeYear = AcademicYear::getActive();

        $totalJurnal = Berita::where('user_id', $guruId)->count();
        $totalWeb = Berita::where('user_id', $guruId)->where('tampil_di_web', true)->count();
        $totalPrivate = Berita::where('user_id', $guruId)->where('tampil_di_web', false)->count();

        return view('guru.berita.index', compact(
            'beritas',
            'academicYears',
            'activeYear',
            'totalJurnal',
            'totalWeb',
            'totalPrivate'
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
        ]);

        $activeYear = AcademicYear::getActive();
        $academicYearId = $request->input('academic_year_id') ?: ($activeYear?->id);

        $gambarPath = null;
        if ($request->hasFile('gambar')) {
            $dest = public_path('uploads/berita');
            $filename = ImageOptimizerService::uploadAndConvertToWebp($request->file('gambar'), $dest, 1600, 82, 'jurnal_');
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

        return back()->with('success', "Catatan / Buku harian '{$berita->judul}' berhasil disimpan.");
    }

    public function update(Request $request, Berita $berita)
    {
        if ($berita->user_id !== auth()->id() && !auth()->user()->isAdmin()) {
            abort(403);
        }

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
            if ($berita->gambar && File::exists(public_path($berita->gambar))) {
                File::delete(public_path($berita->gambar));
            }

            $dest = public_path('uploads/berita');
            $filename = ImageOptimizerService::uploadAndConvertToWebp($request->file('gambar'), $dest, 1600, 82, 'jurnal_');
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

        return back()->with('success', "Data jurnal berhasil diperbarui.");
    }

    public function toggleWeb(Berita $berita)
    {
        if ($berita->user_id !== auth()->id() && !auth()->user()->isAdmin()) {
            abort(403);
        }

        $newStatus = !$berita->tampil_di_web;
        $berita->update(['tampil_di_web' => $newStatus]);

        $msg = $newStatus ? "Catatan ini sekarang DITAMPILKAN di website utama sekolah." : "Catatan ini sekarang DIUBAH menjadi catatan internal (tidak tampil di website).";
        return back()->with('success', $msg);
    }

    public function destroy(Berita $berita)
    {
        if ($berita->user_id !== auth()->id() && !auth()->user()->isAdmin()) {
            abort(403);
        }

        if ($berita->gambar && File::exists(public_path($berita->gambar))) {
            File::delete(public_path($berita->gambar));
        }

        $berita->delete();

        return back()->with('success', "Catatan berhasil dihapus.");
    }
}

