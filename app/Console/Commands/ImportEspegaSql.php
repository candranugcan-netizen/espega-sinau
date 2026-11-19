<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class ImportEspegaSql extends Command
{
    protected $signature = 'espega:import-db {--file= : Path to sql file}';
    protected $description = 'Import legacy espega_db.sql into current database';

    public function handle()
    {
        $filePath = $this->option('file');

        if (!$filePath) {
            $candidates = [
                base_path('espega_old/espega_db.sql'),
                base_path('espega_db.sql'),
                base_path('database/espega_db.sql'),
            ];

            foreach ($candidates as $cand) {
                if (File::exists($cand)) {
                    $filePath = $cand;
                    break;
                }
            }
        }

        if (!$filePath || !File::exists($filePath)) {
            $this->error("File SQL tidak ditemukan! Pastikan file espega_db.sql ada di direktori proyek.");
            return Command::FAILURE;
        }

        $this->info("Membaca berkas SQL: $filePath ...");
        $content = File::get($filePath);

        $driver = DB::getDriverName();
        $this->info("Koneksi database aktif: $driver");

        // Disable foreign key checks during import
        if ($driver === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        } elseif ($driver === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF;');
        }

        // Split into statements
        $lines = explode("\n", $content);
        $buffer = '';
        $count = 0;
        $inInsert = false;

        $this->output->progressStart(count($lines));

        foreach ($lines as $line) {
            $trimmed = trim($line);
            $this->output->progressAdvance();

            // Ignore comments and empty lines
            if ($trimmed === '' || str_starts_with($trimmed, '--') || str_starts_with($trimmed, '/*') || str_starts_with($trimmed, '#')) {
                continue;
            }

            // We only need INSERT statements because tables are already created by Laravel migrations
            if (str_starts_with(strtoupper($trimmed), 'INSERT INTO')) {
                $inInsert = true;
                $buffer = $line;
            } elseif ($inInsert) {
                $buffer .= "\n" . $line;
            }

            if ($inInsert && str_ends_with(rtrim($trimmed), ';')) {
                try {
                    DB::unprepared($buffer);
                    $count++;
                } catch (\Throwable $e) {
                    $this->warn("Baris insert gagal (diabaikan jika duplikat): " . substr($e->getMessage(), 0, 100));
                }
                $buffer = '';
                $inInsert = false;
            }
        }

        $this->output->progressFinish();

        if ($driver === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        } elseif ($driver === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = ON;');
        }

        $this->info("Berhasil mengimpor $count blok data dari espega_db.sql!");
        return Command::SUCCESS;
    }
}

