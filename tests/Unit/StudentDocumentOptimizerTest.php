<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\StudentDocumentOptimizer;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StudentDocumentOptimizerTest extends TestCase
{
    public function test_image_upload_is_saved_with_the_shared_image_optimizer(): void
    {
        Storage::fake('local');
        $student = new User;
        $student->setAttribute('id', 42);
        $file = UploadedFile::fake()->createWithContent(
            'document.png',
            base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/lS8AAAAASUVORK5CYII=')
        );

        $result = (new StudentDocumentOptimizer)->store($file, $student);

        $this->assertTrue(Storage::disk('local')->exists($result['path']));
        $this->assertStringStartsWith('image/', $result['mime_type']);
        $this->assertSame(Storage::disk('local')->size($result['path']), $result['size_bytes']);
    }

    public function test_pdf_upload_is_stored_without_ghostscript(): void
    {
        Storage::fake('local');
        config(['services.ghostscript.binary' => null]);

        $student = new User;
        $student->setAttribute('id', 42);
        $content = "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\n%%EOF";
        $file = UploadedFile::fake()->createWithContent('document.pdf', $content);

        $result = (new StudentDocumentOptimizer)->store($file, $student);

        $this->assertTrue(Storage::disk('local')->exists($result['path']));
        $this->assertSame($content, Storage::disk('local')->get($result['path']));
        $this->assertSame('application/pdf', $result['mime_type']);
    }
}
