<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class CopyLegacyAssets extends Command
{
    protected $signature = 'espega:copy-assets';
    protected $description = 'Salin aset gambar soal dan berkas library dari espega_old ke aplikasi baru';

    public function handle()
    {
        $sourceDir = base_path('espega_old/assets/img/soal');
        $destDir = public_path('assets/img/soal');

        if (File::isDirectory($sourceDir)) {
            if (!File::isDirectory($destDir)) {
                File::makeDirectory($destDir, 0755, true);
            }

            $files = File::files($sourceDir);
            $count = 0;

            foreach ($files as $file) {
                $targetPath = $destDir . DIRECTORY_SEPARATOR . $file->getFilename();
                if (!File::exists($targetPath)) {
                    File::copy($file->getPathname(), $targetPath);
                    $count++;
                }
            }
            $this->info("Berhasil menyalin $count berkas gambar soal ke public/assets/img/soal.");
        } else {
            $this->warn("Direktori sumber gambar $sourceDir tidak ditemukan.");
        }

        // Copy vendor libraries ke app/Services/Libraries/ jika belum ada
        $libDest = app_path('Services/Libraries');
        if (!File::isDirectory($libDest)) {
            File::makeDirectory($libDest, 0755, true);
        }

        $xlsxSource = base_path('espega_old/vendor/SimpleXLSX.php');
        if (File::exists($xlsxSource) && !File::exists($libDest . '/SimpleXLSX.php')) {
            File::copy($xlsxSource, $libDest . '/SimpleXLSX.php');
            $this->info("Disalin: SimpleXLSX.php ke app/Services/Libraries/");
        }

        $xlsxGenSource = base_path('espega_old/vendor/SimpleXLSXGen.php');
        if (File::exists($xlsxGenSource) && !File::exists($libDest . '/SimpleXLSXGen.php')) {
            File::copy($xlsxGenSource, $libDest . '/SimpleXLSXGen.php');
            $this->info("Disalin: SimpleXLSXGen.php ke app/Services/Libraries/");
        }

        $fpdfSource = base_path('espega_old/vendor/fpdf');
        if (File::isDirectory($fpdfSource) && !File::isDirectory($libDest . '/fpdf')) {
            File::copyDirectory($fpdfSource, $libDest . '/fpdf');
            $this->info("Disalin: direktori fpdf ke app/Services/Libraries/fpdf");
        }

        return Command::SUCCESS;
    }
}
