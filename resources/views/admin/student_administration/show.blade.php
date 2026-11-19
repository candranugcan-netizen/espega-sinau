@extends('layouts.admin')

@section('page_title', 'Administrasi Siswa')

@section('content')
<div class="space-y-5">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.student_administration.index') }}" class="flex h-9 w-9 items-center justify-center rounded-lg border border-slate-300 bg-white text-slate-600 hover:bg-slate-50" title="Kembali ke daftar">
                <i class="fa-solid fa-arrow-left"></i>
            </a>
            <div>
                <h2 class="text-xl font-bold text-slate-900">{{ $student->nama_lengkap }}</h2>
                <p class="mt-1 text-xs text-slate-500">{{ $student->username ?: 'Tanpa NIS' }} · {{ $student->kelas ?: 'Tanpa kelas' }}</p>
            </div>
        </div>
        <span class="inline-flex items-center gap-2 self-start rounded-full bg-emerald-50 px-3 py-1.5 text-xs font-bold text-emerald-800">
            <i class="fa-solid fa-id-card"></i> Data Induk
        </span>
    </div>

    @if(session('success'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
            <ul class="list-inside list-disc space-y-1">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="grid items-start gap-5 xl:grid-cols-[minmax(0,1.3fr)_minmax(340px,0.8fr)]">
        <form action="{{ route('admin.student_administration.update', $student) }}" method="POST" class="rounded-xl border border-slate-200 bg-white">
            @csrf
            @method('PUT')
            <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                <h3 class="text-sm font-bold text-slate-900">Data peserta didik</h3>
                <button class="inline-flex items-center gap-2 rounded-lg bg-brand-600 px-3.5 py-2 text-xs font-bold text-white hover:bg-brand-700">
                    <i class="fa-solid fa-floppy-disk"></i> Simpan
                </button>
            </div>
            <div class="space-y-6 p-5">
                <section>
                    <h4 class="mb-3 text-[11px] font-bold uppercase tracking-wide text-slate-500">Identitas siswa</h4>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <label class="text-xs font-semibold text-slate-700 sm:col-span-2">Nama lengkap
                            <input name="nama_lengkap" value="{{ old('nama_lengkap', $student->nama_lengkap) }}" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-normal focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-100">
                        </label>
                        <label class="text-xs font-semibold text-slate-700">Username / NIS
                            <input name="username" value="{{ old('username', $student->username) }}" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-normal focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-100">
                        </label>
                        <label class="text-xs font-semibold text-slate-700">NISN
                            <input name="nisn" value="{{ old('nisn', $student->nisn) }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-normal focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-100">
                        </label>
                        <label class="text-xs font-semibold text-slate-700">NIK
                            <input name="nik" value="{{ old('nik', $profile->nik) }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-normal focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-100">
                        </label>
                        <label class="text-xs font-semibold text-slate-700">Nomor Kartu Keluarga
                            <input name="nomor_kk" value="{{ old('nomor_kk', $profile->nomor_kk) }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-normal focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-100">
                        </label>
                        <label class="text-xs font-semibold text-slate-700">Kelas
                            <input name="kelas" value="{{ old('kelas', $student->kelas) }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-normal focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-100">
                        </label>
                        <label class="text-xs font-semibold text-slate-700">Jenis kelamin
                            <select name="jenis_kelamin" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-normal focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-100">
                                <option value="">Pilih</option><option value="L" @selected(old('jenis_kelamin', $student->jenis_kelamin) === 'L')>Laki-laki</option><option value="P" @selected(old('jenis_kelamin', $student->jenis_kelamin) === 'P')>Perempuan</option>
                            </select>
                        </label>
                        <label class="text-xs font-semibold text-slate-700">Telepon siswa
                            <input name="telepon" value="{{ old('telepon', $student->telepon) }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-normal focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-100">
                        </label>
                        <label class="text-xs font-semibold text-slate-700">Tempat lahir
                            <input name="tempat_lahir" value="{{ old('tempat_lahir', $profile->tempat_lahir) }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-normal focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-100">
                        </label>
                        <label class="text-xs font-semibold text-slate-700">Tanggal lahir
                            <input type="date" name="tanggal_lahir" value="{{ old('tanggal_lahir', $profile->tanggal_lahir?->format('Y-m-d')) }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-normal focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-100">
                        </label>
                        <label class="text-xs font-semibold text-slate-700">Agama
                            <input name="agama" value="{{ old('agama', $profile->agama) }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-normal focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-100">
                        </label>
                        <label class="text-xs font-semibold text-slate-700">Anak ke-
                            <input type="number" min="1" name="anak_ke" value="{{ old('anak_ke', $profile->anak_ke) }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-normal focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-100">
                        </label>
                        <label class="text-xs font-semibold text-slate-700">Jumlah saudara
                            <input type="number" min="0" name="jumlah_saudara" value="{{ old('jumlah_saudara', $profile->jumlah_saudara) }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-normal focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-100">
                        </label>
                        <label class="text-xs font-semibold text-slate-700 sm:col-span-2">Alamat sesuai dokumen
                            <textarea name="alamat_detail" rows="2" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-normal focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-100">{{ old('alamat_detail', $profile->alamat_detail ?: $student->alamat) }}</textarea>
                        </label>
                        <label class="text-xs font-semibold text-slate-700 sm:col-span-2">Alamat domisili
                            <textarea name="alamat" rows="2" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-normal focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-100">{{ old('alamat', $student->alamat) }}</textarea>
                        </label>
                        <label class="text-xs font-semibold text-slate-700">Kode pos
                            <input name="kode_pos" value="{{ old('kode_pos', $profile->kode_pos) }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-normal focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-100">
                        </label>
                        <label class="text-xs font-semibold text-slate-700">Asal sekolah
                            <input name="asal_sekolah" value="{{ old('asal_sekolah', $profile->asal_sekolah) }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-normal focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-100">
                        </label>
                        <label class="text-xs font-semibold text-slate-700">Tanggal masuk
                            <input type="date" name="tanggal_masuk" value="{{ old('tanggal_masuk', $profile->tanggal_masuk?->format('Y-m-d')) }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-normal focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-100">
                        </label>
                    </div>
                </section>

                <section class="border-t border-slate-200 pt-5">
                    <h4 class="mb-3 text-[11px] font-bold uppercase tracking-wide text-slate-500">Orang tua dan wali</h4>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <label class="text-xs font-semibold text-slate-700">Nama ayah<input name="nama_ayah" value="{{ old('nama_ayah', $profile->nama_ayah) }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-normal"></label>
                        <label class="text-xs font-semibold text-slate-700">Pekerjaan ayah<input name="pekerjaan_ayah" value="{{ old('pekerjaan_ayah', $profile->pekerjaan_ayah) }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-normal"></label>
                        <label class="text-xs font-semibold text-slate-700">Telepon ayah<input name="telepon_ayah" value="{{ old('telepon_ayah', $profile->telepon_ayah) }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-normal"></label>
                        <label class="text-xs font-semibold text-slate-700">Nama ibu<input name="nama_ibu" value="{{ old('nama_ibu', $profile->nama_ibu) }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-normal"></label>
                        <label class="text-xs font-semibold text-slate-700">Pekerjaan ibu<input name="pekerjaan_ibu" value="{{ old('pekerjaan_ibu', $profile->pekerjaan_ibu) }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-normal"></label>
                        <label class="text-xs font-semibold text-slate-700">Telepon ibu<input name="telepon_ibu" value="{{ old('telepon_ibu', $profile->telepon_ibu) }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-normal"></label>
                        <label class="text-xs font-semibold text-slate-700">Nama wali<input name="nama_wali" value="{{ old('nama_wali', $profile->nama_wali) }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-normal"></label>
                        <label class="text-xs font-semibold text-slate-700">Pekerjaan wali<input name="pekerjaan_wali" value="{{ old('pekerjaan_wali', $profile->pekerjaan_wali) }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-normal"></label>
                        <label class="text-xs font-semibold text-slate-700">Telepon wali<input name="telepon_wali" value="{{ old('telepon_wali', $profile->telepon_wali) }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-normal"></label>
                        <label class="text-xs font-semibold text-slate-700 sm:col-span-2">Alamat wali<textarea name="alamat_wali" rows="2" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-normal">{{ old('alamat_wali', $profile->alamat_wali) }}</textarea></label>
                        <label class="text-xs font-semibold text-slate-700 sm:col-span-2">Catatan administrasi<textarea name="catatan" rows="3" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-normal">{{ old('catatan', $profile->catatan) }}</textarea></label>
                    </div>
                </section>
            </div>
            <div class="border-t border-slate-200 px-5 py-4 text-right">
                <button class="inline-flex items-center gap-2 rounded-lg bg-brand-600 px-4 py-2 text-xs font-bold text-white hover:bg-brand-700"><i class="fa-solid fa-floppy-disk"></i> Simpan data</button>
            </div>
        </form>

        <div class="space-y-5">
            <section class="rounded-xl border border-slate-200 bg-white">
                <div class="border-b border-slate-200 px-5 py-4">
                    <h3 class="text-sm font-bold text-slate-900">Arsip dokumen</h3>
                    <p class="mt-1 text-xs text-slate-500">Gambar dikompres sebelum disimpan; PDF disimpan tanpa kompresi.</p>
                </div>
                <form action="{{ route('admin.student_administration.documents.store', $student) }}" method="POST" enctype="multipart/form-data" class="space-y-3 p-5">
                    @csrf
                    <label class="block text-xs font-semibold text-slate-700">Jenis dokumen
                        <select name="document_type_id" required class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm">
                            <option value="">Pilih jenis</option>
                            @foreach($documentTypes as $type)
                                <option value="{{ $type->id }}">{{ $type->name }}{{ $type->is_required ? ' · Wajib' : '' }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="block text-xs font-semibold text-slate-700">File
                        <input type="file" name="file" accept=".pdf,.jpg,.jpeg,.png,.webp" required class="mt-1 block w-full rounded-lg border border-slate-300 bg-white text-xs file:mr-3 file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-xs file:font-bold">
                        <span class="mt-1 block font-normal text-slate-500">PDF/JPG/JPEG/PNG/WebP, maksimal 2 MB.</span>
                    </label>
                    <button class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-slate-900 px-4 py-2.5 text-xs font-bold text-white hover:bg-slate-700"><i class="fa-solid fa-cloud-arrow-up"></i> Unggah dokumen</button>
                </form>

                <div class="border-t border-slate-200">
                    @forelse($student->studentDocuments as $document)
                        <div class="flex items-center gap-3 border-b border-slate-100 px-5 py-3 last:border-b-0">
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-sky-50 text-sky-700"><i class="fa-solid {{ $document->mime_type === 'application/pdf' ? 'fa-file-pdf' : 'fa-file-image' }}"></i></span>
                            <div class="min-w-0 flex-1">
                                <div class="truncate text-xs font-bold text-slate-800">{{ $document->documentType->name }}</div>
                                <div class="truncate text-[11px] text-slate-500">{{ $document->original_name }} · {{ number_format($document->size_bytes / 1024, 0) }} KB</div>
                            </div>
                            <a href="{{ route('admin.student_administration.documents.download', [$student, $document]) }}" class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-slate-500 hover:bg-slate-100 hover:text-brand-700" title="Unduh dokumen"><i class="fa-solid fa-download"></i></a>
                            <form action="{{ route('admin.student_administration.documents.destroy', [$student, $document]) }}" method="POST" onsubmit="return confirm('Hapus dokumen ini?')">
                                @csrf @method('DELETE')
                                <button class="flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 hover:bg-rose-50 hover:text-rose-600" title="Hapus dokumen"><i class="fa-solid fa-trash-can"></i></button>
                            </form>
                        </div>
                    @empty
                        <p class="px-5 py-6 text-center text-xs text-slate-500">Belum ada dokumen.</p>
                    @endforelse
                </div>
            </section>

            <section class="rounded-xl border border-slate-200 bg-white p-5">
                <h3 class="text-sm font-bold text-slate-900">Jenis dokumen</h3>
                <div class="mt-3 space-y-2">
                    @foreach($allDocumentTypes as $type)
                        <div class="flex items-center justify-between gap-3 text-xs">
                            <span class="text-slate-700">{{ $type->name }}</span>
                            <span class="text-slate-400">{{ $type->is_required ? 'Wajib' : 'Opsional' }}{{ $type->is_active ? '' : ' · Nonaktif' }}</span>
                        </div>
                    @endforeach
                </div>
                <form action="{{ route('admin.student_administration.document_types.store') }}" method="POST" class="mt-4 border-t border-slate-200 pt-4">
                    @csrf
                    <label class="block text-xs font-semibold text-slate-700">Tambah jenis
                        <input name="name" maxlength="100" required placeholder="Contoh: Kartu Indonesia Pintar" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-normal">
                    </label>
                    <label class="mt-3 flex items-center gap-2 text-xs text-slate-700"><input type="checkbox" name="is_required" value="1" class="rounded border-slate-300 text-brand-600"> Wajib dilengkapi</label>
                    <button class="mt-3 inline-flex items-center gap-2 rounded-lg border border-slate-300 px-3 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50"><i class="fa-solid fa-plus"></i> Tambah jenis</button>
                </form>
            </section>
        </div>
    </div>
</div>
@endsection