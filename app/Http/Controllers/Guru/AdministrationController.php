<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\GuruAdministration;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AdministrationController extends Controller
{
    public function index(Request $request): View
    {
        $query = $request->user()->guruAdministrations();
        $search = trim((string) $request->query('q', ''));
        $category = trim((string) $request->query('category', ''));

        if ($search !== '') {
            $query->where(function (Builder $query) use ($search): void {
                $query->where('title', 'like', "%{$search}%")
                    ->orWhere('category', 'like', "%{$search}%")
                    ->orWhere('original_name', 'like', "%{$search}%");
            });
        }

        if ($category === '__uncategorized') {
            $query->where(function (Builder $query): void {
                $query->whereNull('category')->orWhere('category', '');
            });
        } elseif ($category !== '') {
            $query->where('category', $category);
        }

        $documents = $query->latest()->orderByDesc('id')->paginate(12)->withQueryString();
        $categories = $request->user()->guruAdministrations()
            ->whereNotNull('category')
            ->where('category', '<>', '')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');

        return view('guru.administration.index', compact('documents', 'categories'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'category' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:1000'],
            'file' => [
                'required',
                'file',
                'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png',
                'extensions:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png',
                'max:20480',
            ],
        ], [
            'file.required' => 'Pilih dokumen yang akan diunggah.',
            'file.mimes' => 'Gunakan PDF, dokumen Office, JPG, atau PNG.',
            'file.extensions' => 'Ekstensi file tidak didukung.',
            'file.max' => 'Ukuran dokumen maksimal 20 MB.',
        ]);

        $file = $validated['file'];
        $user = $request->user();
        $directory = "guru-administrations/{$user->id}";
        $disk = Storage::disk('local');
        $baseName = implode('_', [
            Str::slug($user->nama) ?: 'guru',
            Str::slug($validated['title']) ?: 'dokumen',
            Str::slug($validated['category'] ?? 'tanpa-kategori') ?: 'tanpa-kategori',
            now()->format('Y-m-d'),
        ]);
        $extension = $file->extension();
        $filename = "{$baseName}.{$extension}";
        $suffix = 2;

        while ($disk->exists("{$directory}/{$filename}")) {
            $filename = "{$baseName}_{$suffix}.{$extension}";
            $suffix++;
        }

        $path = $file->storeAs($directory, $filename, 'local');

        if (! $path) {
            return back()->withInput()->withErrors(['file' => 'Dokumen gagal disimpan. Silakan coba lagi.']);
        }

        $user->guruAdministrations()->create([
            'title' => $validated['title'],
            'category' => $validated['category'] ?? null,
            'description' => $validated['description'] ?? null,
            'original_name' => $filename,
            'path' => $path,
            'mime_type' => (string) $file->getMimeType(),
            'size_bytes' => (int) $file->getSize(),
        ]);

        return redirect()->route('guru.administration.index')->with('success', 'Dokumen administrasi berhasil diunggah.');
    }

    public function download(Request $request, GuruAdministration $document): BinaryFileResponse
    {
        $document = $request->user()->guruAdministrations()->findOrFail($document->id);
        $disk = Storage::disk('local');

        abort_unless($disk->exists($document->path), 404);

        return response()->download($disk->path($document->path), $document->original_name, [
            'Content-Type' => $document->mime_type,
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function destroy(Request $request, GuruAdministration $document): RedirectResponse
    {
        $document = $request->user()->guruAdministrations()->findOrFail($document->id);

        Storage::disk('local')->delete($document->path);
        $document->delete();

        return redirect()->route('guru.administration.index')->with('success', 'Dokumen administrasi berhasil dihapus.');
    }
}
