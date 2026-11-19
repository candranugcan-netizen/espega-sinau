<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Menjalankan migrasi data pengguna dari database lama...');
        Artisan::call('espega:migrate-users', [], $this->command->getOutput());
    }
}

