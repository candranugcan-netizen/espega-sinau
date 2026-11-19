<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->command->info('1. Migrasi Akun Pengguna (Users)...');
        Artisan::call('espega:migrate-users', [], $this->command->getOutput());

        $this->command->info('2. Migrasi Master Data & Modul Ujian (Subjects, Bank Soal, Exams)...');
        Artisan::call('espega:import-db', [], $this->command->getOutput());
    }
}
