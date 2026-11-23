<?php

namespace Tests\Feature;

use App\Models\StudentAttendance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use Tests\TestCase;

#[RequiresPhpExtension('pdo_sqlite')]
class StudentAttendanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_save_and_export_class_attendance(): void
    {
        $this->seedRoles();
        $admin = $this->createUser('admin-absensi', 1);
        $student = $this->createUser('siswa-absensi', 3, '7A');
        $date = '2026-11-20';

        $this->actingAs($admin)
            ->get(route('admin.attendance.index', ['kelas' => '7A', 'tanggal' => $date]))
            ->assertOk()
            ->assertSee('Daftar hadir')
            ->assertSee('save-attendance-bottom', false)
            ->assertSee('download-attendance-png', false);

        $this->post(route('admin.attendance.store'), [
            'kelas' => '7A',
            'tanggal' => $date,
            'students' => [
                $student->id => ['status' => 'hadir', 'catatan' => 'Tepat waktu'],
            ],
        ])->assertRedirect(route('admin.attendance.index', ['kelas' => '7A', 'tanggal' => $date]));

        $this->assertDatabaseHas('student_attendances', [
            'user_id' => $student->id,
            'recorded_by' => $admin->id,
            'tanggal' => $date,
            'kelas' => '7A',
            'status' => 'hadir',
            'catatan' => 'Tepat waktu',
        ]);

        $this->post(route('admin.attendance.store'), [
            'kelas' => '7A',
            'tanggal' => $date,
            'students' => [
                $student->id => ['status' => 'izin', 'catatan' => 'Surat diterima'],
            ],
        ])->assertRedirect(route('admin.attendance.index', ['kelas' => '7A', 'tanggal' => $date]));

        $this->assertDatabaseCount('student_attendances', 1);
        $this->assertDatabaseHas('student_attendances', [
            'user_id' => $student->id,
            'tanggal' => $date,
            'status' => 'izin',
            'catatan' => 'Surat diterima',
        ]);

        $pdfResponse = $this->actingAs($admin)
            ->get(route('admin.attendance.export_pdf', ['kelas' => '7A', 'tanggal' => $date]))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->assertStringStartsWith('%PDF', $pdfResponse->getContent());
    }

    public function test_attendance_rejects_invalid_status_without_saving(): void
    {
        $this->seedRoles();
        $admin = $this->createUser('admin-status', 1);
        $student = $this->createUser('siswa-status', 3, '7A');

        $this->actingAs($admin)
            ->from(route('admin.attendance.index', ['kelas' => '7A']))
            ->post(route('admin.attendance.store'), [
                'kelas' => '7A',
                'tanggal' => '2026-11-20',
                'students' => [
                    $student->id => ['status' => 'terlambat', 'catatan' => ''],
                ],
            ])
            ->assertSessionHasErrors('students.'.$student->id.'.status');

        $this->assertDatabaseCount('student_attendances', 0);
    }

    public function test_admin_cannot_save_attendance_for_student_in_another_class(): void
    {
        $this->seedRoles();
        $admin = $this->createUser('admin-kelas', 1);
        $this->createUser('siswa-kelas-7a', 3, '7A');
        $student = $this->createUser('siswa-kelas-7b', 3, '7B');

        $this->actingAs($admin)
            ->from(route('admin.attendance.index', ['kelas' => '7A']))
            ->post(route('admin.attendance.store'), [
                'kelas' => '7A',
                'tanggal' => '2026-11-20',
                'students' => [
                    $student->id => ['status' => 'hadir', 'catatan' => ''],
                ],
            ])
            ->assertSessionHasErrors('students');

        $this->assertDatabaseCount('student_attendances', 0);
    }

    public function test_monthly_recap_aggregates_only_selected_month_and_class(): void
    {
        $this->seedRoles();
        $admin = $this->createUser('admin-rekap', 1);
        $student = $this->createUser('siswa-rekap', 3, '7A');
        $classmate = $this->createUser('teman-rekap', 3, '7A');
        $otherClassStudent = $this->createUser('siswa-kelas-lain', 3, '7B');
        $this->createAttendance($admin, $student, '2026-11-02', 'hadir', '7A');
        $this->createAttendance($admin, $student, '2026-11-03', 'sakit', '7A');
        $this->createAttendance($admin, $classmate, '2026-11-03', 'alpa', '7A');
        $this->createAttendance($admin, $student, '2026-10-30', 'izin', '7A');
        $this->createAttendance($admin, $otherClassStudent, '2026-11-03', 'hadir', '7B');

        $this->actingAs($admin)
            ->get(route('admin.attendance.monthly', ['kelas' => '7A', 'periode' => '2026-11']))
            ->assertOk()
            ->assertSee('Rekap absensi bulanan')
            ->assertSee('siswa-rekap')
            ->assertSee('teman-rekap')
            ->assertDontSee('siswa-kelas-lain')
            ->assertSee('50.0%')
            ->assertSee('33.3%');

        $pdfResponse = $this->get(route('admin.attendance.monthly.export_pdf', [
            'kelas' => '7A',
            'periode' => '2026-11',
        ]))->assertOk()->assertHeader('Content-Type', 'application/pdf');

        $this->assertStringStartsWith('%PDF', $pdfResponse->getContent());
    }

    public function test_non_admin_cannot_open_attendance(): void
    {
        $this->seedRoles();
        $student = $this->createUser('siswa-terlarang', 3, '7A');

        $this->actingAs($student)
            ->get(route('admin.attendance.index'))
            ->assertForbidden();
    }

    private function seedRoles(): void
    {
        DB::table('roles')->insert([
            ['id' => 1, 'nama_role' => 'admin'],
            ['id' => 3, 'nama_role' => 'siswa'],
        ]);
    }

    private function createUser(string $username, int $roleId, ?string $className = null): User
    {
        return User::create([
            'username' => $username,
            'nama_lengkap' => $username,
            'kelas' => $className,
            'password' => 'password',
            'role_id' => $roleId,
            'status_blokir' => '0',
        ]);
    }

    private function createAttendance(User $admin, User $student, string $date, string $status, string $className): StudentAttendance
    {
        return StudentAttendance::create([
            'user_id' => $student->id,
            'recorded_by' => $admin->id,
            'tanggal' => $date,
            'kelas' => $className,
            'status' => $status,
        ]);
    }
}
