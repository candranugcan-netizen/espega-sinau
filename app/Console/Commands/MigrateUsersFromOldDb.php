<?php

namespace App\Console\Commands;

use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class MigrateUsersFromOldDb extends Command
{
    protected $signature = 'espega:migrate-users {--file= : Path ke file SQL espega_db.sql}';
    protected $description = 'Migrasikan seluruh data pengguna (Admin, Guru, Siswa) dari database lama ke database baru';

    public function handle()
    {
        $this->info("=== Memulai Migrasi Users dari Database Lama ke Database Baru ===");

        // 1. Pastikan tabel roles terisi data default
        $this->ensureRolesExist();

        // 2. Cari file SQL database lama
        $filePath = $this->option('file') ?: $this->findSqlFile();

        if (!$filePath || !File::exists($filePath)) {
            $this->error("File espega_db.sql tidak ditemukan! Pastikan file berada di folder espega_old/ atau root proyek.");
            return Command::FAILURE;
        }

        $this->info("Membaca file database: $filePath");
        $content = File::get($filePath);

        // 3. Ekstrak blok INSERT INTO `users`
        $usersData = $this->extractUsersFromSql($content);

        if (empty($usersData)) {
            $this->error("Tidak ditemukan data pengguna di dalam file SQL.");
            return Command::FAILURE;
        }

        $this->info("Ditemukan " . count($usersData) . " akun pengguna di database lama.");

        // 4. Nonaktifkan foreign key checks sementara untuk menghindari kendala urutan
        $driver = DB::getDriverName();
        if ($driver === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        } elseif ($driver === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF;');
        }

        $migrated = 0;
        $adminCount = 0;
        $guruCount = 0;
        $siswaCount = 0;

        $this->output->progressStart(count($usersData));

        foreach ($usersData as $u) {
            $roleId = (int)($u['role_id'] ?? 3);
            if ($roleId === 1) $adminCount++;
            elseif ($roleId === 2) $guruCount++;
            else $siswaCount++;

            User::updateOrCreate(
                ['id' => $u['id']],
                [
                    'username' => $u['username'],
                    'name' => $u['nama_lengkap'] ?: $u['username'],
                    'nama_lengkap' => $u['nama_lengkap'],
                    'email' => null,
                    'password' => $u['password'],
                    'token' => $u['token'],
                    'kelas' => $u['kelas'],
                    'role_id' => $roleId,
                    'status_blokir' => (string)($u['status_blokir'] ?? '0') === '1' ? '1' : '0',
                ]
            );

            $migrated++;
            $this->output->progressAdvance();
        }

        $this->output->progressFinish();

        if ($driver === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        } elseif ($driver === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = ON;');
        }

        $this->newLine();
        $this->info("✅ Migrasi Users Berhasil!");

        $this->table(
            ['Kategori Akun', 'Jumlah Akun'],
            [
                ['Super Administrator', $adminCount],
                ['Guru Pengampu', $guruCount],
                ['Peserta Didik (Siswa)', $siswaCount],
                ['TOTAL USERS TERDATA', $migrated],
            ]
        );

        // Rekap kelas siswa
        $kelasBreakdown = User::where('role_id', 3)
            ->select('kelas', DB::raw('count(*) as total'))
            ->groupBy('kelas')
            ->orderBy('kelas')
            ->get();

        $rows = [];
        foreach ($kelasBreakdown as $kb) {
            $rows[] = ['Kelas ' . ($kb->kelas ?: 'Belum Ada'), $kb->total . ' Siswa'];
        }
        if (!empty($rows)) {
            $this->info("Rincian Siswa per Rombongan Belajar (Rombel):");
            $this->table(['Rombel Kelas', 'Jumlah Siswa'], $rows);
        }

        return Command::SUCCESS;
    }

    protected function ensureRolesExist(): void
    {
        $roles = [
            1 => 'admin',
            2 => 'guru',
            3 => 'siswa',
        ];

        foreach ($roles as $id => $name) {
            Role::updateOrCreate(
                ['id' => $id],
                ['nama_role' => $name]
            );
        }
    }

    protected function findSqlFile(): ?string
    {
        $candidates = [
            base_path('espega_old/espega_db.sql'),
            base_path('espega_db.sql'),
            base_path('database/espega_db.sql'),
        ];

        foreach ($candidates as $cand) {
            if (File::exists($cand)) {
                return $cand;
            }
        }

        return null;
    }

    protected function extractUsersFromSql(string $content): array
    {
        $users = [];

        // Match pattern INSERT INTO `users` ... VALUES (...);
        if (preg_match('/INSERT\s+INTO\s+[`"]?users[`"]?\s*\(([^)]+)\)\s*VALUES\s*(.*?);/is', $content, $matches)) {
            $columnsStr = $matches[1];
            $valuesStr = $matches[2];

            // Parse columns
            $columns = array_map(function ($col) {
                return trim($col, " `\"'\t\n\r");
            }, explode(',', $columnsStr));

            // Parse rows: e.g. (1, 'admin', 'admin123', '1234', 'Administrator Utama', NULL, 1, '0'), ...
            // Regex to match individual tuples
            preg_match_all('/\((.*?)\)(?:,\s*|\s*$)/s', $valuesStr, $rowMatches);

            foreach ($rowMatches[1] as $rowString) {
                $values = str_getcsv($rowString, ',', "'", "\\");
                $userData = [];

                foreach ($columns as $idx => $colName) {
                    $val = isset($values[$idx]) ? trim($values[$idx]) : null;
                    if ($val === 'NULL' || $val === 'null' || $val === '') {
                        $val = null;
                    }
                    $userData[$colName] = $val;
                }

                if (!empty($userData['id']) || !empty($userData['username'])) {
                    $users[] = $userData;
                }
            }
        }

        return $users;
    }
}

