<?php
/**
 * Class BankSoalExporter
 * Menangani export Bank Soal ke format Excel (.xlsx) dan PDF Siap Cetak (2 Kolom + Kop SMPN 3 Ngawen + Kunci Jawaban terpisah)
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../vendor/SimpleXLSXGen.php';
require_once __DIR__ . '/../vendor/fpdf/fpdf.php';

use Shuchkin\SimpleXLSXGen;

class BankSoalPDF extends FPDF {
    protected $col = 0;
    protected $y0;
    protected $isTwoColumn = false;
    protected $runningTitle = '';

    public function setRunningTitle($title) {
        $this->runningTitle = $title;
    }

    public function setTwoColumn($enable) {
        $this->isTwoColumn = $enable;
        if (!$enable) {
            $this->SetLeftMargin(10);
            $this->SetRightMargin(10);
            $this->col = 0;
        }
    }

    public function setColumnStartY($y) {
        $this->y0 = $y;
    }

    public function getCol() {
        return $this->col;
    }

    public function SetCol($col) {
        $this->col = $col;
        // Total lebar cetak: 190mm (X: 10 sampai 200).
        // 2 kolom lebar 91mm dengan celah 8mm:
        // Kolom 0: X = 10mm sampai 101mm.
        // Kolom 1: X = 109mm sampai 200mm.
        $x = 10 + $col * 99;
        $this->SetLeftMargin($x);
        $this->SetRightMargin(210 - ($x + 91));
        $this->SetX($x);
    }

    public function AcceptPageBreak() {
        if (!$this->isTwoColumn) {
            return true;
        }
        if ($this->col < 1) {
            // Pindah ke kolom kanan pada halaman yang sama
            $this->SetCol(1);
            $this->SetY($this->y0);
            return false;
        } else {
            // Pindah ke kolom kiri di halaman berikutnya
            $this->SetCol(0);
            return true;
        }
    }

    public function CheckSpace($height) {
        if ($this->GetY() + $height > 275) {
            if ($this->isTwoColumn) {
                if ($this->col < 1) {
                    $this->SetCol(1);
                    $this->SetY($this->y0);
                } else {
                    $this->SetCol(0);
                    $this->AddPage();
                }
            } else {
                $this->AddPage();
            }
        }
    }

    function Header() {
        if ($this->page > 1 && $this->isTwoColumn) {
            $this->SetLeftMargin(10);
            $this->SetRightMargin(10);
            $this->SetX(10);
            $this->SetFont('Arial', 'I', 7.5);
            $this->SetTextColor(100, 116, 139);
            $this->Cell(0, 5, 'SMP Negeri 3 Ngawen  |  ' . $this->runningTitle, 0, 1, 'L');
            $this->SetDrawColor(203, 213, 225);
            $this->SetLineWidth(0.2);
            $this->Line(10, $this->GetY(), 200, $this->GetY());
            $this->Ln(3);
            $this->y0 = $this->GetY();
            $this->SetCol(0);
        }
    }

    function Footer() {
        $this->SetY(-12);
        $this->SetLeftMargin(10);
        $this->SetRightMargin(10);
        $this->SetX(10);
        $this->SetFont('Arial', '', 7.5);
        $this->SetTextColor(100, 116, 139);
        $this->SetDrawColor(226, 232, 240);
        $this->SetLineWidth(0.2);
        $this->Line(10, $this->GetY(), 200, $this->GetY());
        $this->SetY(-11);
        $this->Cell(0, 8, 'SMP Negeri 3 Ngawen  -  Halaman ' . $this->PageNo(), 0, 0, 'C');
    }
}

class BankSoalExporter {

    private static function cleanText($text) {
        if ($text === null || $text === '') return '';
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $replacements = [
            "\xE2\x80\x98" => "'", // ‘
            "\xE2\x80\x99" => "'", // ’
            "\xE2\x80\x9C" => '"', // “
            "\xE2\x80\x9D" => '"', // ”
            "\xE2\x80\x93" => "-", // –
            "\xE2\x80\x94" => "-", // —
            "\xE2\x80\xA6" => "...", // …
            "\xC2\xA0"     => " ", // non-breaking space
        ];
        $text = strtr($text, $replacements);
        if (function_exists('iconv')) {
            $converted = @iconv('UTF-8', 'windows-1252//TRANSLIT', $text);
            if ($converted !== false) return $converted;
        }
        return utf8_decode($text);
    }

    /**
     * Export Bank Soal ke format Excel (.xlsx) sesuai template import
     */
    public static function exportExcel($pdo, $bank_id, $userId = null, $userRole = null) {
        if ($userId === null && isset($_SESSION['user_id'])) {
            $userId = $_SESSION['user_id'];
        }
        if ($userRole === null && isset($_SESSION['role'])) {
            $userRole = $_SESSION['role'];
        }

        if ($userRole === 'admin') {
            $stmt = $pdo->prepare("
                SELECT bs.*, s.nama_mapel, u.nama_lengkap as nama_guru 
                FROM bank_soal bs 
                JOIN subjects s ON bs.subject_id = s.id 
                LEFT JOIN users u ON bs.guru_id = u.id 
                WHERE bs.id = ?
            ");
            $stmt->execute([$bank_id]);
        } else {
            $stmt = $pdo->prepare("
                SELECT bs.*, s.nama_mapel, u.nama_lengkap as nama_guru 
                FROM bank_soal bs 
                JOIN subjects s ON bs.subject_id = s.id 
                LEFT JOIN users u ON bs.guru_id = u.id 
                WHERE bs.id = ? AND bs.guru_id = ?
            ");
            $stmt->execute([$bank_id, $userId]);
        }
        $bank = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$bank) {
            die("Bank Soal tidak ditemukan atau Anda tidak memiliki akses.");
        }

        $stmtQ = $pdo->prepare("
            SELECT pertanyaan, pil_a, pil_b, pil_c, pil_d, pil_e, kunci_jawaban 
            FROM questions 
            WHERE bank_soal_id = ? 
            ORDER BY (CASE WHEN urutan > 0 THEN urutan ELSE 9999999 END) ASC, id ASC
        ");
        $stmtQ->execute([$bank_id]);
        $questions = $stmtQ->fetchAll(PDO::FETCH_ASSOC);

        $excelData = [
            ['Pertanyaan', 'Pilihan A', 'Pilihan B', 'Pilihan C', 'Pilihan D', 'Pilihan E', 'Kunci (A/B/C/D/E)']
        ];

        foreach ($questions as $q) {
            $excelData[] = [
                $q['pertanyaan'] ?? '',
                $q['pil_a'] ?? '',
                $q['pil_b'] ?? '',
                $q['pil_c'] ?? '',
                $q['pil_d'] ?? '',
                $q['pil_e'] ?? '',
                strtoupper(trim($q['kunci_jawaban'] ?? 'A'))
            ];
        }

        $cleanName = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $bank['nama_bank']);
        $filename = "Template_Bank_Soal_" . $cleanName . ".xlsx";

        $xlsx = SimpleXLSXGen::fromArray($excelData);
        $xlsx->downloadAs($filename);
        exit;
    }

    /**
     * Export Bank Soal ke format PDF Siap Cetak (2 Kolom + Kop SMPN 3 Ngawen + Kunci Jawaban terpisah)
     */
    public static function exportPDF($pdo, $bank_id, $arg3 = true, $arg4 = null, $arg5 = true) {
        // Mendukung kedua variasi urutan argumen:
        // A. exportPDF($pdo, $bank_id, $withKey, $userId, $userRole)
        // B. exportPDF($pdo, $bank_id, $userId, $userRole, $withKey)
        if (is_bool($arg3)) {
            $withKey  = $arg3;
            $userId   = $arg4 ?? ($_SESSION['user_id'] ?? null);
            $userRole = (is_string($arg5) ? $arg5 : null) ?? ($_SESSION['role'] ?? 'guru');
        } else {
            $userId   = $arg3 ?? ($_SESSION['user_id'] ?? null);
            $userRole = (is_string($arg4) ? $arg4 : null) ?? ($_SESSION['role'] ?? 'guru');
            $withKey  = is_bool($arg5) ? $arg5 : true;
        }

        if ($userRole === 'admin') {
            $stmt = $pdo->prepare("
                SELECT bs.*, s.nama_mapel, s.kelas as kelas_mapel, u.nama_lengkap as nama_guru 
                FROM bank_soal bs 
                JOIN subjects s ON bs.subject_id = s.id 
                LEFT JOIN users u ON bs.guru_id = u.id 
                WHERE bs.id = ?
            ");
            $stmt->execute([$bank_id]);
        } else {
            $stmt = $pdo->prepare("
                SELECT bs.*, s.nama_mapel, s.kelas as kelas_mapel, u.nama_lengkap as nama_guru 
                FROM bank_soal bs 
                JOIN subjects s ON bs.subject_id = s.id 
                LEFT JOIN users u ON bs.guru_id = u.id 
                WHERE bs.id = ? AND bs.guru_id = ?
            ");
            $stmt->execute([$bank_id, $userId]);
        }
        $bank = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$bank) {
            die("Bank Soal tidak ditemukan atau Anda tidak memiliki akses.");
        }

        $stmtQ = $pdo->prepare("SELECT * FROM questions WHERE bank_soal_id = ? ORDER BY (CASE WHEN urutan > 0 THEN urutan ELSE 9999999 END) ASC, id ASC");
        $stmtQ->execute([$bank_id]);
        $questions = $stmtQ->fetchAll(PDO::FETCH_ASSOC);

        $pdf = new BankSoalPDF('P', 'mm', 'A4');
        $pdf->SetMargins(10, 10, 10);
        $pdf->SetAutoPageBreak(true, 15);
        $pdf->setRunningTitle(self::cleanText($bank['nama_bank']));

        // ==========================================
        // 1. HALAMAN UTAMA (KOP SURAT & NASKAH SOAL)
        // ==========================================
        $pdf->AddPage(); // Halaman 1 dalam mode Single Column

        // --- KOP SMP NEGERI 3 NGAWEN ---
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(0, 4.5, 'PEMERINTAH KABUPATEN GUNUNGKIDUL', 0, 1, 'C');
        $pdf->Cell(0, 4.5, 'DINAS PENDIDIKAN', 0, 1, 'C');
        $pdf->SetFont('Arial', 'B', 14);
        $pdf->Cell(0, 6, 'SMP NEGERI 3 NGAWEN', 0, 1, 'C');
        $pdf->SetFont('Arial', '', 7.5);
        $pdf->Cell(0, 4, 'Alamat: Jurangjero, Ngawen, Gunungkidul, D.I. Yogyakarta 55853', 0, 1, 'C');
        $pdf->Ln(2);

        // Garis Ganda Kop Surat
        $yKop = $pdf->GetY();
        $pdf->SetDrawColor(0, 0, 0);
        $pdf->SetLineWidth(0.7);
        $pdf->Line(10, $yKop, 200, $yKop);
        $pdf->SetLineWidth(0.2);
        $pdf->Line(10, $yKop + 0.8, 200, $yKop + 0.8);
        $pdf->Ln(3);

        // Judul Naskah
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(0, 5, 'NASKAH BUTIR SOAL UJIAN / PENILAIAN', 0, 1, 'C');
        $pdf->Ln(1);

        // Box Metadata Ujian (Tabel 2 Kolom Header)
        $metaY = $pdf->GetY();
        $pdf->SetFillColor(248, 250, 252);
        $pdf->SetDrawColor(203, 213, 225);
        $pdf->Rect(10, $metaY, 190, 18, 'DF');

        $pdf->SetXY(13, $metaY + 2);
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->Cell(25, 4, 'Mata Pelajaran', 0, 0, 'L');
        $pdf->SetFont('Arial', '', 8);
        $pdf->Cell(65, 4, ': ' . self::cleanText($bank['nama_mapel']), 0, 0, 'L');

        $pdf->SetFont('Arial', 'B', 8);
        $pdf->Cell(25, 4, 'Paket Soal', 0, 0, 'L');
        $pdf->SetFont('Arial', '', 8);
        $pdf->Cell(60, 4, ': ' . self::cleanText($bank['nama_bank']), 0, 1, 'L');

        $pdf->SetX(13);
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->Cell(25, 4, 'Kelas Sasaran', 0, 0, 'L');
        $pdf->SetFont('Arial', '', 8);
        $targetKelas = !empty($bank['kelas_rilis']) ? $bank['kelas_rilis'] : ($bank['kelas_mapel'] ?: 'Semua Kelas');
        $pdf->Cell(65, 4, ': ' . self::cleanText($targetKelas), 0, 0, 'L');

        $pdf->SetFont('Arial', 'B', 8);
        $pdf->Cell(25, 4, 'Guru Pengampu', 0, 0, 'L');
        $pdf->SetFont('Arial', '', 8);
        $pdf->Cell(60, 4, ': ' . self::cleanText($bank['nama_guru'] ?: 'Guru Mata Pelajaran'), 0, 1, 'L');

        $pdf->SetX(13);
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->Cell(25, 4, 'Bentuk Soal', 0, 0, 'L');
        $pdf->SetFont('Arial', '', 8);
        $pdf->Cell(65, 4, ': Pilihan Ganda (' . count($questions) . ' Butir Soal)', 0, 0, 'L');

        $pdf->SetFont('Arial', 'B', 8);
        $pdf->Cell(25, 4, 'Status Dokumen', 0, 0, 'L');
        $pdf->SetFont('Arial', '', 8);
        $pdf->Cell(60, 4, ': Bank Soal Resmi Sekolah', 0, 1, 'L');

        $pdf->SetY($metaY + 20);

        // Petunjuk Pengerjaan
        $pdf->SetFont('Arial', 'I', 7.5);
        $pdf->SetTextColor(71, 85, 105);
        $pdf->Cell(0, 4, 'Petunjuk: Pilihlah salah satu jawaban yang paling tepat dengan menyilang (X) pada lembar jawaban yang tersedia!', 0, 1, 'L');
        $pdf->Ln(2);

        // ==========================================
        // 2. AKTIFKAN FORMAT SOAL 2 KOLOM KANAN KIRI
        // ==========================================
        $yColumnStart = $pdf->GetY();
        $pdf->setColumnStartY($yColumnStart);
        $pdf->setTwoColumn(true);
        $pdf->SetCol(0);

        $no = 1;
        $imgBaseDir = __DIR__ . '/../assets/img/soal/';

        foreach ($questions as $q) {
            // Perkirakan tinggi pertanyaan dan opsi untuk mencegah pemotongan janggal
            $qLines = ceil(strlen($q['pertanyaan']) / 45);
            $estH = 5 + ($qLines * 4.2) + 22; // Estimasi teks + 4 opsi jawaban
            if (!empty($q['img_pertanyaan'])) $estH += 35;
            $pdf->CheckSpace(min($estH, 50));

            $currentColX = 10 + $pdf->getCol() * 99;

            // Cetak Nomor Soal dan Teks Pertanyaan
            $pdf->SetFont('Arial', 'B', 8.5);
            $pdf->SetTextColor(15, 23, 42);
            $pdf->Cell(5.5, 4.3, $no . '.', 0, 0, 'L');

            $pdf->SetFont('Arial', '', 8.5);
            $cleanPertanyaan = self::cleanText($q['pertanyaan']);
            $pdf->MultiCell(85.5, 4.3, $cleanPertanyaan, 0, 'L');

            // Cetak Gambar Pertanyaan (Jika Ada)
            if (!empty($q['img_pertanyaan'])) {
                $imgPath = $imgBaseDir . $q['img_pertanyaan'];
                if (file_exists($imgPath) && is_file($imgPath)) {
                    $imgSize = @getimagesize($imgPath);
                    if ($imgSize && in_array($imgSize[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG])) {
                        $w = $imgSize[0];
                        $h = $imgSize[1];
                        $renderW = min(75, 85);
                        $renderH = ($h / $w) * $renderW;
                        if ($renderH > 42) {
                            $renderH = 42;
                            $renderW = ($w / $h) * $renderH;
                        }
                        $pdf->CheckSpace($renderH + 3);
                        $imgX = $currentColX + (91 - $renderW) / 2;
                        try {
                            $pdf->Image($imgPath, $imgX, $pdf->GetY() + 1, $renderW, $renderH);
                            $pdf->SetY($pdf->GetY() + $renderH + 3);
                        } catch (Exception $e) {}
                    }
                }
            }

            // Cetak Opsi Jawaban (A, B, C, D, dan opsional E)
            $options = [
                'A' => ['text' => $q['pil_a'], 'img' => $q['img_a'] ?? ''],
                'B' => ['text' => $q['pil_b'], 'img' => $q['img_b'] ?? ''],
                'C' => ['text' => $q['pil_c'], 'img' => $q['img_c'] ?? ''],
                'D' => ['text' => $q['pil_d'], 'img' => $q['img_d'] ?? ''],
            ];
            if (!empty($q['pil_e'])) {
                $options['E'] = ['text' => $q['pil_e'], 'img' => $q['img_e'] ?? ''];
            }

            foreach ($options as $optKey => $optData) {
                $pdf->SetFont('Arial', 'B', 8);
                $pdf->SetTextColor(30, 41, 59);
                $pdf->Cell(4.5, 4, $optKey . '.', 0, 0, 'L');

                $pdf->SetFont('Arial', '', 8);
                $pdf->MultiCell(86.5, 4, self::cleanText($optData['text'] ?: '-'), 0, 'L');

                // Gambar Opsi (jika ada)
                if (!empty($optData['img'])) {
                    $optImgPath = $imgBaseDir . $optData['img'];
                    if (file_exists($optImgPath) && is_file($optImgPath)) {
                        $oSize = @getimagesize($optImgPath);
                        if ($oSize && in_array($oSize[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG])) {
                            $ow = $oSize[0];
                            $oh = $oSize[1];
                            $orW = min(50, 75);
                            $orH = ($oh / $ow) * $orW;
                            if ($orH > 25) { $orH = 25; $orW = ($ow / $oh) * $orH; }
                            $pdf->CheckSpace($orH + 2);
                            try {
                                $pdf->Image($optImgPath, $currentColX + 5, $pdf->GetY() + 0.5, $orW, $orH);
                                $pdf->SetY($pdf->GetY() + $orH + 1.5);
                            } catch (Exception $e) {}
                        }
                    }
                }
            }

            $pdf->Ln(2); // Spasi antar butir soal
            $no++;
        }

        // ==========================================
        // 3. HALAMAN KUNCI JAWABAN TERPISAH
        // ==========================================
        if ($withKey) {
            $pdf->setTwoColumn(false);
            $pdf->AddPage(); // Halaman baru terpisah untuk Kunci Jawaban!

            // Header Kunci Jawaban
            $pdf->SetFont('Arial', 'B', 12);
            $pdf->SetTextColor(15, 23, 42);
            $pdf->Cell(0, 5, 'LEMBAR KUNCI JAWABAN RESMI', 0, 1, 'C');
            $pdf->SetFont('Arial', 'B', 14);
            $pdf->Cell(0, 6, 'SMP NEGERI 3 NGAWEN', 0, 1, 'C');
            $pdf->SetFont('Arial', '', 8.5);
            $pdf->SetTextColor(71, 85, 105);
            $pdf->Cell(0, 4.5, 'Paket: ' . self::cleanText($bank['nama_bank']) . '  |  Mata Pelajaran: ' . self::cleanText($bank['nama_mapel']) . ' (' . self::cleanText($targetKelas) . ')', 0, 1, 'C');
            $pdf->Ln(2);

            $pdf->SetDrawColor(15, 23, 42);
            $pdf->SetLineWidth(0.6);
            $pdf->Line(10, $pdf->GetY(), 200, $pdf->GetY());
            $pdf->Ln(6);

            // Tabel Grid Kunci Jawaban (5 pasang No-Kunci per baris, total lebar 190mm)
            // Setiap pasang = 38mm (No: 18mm, Kunci: 20mm)
            $totalQ = count($questions);
            $colPairs = 5;
            $wNo = 18;
            $wKey = 20;
            $wBlock = $wNo + $wKey; // 38mm

            // Hitung distribusi jawaban
            $distribusi = ['A' => 0, 'B' => 0, 'C' => 0, 'D' => 0, 'E' => 0];

            $pdf->SetFont('Arial', 'B', 8.5);
            $pdf->SetFillColor(241, 245, 249);
            $pdf->SetDrawColor(203, 213, 225);
            $pdf->SetLineWidth(0.2);

            // Cetak Baris Header Tabel Kunci
            for ($c = 0; $c < $colPairs; $c++) {
                $pdf->Cell($wNo, 6, 'No', 1, 0, 'C', true);
                $pdf->Cell($wKey, 6, 'Kunci', 1, ($c == $colPairs - 1) ? 1 : 0, 'C', true);
            }

            // Susun Baris Kunci
            $pdf->SetFont('Arial', '', 8.5);
            $numRows = ceil($totalQ / $colPairs);

            for ($r = 0; $r < $numRows; $r++) {
                $fill = ($r % 2 == 1);
                $pdf->SetFillColor(248, 250, 252);

                for ($c = 0; $c < $colPairs; $c++) {
                    $qIndex = $r + ($c * $numRows); // Pengurutan kolom menurun agar mudah dibaca vertikal
                    if ($qIndex < $totalQ) {
                        $qItem = $questions[$qIndex];
                        $kunci = strtoupper(trim($qItem['kunci_jawaban'] ?? 'A'));
                        if (isset($distribusi[$kunci])) $distribusi[$kunci]++;

                        $pdf->SetFont('Arial', '', 8.5);
                        $pdf->Cell($wNo, 5.5, ($qIndex + 1), 1, 0, 'C', $fill);
                        $pdf->SetFont('Arial', 'B', 9);
                        $pdf->SetTextColor(37, 99, 235); // Cobalt Blue
                        $pdf->Cell($wKey, 5.5, $kunci, 1, ($c == $colPairs - 1) ? 1 : 0, 'C', $fill);
                        $pdf->SetTextColor(15, 23, 42);
                    } else {
                        // Kotak kosong penutup jika total soal ganjil
                        $pdf->Cell($wNo, 5.5, '-', 1, 0, 'C', $fill);
                        $pdf->Cell($wKey, 5.5, '-', 1, ($c == $colPairs - 1) ? 1 : 0, 'C', $fill);
                    }
                }
            }

            $pdf->Ln(6);

            // Rekapitulasi Statistik Kunci Jawaban
            $pdf->SetFillColor(241, 245, 249);
            $pdf->SetDrawColor(203, 213, 225);
            $pdf->Rect(10, $pdf->GetY(), 190, 11, 'DF');
            $pdf->SetXY(14, $pdf->GetY() + 3);
            $pdf->SetFont('Arial', 'B', 8);
            $pdf->Cell(45, 5, 'Rekapitulasi Kunci Jawaban :', 0, 0, 'L');
            $pdf->SetFont('Arial', '', 8);
            $txtRekap = sprintf("A = %d  |  B = %d  |  C = %d  |  D = %d", $distribusi['A'], $distribusi['B'], $distribusi['C'], $distribusi['D']);
            if ($distribusi['E'] > 0) $txtRekap .= sprintf("  |  E = %d", $distribusi['E']);
            $txtRekap .= sprintf("    (Total: %d Butir)", $totalQ);
            $pdf->Cell(130, 5, $txtRekap, 0, 1, 'L');

            $pdf->Ln(10);

            // Tanda Tangan Pengesahan (Kepala Sekolah & Guru Pengampu)
            $ySign = $pdf->GetY();
            if ($ySign > 230) {
                $pdf->AddPage();
                $ySign = 20;
            }

            $tglHariIni = date('d') . ' ' . [1=>'Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'][(int)date('m')] . ' ' . date('Y');

            $pdf->SetFont('Arial', '', 8.5);
            $pdf->SetXY(15, $ySign);
            $pdf->Cell(80, 4.5, 'Mengetahui,', 0, 0, 'C');
            $pdf->SetXY(115, $ySign);
            $pdf->Cell(80, 4.5, 'Ngawen, ' . $tglHariIni, 0, 1, 'C');

            $pdf->SetXY(15, $ySign + 4.5);
            $pdf->Cell(80, 4.5, 'Kepala SMP Negeri 3 Ngawen', 0, 0, 'C');
            $pdf->SetXY(115, $ySign + 4.5);
            $pdf->Cell(80, 4.5, 'Guru Pengampu Mata Pelajaran,', 0, 1, 'C');

            $pdf->SetXY(15, $ySign + 24);
            $pdf->SetFont('Arial', 'B', 9);
            $pdf->Cell(80, 4.5, '.......................................................', 0, 0, 'C');
            $pdf->SetXY(115, $ySign + 24);
            $pdf->Cell(80, 4.5, self::cleanText($bank['nama_guru'] ?: '.......................................................'), 0, 1, 'C');

            $pdf->SetFont('Arial', '', 8);
            $pdf->SetXY(15, $ySign + 28.5);
            $pdf->Cell(80, 4.5, 'NIP. .................................................', 0, 0, 'C');
            $pdf->SetXY(115, $ySign + 28.5);
            $pdf->Cell(80, 4.5, 'NIP. .................................................', 0, 1, 'C');
        }

        $cleanName = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $bank['nama_bank']);
        $pdfFilename = "Naskah_Soal_" . $cleanName . ".pdf";

        // Output ke browser (I = Inline Preview / Download)
        $pdf->Output('I', $pdfFilename);
        exit;
    }
}

