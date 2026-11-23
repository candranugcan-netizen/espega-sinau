<?php

namespace Tests\Feature;

use App\Models\StudentDocument;
use App\Models\StudentDocumentType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use Tests\TestCase;

#[RequiresPhpExtension('pdo_sqlite')]
class StudentAdministrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_save_extended_student_profile_and_add_document_type(): void
    {
        $this->seedRoles();
        $admin = $this->createUser('admin-1', 1);
        $student = $this->createUser('student-1', 3);
        $student->update(['nis' => 'nis-lama']);

        $this->actingAs($admin)
            ->get(route('admin.student_administration.show', $student))
            ->assertOk()
            ->assertSee('student-1')
            ->assertDontSee('nis-lama')
            ->assertDontSee('name="nis"');

        $this->actingAs($admin)
            ->put(route('admin.student_administration.update', $student), [
                'nama_lengkap' => 'Siswa Contoh',
                'username' => 'student-1',
                'nis' => 'nis-yang-tidak-digunakan',
                'kelas' => '7A',
                'nik' => '3300000000000001',
                'nama_ayah' => 'Budi',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('student_profiles', [
            'user_id' => $student->id,
            'nik' => '3300000000000001',
            'nama_ayah' => 'Budi',
        ]);
        $this->assertDatabaseHas('users', [
            'id' => $student->id,
            'username' => 'student-1',
            'nis' => 'nis-lama',
        ]);

        $this->post(route('admin.student_administration.document_types.store'), [
            'name' => 'Kartu Indonesia Pintar',
        ])->assertRedirect();

        $this->assertDatabaseHas('student_document_types', ['name' => 'Kartu Indonesia Pintar']);
    }

    public function test_admin_routes_are_forbidden_to_students(): void
    {
        $this->seedRoles();
        $student = $this->createUser('student-2', 3);

        $this->actingAs($student)
            ->get(route('admin.student_administration.index'))
            ->assertForbidden();
    }

    public function test_document_download_is_limited_to_its_student_record(): void
    {
        Storage::fake('local');
        $this->seedRoles();
        $admin = $this->createUser('admin-2', 1);
        $student = $this->createUser('student-3', 3);
        $otherStudent = $this->createUser('student-4', 3);
        $type = StudentDocumentType::create([
            'name' => 'Kartu Keluarga',
            'slug' => 'kk-test',
            'is_active' => true,
        ]);
        $path = "student-documents/{$student->id}/test.pdf";
        Storage::disk('local')->put($path, '%PDF-test');
        $document = StudentDocument::create([
            'user_id' => $student->id,
            'document_type_id' => $type->id,
            'original_name' => 'kk.pdf',
            'path' => $path,
            'mime_type' => 'application/pdf',
            'size_bytes' => 9,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.student_administration.documents.download', [$otherStudent, $document]))
            ->assertNotFound();
    }

    public function test_document_upload_rejects_files_larger_than_two_megabytes(): void
    {
        $this->seedRoles();
        $admin = $this->createUser('admin-upload', 1);
        $student = $this->createUser('student-upload', 3);
        $type = StudentDocumentType::create([
            'name' => 'Kartu Keluarga',
            'slug' => 'kk-upload-test',
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.student_administration.documents.store', $student), [
                'document_type_id' => $type->id,
                'file' => UploadedFile::fake()->create('document.pdf', 2049, 'application/pdf'),
            ])
            ->assertSessionHasErrors('file');

        $this->assertDatabaseCount('student_documents', 0);
    }

    public function test_admin_uploads_student_documents_with_unique_meaningful_filenames(): void
    {
        Storage::fake('local');
        $this->seedRoles();
        $admin = $this->createUser('admin-unique-document', 1);
        $student = $this->createUser('siswa-unique-document', 3);
        $student->update(['nama_lengkap' => 'Siswa Contoh']);
        $documentType = StudentDocumentType::create([
            'name' => 'Kartu Keluarga',
            'slug' => 'kk-unique-test',
            'is_active' => true,
        ]);
        $this->travelTo('2026-11-23 09:30:00');

        foreach (range(1, 2) as $uploadNumber) {
            $this->actingAs($admin)
                ->post(route('admin.student_administration.documents.store', $student), [
                    'document_type_id' => $documentType->id,
                    'file' => UploadedFile::fake()->create("kartu-keluarga-{$uploadNumber}.pdf", 12, 'application/pdf'),
                ])
                ->assertRedirect();
        }

        $documents = $student->studentDocuments()->orderBy('id')->get();

        $this->assertCount(2, $documents);
        $this->assertNotSame($documents[0]->original_name, $documents[1]->original_name);

        foreach ($documents as $document) {
            $this->assertMatchesRegularExpression(
                '/^siswa-contoh_kartu-keluarga_2026-11-23_[0-9a-f-]{36}\.pdf$/',
                $document->original_name
            );
            $this->assertSame(basename($document->path), $document->original_name);
            $this->assertTrue(Storage::disk('local')->exists($document->path));
        }

        $this->assertDatabaseCount('student_documents', 2);
    }

    private function seedRoles(): void
    {
        DB::table('roles')->insert([
            ['id' => 1, 'nama_role' => 'admin'],
            ['id' => 3, 'nama_role' => 'siswa'],
        ]);
    }

    private function createUser(string $username, int $roleId): User
    {
        return User::create([
            'username' => $username,
            'nama_lengkap' => $username,
            'password' => 'password',
            'role_id' => $roleId,
            'status_blokir' => '0',
        ]);
    }
}
