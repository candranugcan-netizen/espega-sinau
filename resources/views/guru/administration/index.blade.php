@extends('layouts.guru')

@section('title', 'Administrasi Guru - Espega Sinau')
@section('page_title', 'Administrasi Guru')

@section('content')
<div class="space-y-5 sm:space-y-6">
    <div>
        <h2 class="text-lg sm:text-xl font-bold text-slate-900">Arsip Administrasi</h2>
        <p class="mt-1 text-sm leading-relaxed text-slate-500">Simpan dan temukan kembali dokumen administrasi pribadi Anda dengan mudah.</p>
    </div>

    <div class="grid gap-4 lg:grid-cols-5 sm:gap-6">
        <section class="lg:col-span-2 rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
            <div class="mb-4 flex items-center gap-3">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-cyan-50 text-cyan-700">
                    <i class="fa-solid fa-cloud-arrow-up"></i>
                </span>
                <div>
                    <h3 class="font-bold text-slate-900">Unggah dokumen</h3>
                    <p class="text-xs text-slate-500">Maksimal 20 MB per file</p>
                </div>
            </div>

            <form action="{{ route('guru.administration.store') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                @csrf

                <div>
                    <label for="title" class="mb-1.5 block text-xs font-bold text-slate-700">Nama dokumen</label>
                    <input id="title" name="title" type="text" value="{{ old('title') }}" required maxlength="150" placeholder="Contoh: SK Pengangkatan" class="min-h-11 w-full rounded-xl border border-slate-200 px-3.5 py-2 text-sm focus:border-cyan-500 focus:outline-none focus:ring-2 focus:ring-cyan-500/20">
                    @error('title')
                        <p class="mt-1.5 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="category" class="mb-1.5 block text-xs font-bold text-slate-700">Kategori <span class="font-normal text-slate-400">(opsional)</span></label>
                    <input id="category" name="category" type="text" value="{{ old('category') }}" maxlength="100" placeholder="Contoh: Kepegawaian, Sertifikat" class="min-h-11 w-full rounded-xl border border-slate-200 px-3.5 py-2 text-sm focus:border-cyan-500 focus:outline-none focus:ring-2 focus:ring-cyan-500/20">
                    @error('category')
                        <p class="mt-1.5 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="description" class="mb-1.5 block text-xs font-bold text-slate-700">Catatan <span class="font-normal text-slate-400">(opsional)</span></label>
                    <textarea id="description" name="description" rows="3" maxlength="1000" placeholder="Tambahkan keterangan singkat" class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm focus:border-cyan-500 focus:outline-none focus:ring-2 focus:ring-cyan-500/20">{{ old('description') }}</textarea>
                    @error('description')
                        <p class="mt-1.5 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="file" class="mb-1.5 block text-xs font-bold text-slate-700">Pilih file</label>
                    <input id="file" name="file" type="file" required accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.jpg,.jpeg,.png" class="block min-h-11 w-full rounded-xl border border-slate-200 text-sm text-slate-600 file:mr-3 file:min-h-11 file:border-0 file:bg-slate-50 file:px-3 file:text-xs file:font-bold file:text-slate-700">
                    <p class="mt-1.5 text-[11px] leading-relaxed text-slate-400">PDF, Word, Excel, PowerPoint, JPG, atau PNG.</p>
                    @error('file')
                        <p class="mt-1.5 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit" class="inline-flex min-h-11 w-full items-center justify-center gap-2 rounded-xl bg-cyan-700 px-4 py-2.5 text-sm font-bold text-white transition-colors hover:bg-cyan-800 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:ring-offset-2">
                    <i class="fa-solid fa-upload"></i>
                    <span>Simpan ke Arsip</span>
                </button>
            </form>
        </section>

        <section class="min-w-0 lg:col-span-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
            <div class="mb-4 flex flex-col gap-3 border-b border-slate-100 pb-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h3 class="font-bold text-slate-900">Dokumen tersimpan</h3>
                    <p class="mt-0.5 text-xs text-slate-500">Menampilkan {{ $documents->firstItem() ?? 0 }}-{{ $documents->lastItem() ?? 0 }} dari {{ number_format($documents->total()) }} file</p>
                </div>
                <div class="flex flex-col gap-2 sm:w-full sm:max-w-xl">
                    <form action="{{ route('guru.administration.index') }}" method="GET" class="flex min-w-0 flex-col gap-2 sm:flex-row">
                        <input type="search" name="q" value="{{ request('q') }}" placeholder="Cari dokumen..." aria-label="Cari dokumen" class="min-h-10 min-w-0 flex-1 rounded-xl border border-slate-200 px-3 text-sm focus:border-cyan-500 focus:outline-none focus:ring-2 focus:ring-cyan-500/20 sm:w-40">
                        <select name="category" aria-label="Filter kategori" class="min-h-10 min-w-0 rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-700 focus:border-cyan-500 focus:outline-none focus:ring-2 focus:ring-cyan-500/20 sm:w-40">
                            <option value="">Semua kategori</option>
                            <option value="__uncategorized" {{ request('category') === '__uncategorized' ? 'selected' : '' }}>Tanpa kategori</option>
                            @foreach($categories as $category)
                                <option value="{{ $category }}" {{ request('category') === $category ? 'selected' : '' }}>{{ $category }}</option>
                            @endforeach
                        </select>
                        <button type="submit" class="inline-flex min-h-10 shrink-0 items-center justify-center gap-1.5 rounded-xl bg-slate-900 px-3 text-xs font-bold text-white transition-colors hover:bg-slate-700">
                            <i class="fa-solid fa-filter"></i>
                            <span>Filter</span>
                        </button>
                    </form>
                    @if(request()->filled('q') || request()->filled('category'))
                        <a href="{{ route('guru.administration.index') }}" class="self-end text-xs font-semibold text-slate-500 hover:text-cyan-700">Reset filter</a>
                    @endif
                </div>
            </div>

            <div class="divide-y divide-slate-100">
                @forelse($documents as $document)
                    <article class="flex flex-col gap-3 py-4 first:pt-0 last:pb-0 sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex min-w-0 items-start gap-3">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-slate-600">
                                <i class="fa-regular fa-file-lines"></i>
                            </span>
                            <div class="min-w-0">
                                <h4 class="break-words text-sm font-bold text-slate-900">{{ $document->title }}</h4>
                                <p class="mt-0.5 break-all text-xs text-slate-500">{{ $document->original_name }}</p>
                                <div class="mt-2 flex flex-wrap items-center gap-x-2 gap-y-1 text-[11px] text-slate-400">
                                    @if($document->category)
                                        <span class="rounded-md bg-cyan-50 px-2 py-0.5 font-semibold text-cyan-800">{{ $document->category }}</span>
                                    @endif
                                    <span>{{ $document->created_at->format('d M Y') }}</span>
                                    <span>{{ $document->size_bytes >= 1048576 ? number_format($document->size_bytes / 1048576, 1).' MB' : number_format($document->size_bytes / 1024, 0).' KB' }}</span>
                                </div>
                                @if($document->description)
                                    <p class="mt-2 break-words text-xs leading-relaxed text-slate-500">{{ $document->description }}</p>
                                @endif
                            </div>
                        </div>

                        <div class="flex shrink-0 items-center gap-2 pl-[52px] sm:pl-0">
                            <a href="{{ route('guru.administration.download', $document) }}" class="inline-flex min-h-10 items-center justify-center gap-1.5 rounded-lg border border-slate-200 px-3 py-2 text-xs font-bold text-slate-700 transition-colors hover:bg-slate-50">
                                <i class="fa-solid fa-download text-cyan-700"></i>
                                <span>Unduh</span>
                            </a>
                            <form action="{{ route('guru.administration.destroy', $document) }}" method="POST" onsubmit="return confirm('Hapus dokumen ini dari arsip?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" aria-label="Hapus {{ $document->title }}" title="Hapus dokumen" class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-rose-200 text-rose-600 transition-colors hover:bg-rose-50">
                                    <i class="fa-solid fa-trash-can"></i>
                                </button>
                            </form>
                        </div>
                    </article>
                @empty
                    <div class="py-10 text-center">
                        <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-xl bg-slate-100 text-slate-400">
                            <i class="fa-regular fa-folder-open text-lg"></i>
                        </span>
                        <h4 class="mt-3 text-sm font-bold text-slate-700">Belum ada dokumen</h4>
                        <p class="mt-1 text-xs text-slate-500">Dokumen yang Anda simpan akan muncul di sini.</p>
                    </div>
                @endforelse
            </div>

            @if($documents->hasPages())
                <div class="mt-5 border-t border-slate-100 pt-4">
                    {{ $documents->links() }}
                </div>
            @endif
        </section>
    </div>
</div>
@endsection