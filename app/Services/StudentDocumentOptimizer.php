<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class StudentDocumentOptimizer
{
    public function store(UploadedFile $file, User $student): array
    {
        $disk = Storage::disk('local');
        $directory = "student-documents/{$student->id}";
        $disk->makeDirectory($directory);

        if (str_starts_with((string) $file->getMimeType(), 'image/')) {
            return $this->storeImage($file, $directory, $disk->path($directory));
        }

        if ($file->getMimeType() === 'application/pdf') {
            return $this->storePdf($file, $directory);
        }

        throw new RuntimeException('Format dokumen tidak didukung.');
    }

    private function storeImage(UploadedFile $file, string $directory, string $absoluteDirectory): array
    {
        $filename = ImageOptimizerService::uploadAndConvertToWebp(
            $file,
            $absoluteDirectory,
            maxDimension: 1600,
            quality: 82,
            prefix: 'document_'
        );
        $path = "{$directory}/{$filename}";
        $absolutePath = Storage::disk('local')->path($path);
        $mimeType = is_file($absolutePath)
            ? (new \finfo(FILEINFO_MIME_TYPE))->file($absolutePath)
            : false;

        if (! $mimeType || ! str_starts_with($mimeType, 'image/')) {
            Storage::disk('local')->delete($path);
            throw new RuntimeException('Gambar gagal disimpan.');
        }

        return ['path' => $path, 'mime_type' => $mimeType, 'size_bytes' => filesize($absolutePath)];
    }

    private function storePdf(UploadedFile $file, string $directory): array
    {
        $filename = Str::uuid().'.pdf';
        $storedPath = $file->storeAs($directory, $filename, 'local');
        if (! $storedPath) {
            throw new RuntimeException('File PDF gagal disimpan.');
        }

        return [
            'path' => $storedPath,
            'mime_type' => 'application/pdf',
            'size_bytes' => Storage::disk('local')->size($storedPath),
        ];
    }
}
