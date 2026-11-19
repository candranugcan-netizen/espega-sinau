<?php

namespace App\Services;

use App\Models\BankSoal;
use App\Models\Question;
use App\Models\User;
use Illuminate\Support\Str;

class PdfService
{
    /**
     * Pastikan FPDF termuat
     */
    protected static function loadFpdf()
    {
        if (!class_exists('FPDF')) {
            $path = app_path('Services/Libraries/fpdf/fpdf.php');
            if (!file_exists($path)) {
                $path = base_path('espega_old/vendor/fpdf/fpdf.php');
            }
            if (file_exists($path)) {
                require_once $path;
            }
        }
    }

    /**
     * Cetak Kartu Peserta Ujian (Grid 2 Kolom x 4 Baris = 8 kartu per halaman A4)
     */
    public static function cetakKartuPeserta(?string $kelas = null, ?string $search = null)
    {
        self::loadFpdf();

        $query = User::where('role_id', 3);
        if (!empty($kelas)) {
            $query->where('kelas', $kelas);
        }
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('username', 'LIKE', "%$search%")
                  ->orWhere('nama_lengkap', 'LIKE', "%$search%");
            });
        }
        $students = $query->orderBy('kelas')->orderBy('nama_lengkap')->get();

        if (!class_exists('FPDF')) {
            abort(500, 'Library FPDF tidak ditemukan.');
        }

        $pdf = new \FPDF('P', 'mm', 'A4');
        $pdf->SetAutoPageBreak(false);
        $pdf->AddPage();

        // Parameter Ukuran Kartu (A4 = 210 x 297 mm)
        $cardW = 90;
        $cardH = 62;
        $startX = 12;
        $startY = 15;
        $gapX = 6;
        $gapY = 7;

        $x = $startX;
        $y = $startY;
        $count = 0;

        foreach ($students as $s) {
            // Cek pindah halaman jika sudah 8 kartu (2 kolom x 4 baris)
            if ($count > 0 && $count % 8 === 0) {
                $pdf->AddPage();
                $x = $startX;
                $y = $startY;
            }

            // 1. Bingkai Kartu
            $pdf->SetDrawColor(30, 41, 59);
            $pdf->SetLineWidth(0.3);
            $pdf->Rect($x, $y, $cardW, $cardH);

            // 2. Header Kartu (Biru Muda / Slate 100)
            $pdf->SetFillColor(241, 245, 249);
            $pdf->Rect($x + 0.5, $y + 0.5, $cardW - 1, 13, 'F');

            $pdf->SetTextColor(15, 23, 42);
            $pdf->SetFont('Arial', 'B', 9.5);
            $pdf->SetXY($x, $y + 2);
            $pdf->Cell($cardW, 5, 'KARTU PESERTA CBT', 0, 1, 'C');

            $pdf->SetFont('Arial', 'B', 8);
            $pdf->SetTextColor(37, 99, 235); // Biru Cobalt
            $pdf->SetXY($x, $y + 7.5);
            $pdf->Cell($cardW, 4, 'SMP NEGERI 3 NGAWEN', 0, 1, 'C');

            // Garis pembatas header
            $pdf->SetDrawColor(203, 213, 225);
            $pdf->SetLineWidth(0.2);
            $pdf->Line($x, $y + 13.5, $x + $cardW, $y + 13.5);

            // 3. Konten Data Siswa
            $pdf->SetTextColor(30, 41, 59);
            $contentY = $y + 17;
            $lineH = 5.5;
            $labelW = 20;

            // Nama Lengkap
            $pdf->SetFont('Arial', '', 8.5);
            $pdf->SetXY($x + 4, $contentY);
            $pdf->Cell($labelW, $lineH, 'Nama', 0, 0);
            $pdf->Cell(2, $lineH, ':', 0, 0);
            $pdf->SetFont('Arial', 'B', 8.5);
            $nama = Str::limit($s->nama_lengkap, 24);
            $pdf->Cell(60, $lineH, $nama, 0, 1);

            // Kelas
            $pdf->SetFont('Arial', '', 8.5);
            $pdf->SetXY($x + 4, $contentY + $lineH);
            $pdf->Cell($labelW, $lineH, 'Kelas', 0, 0);
            $pdf->Cell(2, $lineH, ':', 0, 0);
            $pdf->SetFont('Arial', 'B', 8.5);
            $pdf->Cell(60, $lineH, $s->kelas ?? '-', 0, 1);

            // Username / NIS
            $pdf->SetFont('Arial', '', 8.5);
            $pdf->SetXY($x + 4, $contentY + ($lineH * 2));
            $pdf->Cell($labelW, $lineH, 'NIS / User', 0, 0);
            $pdf->Cell(2, $lineH, ':', 0, 0);
            $pdf->SetFont('Arial', 'B', 9);
            $pdf->Cell(60, $lineH, $s->username, 0, 1);

            // Token Login
            $pdf->SetFont('Arial', '', 8.5);
            $pdf->SetXY($x + 4, $contentY + ($lineH * 3));
            $pdf->Cell($labelW, $lineH, 'Token Akses', 0, 0);
            $pdf->Cell(2, $lineH, ':', 0, 0);
            $pdf->SetFont('Arial', 'B', 12);
            $pdf->SetTextColor(220, 38, 38); // Merah untuk Token
            $pdf->Cell(60, $lineH, $s->token, 0, 1);
            $pdf->SetTextColor(30, 41, 59);

            // 4. Footer Kartu
            $pdf->SetDrawColor(226, 232, 240);
            $pdf->Line($x + 4, $y + 51, $x + $cardW - 4, $y + 51);

            $pdf->SetFont('Arial', 'I', 7);
            $pdf->SetTextColor(100, 116, 139);
            $pdf->SetXY($x, $y + 53);
            $pdf->Cell($cardW, 4, '*Wajib dibawa saat mengikuti ujian sekolah', 0, 0, 'C');

            // Posisi Grid: Geser ke kanan atau pindah baris
            if ($x === $startX) {
                $x += $cardW + $gapX;
            } else {
                $x = $startX;
                $y += $cardH + $gapY;
            }

            $count++;
        }

        $filename = 'Kartu_Peserta_CBT_' . ($kelas ? "Kelas_{$kelas}_" : 'Semua_') . date('Ymd') . '.pdf';
        $pdf->Output('D', $filename);
        exit;
    }

    /**
     * Export Bank Soal ke format PDF Siap Cetak (2 Kolom + Kop Sekolah + Kunci Jawaban)
     */
    public static function exportBankSoalPdf(int $bankSoalId, bool $withKunci = true)
    {
        self::loadFpdf();

        $bank = BankSoal::with(['subject', 'guru'])->findOrFail($bankSoalId);
        $questions = Question::where('bank_soal_id', $bankSoalId)
            ->orderByRaw('CASE WHEN urutan > 0 THEN urutan ELSE 9999999 END ASC')
            ->orderBy('id')
            ->get();

        if (!class_exists('FPDF')) {
            abort(500, 'Library FPDF tidak ditemukan.');
        }

        $pdf = new \FPDF('P', 'mm', 'A4');
        $pdf->SetMargins(15, 15, 15);
        $pdf->SetAutoPageBreak(true, 15);
        $pdf->AddPage();

        // KOP SURAT RESMI
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(0, 5, 'PEMERINTAH KABUPATEN GUNUNGKIDUL', 0, 1, 'C');
        $pdf->SetFont('Arial', 'B', 11);
        $pdf->Cell(0, 5, 'DINAS PENDIDIKAN', 0, 1, 'C');
        $pdf->SetFont('Arial', 'B', 14);
        $pdf->Cell(0, 6, 'SMP NEGERI 3 NGAWEN', 0, 1, 'C');
        $pdf->SetFont('Arial', '', 8.5);
        $pdf->Cell(0, 4, 'Alamat: Kapanewon Ngawen, Kabupaten Gunungkidul, D.I. Yogyakarta', 0, 1, 'C');

        $pdf->SetLineWidth(0.8);
        $pdf->Line(15, $pdf->GetY() + 2, 195, $pdf->GetY() + 2);
        $pdf->SetLineWidth(0.2);
        $pdf->Line(15, $pdf->GetY() + 3, 195, $pdf->GetY() + 3);
        $pdf->Ln(6);

        // JUDUL NASKAH
        $pdf->SetFont('Arial', 'B', 11);
        $pdf->Cell(0, 5, 'NASKAH BUTIR SOAL UJIAN SEKOLAH', 0, 1, 'C');
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->SetTextColor(37, 99, 235);
        $pdf->Cell(0, 5, strtoupper($bank->nama_bank), 0, 1, 'C');
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Ln(2);

        // KOTAK INFORMASI UJIAN
        $pdf->SetFont('Arial', '', 8.5);
        $pdf->Cell(35, 5, 'Mata Pelajaran', 0, 0);
        $pdf->Cell(3, 5, ':', 0, 0);
        $pdf->SetFont('Arial', 'B', 8.5);
        $pdf->Cell(70, 5, $bank->subject->nama_mapel ?? '-', 0, 0);

        $pdf->SetFont('Arial', '', 8.5);
        $pdf->Cell(25, 5, 'Kelas Target', 0, 0);
        $pdf->Cell(3, 5, ':', 0, 0);
        $pdf->SetFont('Arial', 'B', 8.5);
        $pdf->Cell(40, 5, $bank->kelas_rilis ?? 'Semua Kelas', 0, 1);

        $pdf->SetFont('Arial', '', 8.5);
        $pdf->Cell(35, 5, 'Guru Pengampu', 0, 0);
        $pdf->Cell(3, 5, ':', 0, 0);
        $pdf->Cell(70, 5, $bank->guru->nama_lengkap ?? '-', 0, 0);

        $pdf->Cell(25, 5, 'Jumlah Soal', 0, 0);
        $pdf->Cell(3, 5, ':', 0, 0);
        $pdf->Cell(40, 5, count($questions) . ' Butir Pilihan Ganda', 0, 1);

        $pdf->SetLineWidth(0.2);
        $pdf->Line(15, $pdf->GetY() + 2, 195, $pdf->GetY() + 2);
        $pdf->Ln(5);

        // DAFTAR BUTIR SOAL
        $num = 1;
        $distribusi = ['A' => 0, 'B' => 0, 'C' => 0, 'D' => 0, 'E' => 0];

        foreach ($questions as $q) {
            $pdf->SetFont('Arial', 'B', 9);
            $pdf->Cell(8, 5, $num . '.', 0, 0);

            $pdf->SetFont('Arial', '', 9);
            $cleanPertanyaan = strip_tags($q->pertanyaan);
            $pdf->MultiCell(172, 5, $cleanPertanyaan);

            // Jika ada gambar soal
            if (!empty($q->img_pertanyaan)) {
                $imgPath = public_path('assets/img/soal/' . $q->img_pertanyaan);
                if (file_exists($imgPath)) {
                    $pdf->Ln(1);
                    $pdf->Image($imgPath, $pdf->GetX() + 8, $pdf->GetY(), 60);
                    $pdf->Ln(45);
                }
            }

            $pdf->Ln(1);

            // Pilihan Jawaban A, B, C, D, E
            foreach (['A', 'B', 'C', 'D', 'E'] as $opt) {
                $key = strtolower($opt);
                $optText = $q->{'pil_' . $key};
                $optImg = $q->{'img_' . $key};

                if (!empty($optText) || !empty($optImg)) {
                    $pdf->SetFont('Arial', 'B', 8.5);
                    $pdf->Cell(8, 4.5, '', 0, 0);
                    $pdf->Cell(6, 4.5, $opt . '.', 0, 0);

                    $pdf->SetFont('Arial', '', 8.5);
                    $pdf->MultiCell(166, 4.5, strip_tags((string)$optText));

                    if (!empty($optImg)) {
                        $imgPath = public_path('assets/img/soal/' . $optImg);
                        if (file_exists($imgPath)) {
                            $pdf->Image($imgPath, $pdf->GetX() + 14, $pdf->GetY(), 40);
                            $pdf->Ln(28);
                        }
                    }
                }
            }

            $kunci = strtoupper(trim($q->kunci_jawaban ?? 'A'));
            if (isset($distribusi[$kunci])) {
                $distribusi[$kunci]++;
            }

            $pdf->Ln(3);
            $num++;
        }

        // KUNCI JAWABAN TERPISAH JIKA DIMINTA
        if ($withKunci) {
            $pdf->AddPage();
            $pdf->SetFont('Arial', 'B', 12);
            $pdf->Cell(0, 6, 'KUNCI JAWABAN & DISTRIBUSI KUNCI', 0, 1, 'C');
            $pdf->SetFont('Arial', '', 9);
            $pdf->Cell(0, 5, strtoupper($bank->nama_bank), 0, 1, 'C');
            $pdf->Ln(4);

            // Tabel 5 Pasang Kolom (No - Kunci)
            $totalQ = count($questions);
            $colPairs = 5;
            $numRows = ceil($totalQ / $colPairs);

            $pdf->SetFont('Arial', 'B', 8.5);
            $pdf->SetFillColor(241, 245, 249);

            for ($c = 0; $c < $colPairs; $c++) {
                $pdf->Cell(18, 6, 'No', 1, 0, 'C', true);
                $pdf->Cell(18, 6, 'Kunci', 1, ($c === $colPairs - 1) ? 1 : 0, 'C', true);
            }

            $pdf->SetFont('Arial', '', 8.5);
            for ($r = 0; $r < $numRows; $r++) {
                for ($c = 0; $c < $colPairs; $c++) {
                    $qIdx = $r + ($c * $numRows);
                    if ($qIdx < $totalQ) {
                        $qItem = $questions[$qIdx];
                        $kunci = strtoupper(trim($qItem->kunci_jawaban ?? 'A'));

                        $pdf->Cell(18, 5.5, ($qIdx + 1), 1, 0, 'C');
                        $pdf->SetFont('Arial', 'B', 9);
                        $pdf->SetTextColor(37, 99, 235);
                        $pdf->Cell(18, 5.5, $kunci, 1, ($c === $colPairs - 1) ? 1 : 0, 'C');
                        $pdf->SetTextColor(0, 0, 0);
                        $pdf->SetFont('Arial', '', 8.5);
                    } else {
                        $pdf->Cell(18, 5.5, '-', 1, 0, 'C');
                        $pdf->Cell(18, 5.5, '-', 1, ($c === $colPairs - 1) ? 1 : 0, 'C');
                    }
                }
            }

            $pdf->Ln(6);
            $pdf->SetFont('Arial', 'B', 8.5);
            $rekap = sprintf("Rekapitulasi: A=%d  |  B=%d  |  C=%d  |  D=%d  |  E=%d  (Total %d Soal)",
                $distribusi['A'], $distribusi['B'], $distribusi['C'], $distribusi['D'], $distribusi['E'], $totalQ
            );
            $pdf->Cell(0, 6, $rekap, 1, 1, 'C', true);
        }

        $safeName = Str::slug($bank->nama_bank, '_');
        $filename = "Naskah_Soal_{$safeName}.pdf";
        $pdf->Output('I', $filename);
        exit;
    }
}
