<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ImageOptimizerService
{
    /**
     * Upload dan kompres gambar secara otomatis menjadi format WebP berkualitas tinggi & ringan.
     *
     * @param UploadedFile|string $file Berkas gambar yang diunggah atau path file
     * @param string $destinationFolder Direktori tujuan absolut (misal: public_path('uploads/berita'))
     * @param int $maxDimension Batas dimensi maksimal lebar/tinggi gambar (default: 1600px)
     * @param int $quality Kualitas kompresi WebP (0-100, default 82 untuk rasio ukuran/kualitas ideal)
     * @param string|null $prefix Awalan nama file (misal: 'berita_', 'img_soal_')
     * @return string Nama file yang tersimpan
     */
    public static function uploadAndConvertToWebp($file, string $destinationFolder, int $maxDimension = 1600, int $quality = 82, ?string $prefix = null): string
    {
        // Pastikan folder tujuan tersedia
        if (!File::isDirectory($destinationFolder)) {
            File::makeDirectory($destinationFolder, 0755, true);
        }

        $prefix = $prefix ?: 'img_';
        $uniqueName = $prefix . time() . '_' . Str::random(8);

        // Jika GD library tidak tersedia, lakukan simpan standar
        if (!extension_loaded('gd') || !function_exists('imagecreatefromstring')) {
            $ext = ($file instanceof UploadedFile) ? $file->getClientOriginalExtension() : pathinfo($file, PATHINFO_EXTENSION);
            $filename = $uniqueName . '.' . strtolower($ext ?: 'jpg');
            if ($file instanceof UploadedFile) {
                $file->move($destinationFolder, $filename);
            } else {
                File::copy($file, $destinationFolder . '/' . $filename);
            }
            return $filename;
        }

        try {
            $rawContent = ($file instanceof UploadedFile) ? file_get_contents($file->getRealPath()) : file_get_contents($file);
            $srcImage = @imagecreatefromstring($rawContent);

            if (!$srcImage) {
                // Fallback jika bukan format gambar yang dikenali GD
                $ext = ($file instanceof UploadedFile) ? $file->getClientOriginalExtension() : pathinfo($file, PATHINFO_EXTENSION);
                $filename = $uniqueName . '.' . strtolower($ext ?: 'jpg');
                if ($file instanceof UploadedFile) {
                    $file->move($destinationFolder, $filename);
                } else {
                    File::copy($file, $destinationFolder . '/' . $filename);
                }
                return $filename;
            }

            // Atasi orientasi EXIF (foto dari kamera HP yang sering miring/terbalik)
            if ($file instanceof UploadedFile && function_exists('exif_read_data')) {
                try {
                    $exif = @exif_read_data($file->getRealPath());
                    if (!empty($exif['Orientation'])) {
                        switch ($exif['Orientation']) {
                            case 3:
                                $srcImage = imagerotate($srcImage, 180, 0);
                                break;
                            case 6:
                                $srcImage = imagerotate($srcImage, -90, 0);
                                break;
                            case 8:
                                $srcImage = imagerotate($srcImage, 90, 0);
                                break;
                        }
                    }
                } catch (\Throwable $e) {
                    // Abaikan kesalahan EXIF
                }
            }

            $origWidth = imagesx($srcImage);
            $origHeight = imagesy($srcImage);

            // Hitung skala resize proporsional jika resolusi melebihi batas maksimal
            $targetWidth = $origWidth;
            $targetHeight = $origHeight;

            if ($origWidth > $maxDimension || $origHeight > $maxDimension) {
                if ($origWidth >= $origHeight) {
                    $targetWidth = $maxDimension;
                    $targetHeight = (int)round(($origHeight / $origWidth) * $maxDimension);
                } else {
                    $targetHeight = $maxDimension;
                    $targetWidth = (int)round(($origWidth / $origHeight) * $maxDimension);
                }
            }

            // Buat kanvas gambar baru
            $dstImage = imagecreatetruecolor($targetWidth, $targetHeight);

            // Pertahankan transparansi (alpha channel)
            imagealphablending($dstImage, false);
            imagesavealpha($dstImage, true);
            $transparent = imagecolorallocatealpha($dstImage, 255, 255, 255, 127);
            imagefilledrectangle($dstImage, 0, 0, $targetWidth, $targetHeight, $transparent);
            imagealphablending($dstImage, true);

            // Resample dengan filter anti-aliasing berkualitas tinggi
            imagecopyresampled($dstImage, $srcImage, 0, 0, 0, 0, $targetWidth, $targetHeight, $origWidth, $origHeight);

            // Simpan ke WebP jika didukung oleh PHP GD
            if (function_exists('imagewebp')) {
                $filename = $uniqueName . '.webp';
                $destPath = rtrim($destinationFolder, '/\\') . DIRECTORY_SEPARATOR . $filename;
                
                // Pastikan transparansi terjaga pada WebP
                imagealphablending($dstImage, false);
                imagesavealpha($dstImage, true);
                
                imagewebp($dstImage, $destPath, $quality);
            } else {
                // Fallback ke JPEG berkualitas tinggi & terkompresi
                $filename = $uniqueName . '.jpg';
                $destPath = rtrim($destinationFolder, '/\\') . DIRECTORY_SEPARATOR . $filename;
                imagejpeg($dstImage, $destPath, $quality);
            }

            // Bersihkan memori
            imagedestroy($srcImage);
            imagedestroy($dstImage);

            return $filename;

        } catch (\Throwable $e) {
            // Jika terjadi kesalahan saat proses kompresi, fallback simpan berkas asli secara aman
            $ext = ($file instanceof UploadedFile) ? $file->getClientOriginalExtension() : pathinfo($file, PATHINFO_EXTENSION);
            $filename = $uniqueName . '.' . strtolower($ext ?: 'jpg');
            if ($file instanceof UploadedFile) {
                $file->move($destinationFolder, $filename);
            } else {
                File::copy($file, $destinationFolder . '/' . $filename);
            }
            return $filename;
        }
    }
}

