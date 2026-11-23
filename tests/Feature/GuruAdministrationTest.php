<?php

namespace Tests\Feature;

use App\Models\GuruAdministration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use Tests\TestCase;

#[RequiresPhpExtension('pdo_sqlite')]
class GuruAdministrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_can_upload_a_document_to_their_private_archive(): void
    {
        Storage::fake('local');
        $this->seedRoles();
        $teacher = $this->createUser('guru-arsip', 2);
        $this->travelTo('2026-11-23 09:30:00');

        $this->actingAs($teacher)
            ->post(route('guru.administration.store'), [
                'title' => 'SK Pengangkatan',
                'category' => 'Kepegawaian',
                'description' => 'Dokumen kepegawaian',
                'file' => UploadedFile::fake()->create('sk-pengangkatan.pdf', 12, 'application/pdf'),
            ])
            ->assertRedirect(route('guru.administration.index'));

        $this->actingAs($teacher)
            ->post(route('guru.administration.store'), [
                'title' => 'SK Pengangkatan',
                'category' => 'Kepegawaian',
                'description' => 'Dokumen kepegawaian',
                'file' => UploadedFile::fake()->create('sk-pengangkatan.pdf', 12, 'application/pdf'),
            ])
            ->assertRedirect(route('guru.administration.index'));

        $document = $teacher->guruAdministrations()->firstOrFail();
        $documents = $teacher->guruAdministrations()->orderBy('id')->get();

        $this->assertDatabaseHas('guru_administrations', [
            'id' => $document->id,
            'user_id' => $teacher->id,
            'title' => 'SK Pengangkatan',
            'original_name' => 'guru-arsip_sk-pengangkatan_kepegawaian_2026-11-23.pdf',
        ]);
        $this->assertStringStartsWith("guru-administrations/{$teacher->id}/", $document->path);
        $this->assertSame([
            'guru-arsip_sk-pengangkatan_kepegawaian_2026-11-23.pdf',
            'guru-arsip_sk-pengangkatan_kepegawaian_2026-11-23_2.pdf',
        ], $documents->pluck('original_name')->all());

        foreach ($documents as $uploadedDocument) {
            $this->assertTrue(Storage::disk('local')->exists($uploadedDocument->path));
        }
    }

    public function test_teacher_search_only_returns_matching_documents_from_their_archive(): void
    {
        $this->seedRoles();
        $teacher = $this->createUser('guru-cari', 2);
        $otherTeacher = $this->createUser('guru-lain-cari', 2);
        $this->createDocument($teacher, 'Sertifikat Pembelajaran', 'sertifikat.pdf');
        $this->createDocument($teacher, 'Surat Tugas', 'surat-tugas.pdf');
        $this->createDocument($otherTeacher, 'Sertifikat Rahasia', 'rahasia.pdf');

        $this->actingAs($teacher)
            ->get(route('guru.administration.index', ['q' => 'Sertifikat']))
            ->assertOk()
            ->assertSee('Sertifikat Pembelajaran')
            ->assertDontSee('Surat Tugas')
            ->assertDontSee('Sertifikat Rahasia');
    }

    public function test_category_filter_and_pagination_keep_only_matching_teacher_documents(): void
    {
        $this->seedRoles();
        $teacher = $this->createUser('guru-filter', 2);
        $otherTeacher = $this->createUser('guru-filter-lain', 2);

        for ($number = 1; $number <= 13; $number++) {
            $this->createDocument($teacher, sprintf('Arsip %02d', $number), "arsip-{$number}.pdf", 'Kepegawaian');
        }

        $this->createDocument($teacher, 'Dokumen Pelatihan', 'pelatihan.pdf', 'Pelatihan');
        $this->createDocument($otherTeacher, 'Arsip Rahasia', 'rahasia.pdf', 'Kepegawaian');

        $firstPage = $this->actingAs($teacher)
            ->get(route('guru.administration.index', ['category' => 'Kepegawaian']))
            ->assertOk()
            ->assertSee('Arsip 13')
            ->assertSee('Arsip 02')
            ->assertDontSee('Arsip 01')
            ->assertDontSee('Dokumen Pelatihan')
            ->assertDontSee('Arsip Rahasia');

        $documents = $firstPage->viewData('documents');
        $this->assertSame(13, $documents->total());
        $this->assertStringContainsString('category=Kepegawaian', $documents->nextPageUrl());

        $this->get(route('guru.administration.index', ['category' => 'Kepegawaian', 'page' => 2]))
            ->assertOk()
            ->assertSee('Arsip 01')
            ->assertDontSee('Arsip 02')
            ->assertDontSee('Dokumen Pelatihan');
    }

    public function test_teacher_can_download_their_own_document(): void
    {
        Storage::fake('local');
        $this->seedRoles();
        $teacher = $this->createUser('guru-unduh', 2);
        $document = $this->createDocument($teacher, 'Arsip PDF', 'arsip.pdf');
        Storage::disk('local')->put($document->path, '%PDF-test');

        $this->actingAs($teacher)
            ->get(route('guru.administration.download', $document))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('Content-Disposition', 'attachment; filename=arsip.pdf');
    }

    public function test_teacher_cannot_download_another_teachers_document(): void
    {
        Storage::fake('local');
        $this->seedRoles();
        $owner = $this->createUser('guru-pemilik', 2);
        $otherTeacher = $this->createUser('guru-bukan-pemilik', 2);
        $document = $this->createDocument($owner, 'Dokumen Pribadi', 'pribadi.pdf');
        Storage::disk('local')->put($document->path, '%PDF-test');

        $this->actingAs($otherTeacher)
            ->get(route('guru.administration.download', $document))
            ->assertNotFound();
    }

    public function test_teacher_cannot_delete_another_teachers_document(): void
    {
        Storage::fake('local');
        $this->seedRoles();
        $owner = $this->createUser('guru-pemilik-hapus', 2);
        $otherTeacher = $this->createUser('guru-lain-hapus', 2);
        $document = $this->createDocument($owner, 'Dokumen Terjaga', 'terjaga.pdf');
        Storage::disk('local')->put($document->path, '%PDF-test');

        $this->actingAs($otherTeacher)
            ->delete(route('guru.administration.destroy', $document))
            ->assertNotFound();

        $this->assertModelExists($document);
        $this->assertTrue(Storage::disk('local')->exists($document->path));
    }

    public function test_teacher_can_delete_their_own_document(): void
    {
        Storage::fake('local');
        $this->seedRoles();
        $teacher = $this->createUser('guru-hapus', 2);
        $document = $this->createDocument($teacher, 'Dokumen Lama', 'lama.pdf');
        Storage::disk('local')->put($document->path, '%PDF-test');

        $this->actingAs($teacher)
            ->delete(route('guru.administration.destroy', $document))
            ->assertRedirect(route('guru.administration.index'));

        $this->assertModelMissing($document);
        $this->assertFalse(Storage::disk('local')->exists($document->path));
    }

    public function test_upload_rejects_unsupported_file_types(): void
    {
        $this->seedRoles();
        $teacher = $this->createUser('guru-format', 2);

        $this->actingAs($teacher)
            ->from(route('guru.administration.index'))
            ->post(route('guru.administration.store'), [
                'title' => 'File Tidak Didukung',
                'file' => UploadedFile::fake()->create('catatan.txt', 10, 'text/plain'),
            ])
            ->assertSessionHasErrors([
                'file' => 'Gunakan PDF, dokumen Office, JPG, atau PNG.',
            ]);

        $this->assertDatabaseCount('guru_administrations', 0);
    }

    public function test_upload_rejects_files_larger_than_twenty_megabytes(): void
    {
        $this->seedRoles();
        $teacher = $this->createUser('guru-ukuran', 2);

        $this->actingAs($teacher)
            ->from(route('guru.administration.index'))
            ->post(route('guru.administration.store'), [
                'title' => 'File Terlalu Besar',
                'file' => UploadedFile::fake()->create('dokumen.pdf', 20481, 'application/pdf'),
            ])
            ->assertSessionHasErrors([
                'file' => 'Ukuran dokumen maksimal 20 MB.',
            ]);

        $this->assertDatabaseCount('guru_administrations', 0);
    }

    public function test_guest_is_redirected_from_guru_administration_to_login(): void
    {
        $this->get(route('guru.administration.index'))
            ->assertRedirect(route('login'));
    }

    public function test_admin_and_student_cannot_open_guru_administration(): void
    {
        $this->seedRoles();

        foreach ([1, 3] as $roleId) {
            $user = $this->createUser("pengguna-{$roleId}", $roleId);

            $this->actingAs($user)
                ->get(route('guru.administration.index'))
                ->assertForbidden();
        }
    }

    private function seedRoles(): void
    {
        DB::table('roles')->insert([
            ['id' => 1, 'nama_role' => 'admin'],
            ['id' => 2, 'nama_role' => 'guru'],
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

    private function createDocument(User $teacher, string $title, string $filename, string $category = 'Administrasi'): GuruAdministration
    {
        return $teacher->guruAdministrations()->create([
            'title' => $title,
            'category' => $category,
            'original_name' => $filename,
            'path' => "guru-administrations/{$teacher->id}/{$filename}",
            'mime_type' => 'application/pdf',
            'size_bytes' => 10,
        ]);
    }
}
