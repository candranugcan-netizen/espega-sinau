<?php

namespace App\Services;

use App\Models\StudentDocumentType;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class StudentDocumentOptimizer
{
    /**
     * @return array{path: string, mime_type: string, size_bytes: int, original_name: string}
     */
    public function store(UploadedFile $file, User $student, StudentDocumentType $documentType): array
    {
        $disk = Storage::disk('local');
        $directory = "student-documents/{$student->id}";
        $disk->makeDirectory($directory);
        $filenameBase = $this->filenameBase($student, $documentType);

        if (str_starts_with((string) $file->getMimeType(), 'image/')) {
            return $this->storeImage($file, $directory, $disk->path($directory), "{$filenameBase}_");
        }

        if ($file->getMimeType() === 'application/pdf') {
            return $this->storePdf($file, $directory, $filenameBase);
        }

        throw new RuntimeException('Format dokumen tidak didukung.');
    }

    private function storeImage(UploadedFile $file, string $directory, string $absoluteDirectory, string $filenamePrefix): array
    {
        $filename = ImageOptimizerService::uploadAndConvertToWebp(
            $file,
            $absoluteDirectory,
            maxDimension: 1600,
            quality: 82,
            prefix: $filenamePrefix
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

        return [
            'path' => $path,
            'mime_type' => $mimeType,
            'size_bytes' => (int) filesize($absolutePath),
            'original_name' => basename($path),
        ];
    }

    private function storePdf(UploadedFile $file, string $directory, string $filenameBase): array
    {
        $filename = $filenameBase.'_'.Str::uuid().'.pdf';
        $storedPath = $file->storeAs($directory, $filename, 'local');
        if (! $storedPath) {
            throw new RuntimeException('File PDF gagal disimpan.');
        }

        return [
            'path' => $storedPath,
            'mime_type' => 'application/pdf',
            'size_bytes' => Storage::disk('local')->size($storedPath),
            'original_name' => basename($storedPath),
        ];
    }

    private function filenameBase(User $student, StudentDocumentType $documentType): string
    {
        $studentName = Str::limit(Str::slug($student->nama_lengkap ?: $student->username ?: 'siswa'), 60, '');
        $documentTypeName = Str::limit(Str::slug($documentType->name), 60, '');

        return implode('_', [
            $studentName ?: 'siswa',
            $documentTypeName ?: 'dokumen',
            now()->format('Y-m-d'),
        ]);
    }
}
