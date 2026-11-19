<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PermohonanInformasi;
use Illuminate\Http\Request;

class PermohonanInformasiController extends Controller
{
    /**
     * Tampilkan Daftar Permohonan Informasi & Pengaduan Masyarakat
     */
    public function index(Request $request)
    {
        $statusFilter = $request->query('status', 'all');
        $q = $request->query('q', '');

        $query = PermohonanInformasi::with('petugas')->latest('id');

        if ($statusFilter !== 'all' && in_array($statusFilter, ['pending', 'proses', 'selesai', 'ditolak'])) {
            $query->where('status', $statusFilter);
        }

        if (!empty($q)) {
            $query->where(function ($sub) use ($q) {
                $sub->where('nomor_laporan', 'LIKE', "%{$q}%")
                    ->orWhere('nama_lengkap', 'LIKE', "%{$q}%")
                    ->orWhere('kontak', 'LIKE', "%{$q}%")
                    ->orWhere('subjek', 'LIKE', "%{$q}%")
                    ->orWhere('pesan', 'LIKE', "%{$q}%");
            });
        }

        $permohonans = $query->paginate(15)->withQueryString();

        // Hitung statistik
        $counts = [
            'all' => PermohonanInformasi::count(),
            'pending' => PermohonanInformasi::where('status', 'pending')->count(),
            'proses' => PermohonanInformasi::where('status', 'proses')->count(),
            'selesai' => PermohonanInformasi::where('status', 'selesai')->count(),
            'ditolak' => PermohonanInformasi::where('status', 'ditolak')->count(),
        ];

        return view('admin.permohonan.index', compact('permohonans', 'counts', 'statusFilter', 'q'));
    }

    /**
     * Berikan / Perbarui Tanggapan dan Status Permohonan
     */
    public function update(Request $request, PermohonanInformasi $permohonan)
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,proses,selesai,ditolak',
            'tanggapan' => 'nullable|string|max:5000',
        ], [
            'status.required' => 'Status wajib dipilih.',
            'status.in' => 'Status tidak valid.',
        ]);

        $updateData = [
            'status' => $validated['status'],
            'tanggapan' => $validated['tanggapan'],
        ];

        if ($request->filled('tanggapan') || in_array($validated['status'], ['selesai', 'proses'])) {
            $updateData['tanggal_tanggapan'] = now();
            $updateData['petugas_id'] = auth()->id();
        }

        $permohonan->update($updateData);

        return back()->with('success', "Tanggapan untuk laporan {$permohonan->nomor_laporan} berhasil disimpan.");
    }

    /**
     * Hapus Data Permohonan
     */
    public function destroy(PermohonanInformasi $permohonan)
    {
        $nomor = $permohonan->nomor_laporan;
        $permohonan->delete();

        return back()->with('success', "Permohonan dengan nomor {$nomor} berhasil dihapus.");
    }
}

