<?php

namespace App\Services;

use App\Models\BankSoal;
use App\Models\Exam;
use App\Models\ExamAnswer;
use App\Models\ExamSession;
use App\Models\Question;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class ExcelService
{
    /**
     * Pastikan library SimpleXLSX dan SimpleXLSXGen termuat
     */
    protected static function loadLibraries()
    {
        if (!class_exists('Shuchkin\SimpleXLSX')) {
            $path = app_path('Services/Libraries/SimpleXLSX.php');
            if (!file_exists($path)) {
                $path = base_path('espega_old/vendor/SimpleXLSX.php');
            }
            if (file_exists($path)) {
                require_once $path;
            }
        }

        if (!class_exists('Shuchkin\SimpleXLSXGen')) {
            $path = app_path('Services/Libraries/SimpleXLSXGen.php');
            if (!file_exists($path)) {
                $path = base_path('espega_old/vendor/SimpleXLSXGen.php');
            }
            if (file_exists($path)) {
                require_once $path;
            }
        }
    }

    /**
     * Template Import Siswa (.xlsx)
     */
    public static function templateSiswa()
    {
        self::loadLibraries();

        $data = [
            ['<b>NIS (Username)</b>', '<b>Nama Lengkap</b>', '<b>Kelas</b>'],
            ['2026001', 'Ahmad Dhani', '7A'],
            ['2026002', 'Siti Aminah', '7A'],
            ['2026003', 'Budi Santoso', '7B'],
            ['2026004', 'Dewi Lestari', '8A'],
            ['2026005', 'Eko Prasetyo', '9A'],
        ];

        if (class_exists('Shuchkin\SimpleXLSXGen')) {
            \Shuchkin\SimpleXLSXGen::fromArray($data)->downloadAs('Template_Import_Siswa.xlsx');
            exit;
        }

        // Fallback ke CSV jika SimpleXLSXGen tidak tersedia
        return self::downloadCsv('Template_Import_Siswa.csv', [
            ['NIS (Username)', 'Nama Lengkap', 'Kelas'],
            ['2026001', 'Ahmad Dhani', '7A'],
            ['2026002', 'Siti Aminah', '7A'],
        ]);
    }

    /**
     * Import Siswa dari Excel / CSV
     */
    public static function importSiswa(UploadedFile $file): int
    {
        self::loadLibraries();
        $filePath = $file->getRealPath();
        $count = 0;

        if (class_exists('Shuchkin\SimpleXLSX') && $xlsx = \Shuchkin\SimpleXLSX::parse($filePath)) {
            $rows = $xlsx->rows();
            for ($i = 1; $i < count($rows); $i++) {
                $row = $rows[$i];
                $username = trim($row[0] ?? '');
                $nama = trim($row[1] ?? '');
                $kelas = trim($row[2] ?? '');

                if (empty($username) || empty($nama)) {
                    continue;
                }

                $existing = User::where('username', $username)->first();
                $token = $existing ? $existing->token : strtoupper(Str::random(6));

                User::updateOrCreate(
                    ['username' => $username],
                    [
                        'nama_lengkap' => $nama,
                        'kelas' => $kelas,
                        'token' => $token,
                        'role_id' => 3,
                        'status_blokir' => '0',
                    ]
                );
                $count++;
            }
            return $count;
        }

        // Fallback parse CSV jika file bukan xlsx
        if (($handle = fopen($filePath, 'r')) !== false) {
            $i = 0;
            while (($row = fgetcsv($handle, 1000, ',')) !== false) {
                if ($i++ === 0) continue; // Skip header
                $username = trim($row[0] ?? '');
                $nama = trim($row[1] ?? '');
                $kelas = trim($row[2] ?? '');

                if (empty($username) || empty($nama)) {
                    continue;
                }

                $existing = User::where('username', $username)->first();
                $token = $existing ? $existing->token : strtoupper(Str::random(6));

                User::updateOrCreate(
                    ['username' => $username],
                    [
                        'nama_lengkap' => $nama,
                        'kelas' => $kelas,
                        'token' => $token,
                        'role_id' => 3,
                        'status_blokir' => '0',
                    ]
                );
                $count++;
            }
            fclose($handle);
        }

        return $count;
    }

    /**
     * Export Data Siswa ke Excel (.xlsx)
     */
    public static function exportSiswa(?string $kelas = null)
    {
        self::loadLibraries();

        $query = User::where('role_id', 3);
        if (!empty($kelas)) {
            $query->where('kelas', $kelas);
        }
        $students = $query->orderBy('kelas')->orderBy('nama_lengkap')->get();

        $data = [
            ['<b>NIS / Username</b>', '<b>Nama Lengkap</b>', '<b>Kelas</b>', '<b>Token Login</b>', '<b>Status Akun</b>'],
        ];

        foreach ($students as $s) {
            $data[] = [
                $s->username,
                $s->nama_lengkap,
                $s->kelas,
                $s->token,
                $s->status_blokir === '1' ? 'Terblokir' : 'Aktif',
            ];
        }

        $filename = 'Data_Siswa_' . ($kelas ? "Kelas_{$kelas}_" : 'Semua_') . date('Y-m-d') . '.xlsx';

        if (class_exists('Shuchkin\SimpleXLSXGen')) {
            \Shuchkin\SimpleXLSXGen::fromArray($data)->downloadAs($filename);
            exit;
        }

        return self::downloadCsv(str_replace('.xlsx', '.csv', $filename), $data);
    }

    /**
     * Template Import Soal (.xlsx)
     */
    public static function templateSoal()
    {
        self::loadLibraries();

        $data = [
            ['<b>Pertanyaan</b>', '<b>Pilihan A</b>', '<b>Pilihan B</b>', '<b>Pilihan C</b>', '<b>Pilihan D</b>', '<b>Pilihan E</b>', '<b>Kunci (A/B/C/D/E)</b>'],
            ['Ibu kota negara Indonesia yang baru bernama...', 'Jakarta', 'Surabaya', 'Nusantara', 'Bandung', 'Medan', 'C'],
            ['Lambang sila ketiga Pancasila adalah...', 'Bintang', 'Rantai', 'Pohon Beringin', 'Kepala Banteng', 'Padi dan Kapas', 'C'],
        ];

        if (class_exists('Shuchkin\SimpleXLSXGen')) {
            \Shuchkin\SimpleXLSXGen::fromArray($data)->downloadAs('Template_Import_Soal_CBT.xlsx');
            exit;
        }

        return self::downloadCsv('Template_Import_Soal_CBT.csv', [
            ['Pertanyaan', 'Pilihan A', 'Pilihan B', 'Pilihan C', 'Pilihan D', 'Pilihan E', 'Kunci (A/B/C/D/E)'],
            ['Ibu kota negara Indonesia yang baru bernama...', 'Jakarta', 'Surabaya', 'Nusantara', 'Bandung', 'Medan', 'C'],
        ]);
    }

    /**
     * Import Butir Soal ke Bank Soal
     */
    public static function importSoal(int $bankSoalId, UploadedFile $file): int
    {
        self::loadLibraries();
        $bank = BankSoal::findOrFail($bankSoalId);
        $filePath = $file->getRealPath();
        $count = 0;

        if (class_exists('Shuchkin\SimpleXLSX') && $xlsx = \Shuchkin\SimpleXLSX::parse($filePath)) {
            $rows = $xlsx->rows();
            for ($i = 1; $i < count($rows); $i++) {
                $row = $rows[$i];
                $pertanyaan = trim($row[0] ?? '');
                $pilA = trim($row[1] ?? '');
                $pilB = trim($row[2] ?? '');
                $pilC = trim($row[3] ?? '');
                $pilD = trim($row[4] ?? '');
                $pilE = trim($row[5] ?? '');
                $kunci = strtoupper(trim($row[6] ?? 'A'));

                if (empty($pertanyaan) || empty($kunci)) {
                    continue;
                }

                Question::create([
                    'bank_soal_id' => $bank->id,
                    'subject_id' => $bank->subject_id,
                    'kelas' => $bank->kelas_rilis,
                    'pertanyaan' => $pertanyaan,
                    'pil_a' => $pilA,
                    'pil_b' => $pilB,
                    'pil_c' => $pilC,
                    'pil_d' => $pilD,
                    'pil_e' => $pilE,
                    'kunci_jawaban' => in_array($kunci, ['A', 'B', 'C', 'D', 'E']) ? $kunci : 'A',
                    'urutan' => 0,
                ]);
                $count++;
            }
            return $count;
        }

        // CSV Fallback
        if (($handle = fopen($filePath, 'r')) !== false) {
            $i = 0;
            while (($row = fgetcsv($handle, 2000, ',')) !== false) {
                if ($i++ === 0) continue;
                $pertanyaan = trim($row[0] ?? '');
                $kunci = strtoupper(trim($row[6] ?? 'A'));

                if (empty($pertanyaan) || empty($kunci)) continue;

                Question::create([
                    'bank_soal_id' => $bank->id,
                    'subject_id' => $bank->subject_id,
                    'kelas' => $bank->kelas_rilis,
                    'pertanyaan' => $pertanyaan,
                    'pil_a' => trim($row[1] ?? ''),
                    'pil_b' => trim($row[2] ?? ''),
                    'pil_c' => trim($row[3] ?? ''),
                    'pil_d' => trim($row[4] ?? ''),
                    'pil_e' => trim($row[5] ?? ''),
                    'kunci_jawaban' => in_array($kunci, ['A', 'B', 'C', 'D', 'E']) ? $kunci : 'A',
                    'urutan' => 0,
                ]);
                $count++;
            }
            fclose($handle);
        }

        return $count;
    }

    /**
     * Export Butir Soal di Bank Soal ke Excel
     */
    public static function exportSoalExcel(int $bankSoalId)
    {
        self::loadLibraries();
        $bank = BankSoal::with('subject', 'guru')->findOrFail($bankSoalId);
        $questions = Question::where('bank_soal_id', $bankSoalId)
            ->orderByRaw('CASE WHEN urutan > 0 THEN urutan ELSE 9999999 END ASC')
            ->orderBy('id')
            ->get();

        $data = [
            ['<b>Pertanyaan</b>', '<b>Pilihan A</b>', '<b>Pilihan B</b>', '<b>Pilihan C</b>', '<b>Pilihan D</b>', '<b>Pilihan E</b>', '<b>Kunci (A/B/C/D/E)</b>'],
        ];

        foreach ($questions as $q) {
            $data[] = [
                $q->pertanyaan,
                $q->pil_a,
                $q->pil_b,
                $q->pil_c,
                $q->pil_d,
                $q->pil_e,
                strtoupper($q->kunci_jawaban ?? 'A'),
            ];
        }

        $safeName = Str::slug($bank->nama_bank, '_');
        $filename = "Bank_Soal_{$safeName}_" . date('Y-m-d') . '.xlsx';

        if (class_exists('Shuchkin\SimpleXLSXGen')) {
            \Shuchkin\SimpleXLSXGen::fromArray($data)->downloadAs($filename);
            exit;
        }

        return self::downloadCsv(str_replace('.xlsx', '.csv', $filename), $data);
    }

    /**
     * Export Hasil Ujian Excel Lengkap & Analisis Butir Soal (Identik dengan espega_old)
     * Menampilkan rekap nilai, jawaban siswa per butir soal, dan analisis tingkat kesukaran
     */
    public static function exportHasilUjianLengkap(?int $examId = null, ?string $kelas = null)
    {
        self::loadLibraries();

        $targetKelas = trim($kelas ?? '');

        // 1. Ambil Informasi Ujian & Mata Pelajaran
        $exam = null;
        $subjectId = null;
        $namaMapel = 'Mata Pelajaran';
        $namaUjian = 'Ujian CBT';

        if (!empty($examId)) {
            $exam = Exam::with('subject')->find($examId);
            if ($exam) {
                $subjectId = $exam->subject_id;
                $namaMapel = $exam->subject->nama_mapel ?? 'Mata Pelajaran';
                $namaUjian = $exam->nama_ujian;
            }
        }

        // 2. Ambil Sesi Ujian Siswa
        $query = ExamSession::with(['user', 'exam.subject', 'answers.question']);

        if (!empty($examId)) {
            $query->where('exam_id', $examId);
        }

        if ($targetKelas !== '') {
            $query->whereHas('user', function ($uq) use ($targetKelas) {
                $uq->where('kelas', $targetKelas);
            });
        }

        $results = $query->get()->sortBy([
            ['user.kelas', 'asc'],
            ['user.nama_lengkap', 'asc'],
        ]);

        if (!$subjectId && $results->isNotEmpty()) {
            $firstSession = $results->first();
            $subjectId = $firstSession->exam->subject_id ?? null;
            $namaMapel = $firstSession->exam->subject->nama_mapel ?? 'Mata Pelajaran';
            $namaUjian = $firstSession->exam->nama_ujian ?? 'Ujian CBT';
        }

        // 3. Ambil Soal yang Benar-Benar Dikerjakan Siswa dalam Sesi Ujian Ini
        // Jangan mengambil seluruh soal yang dimiliki guru di mata pelajaran tersebut
        $sessionIds = $results->pluck('id')->toArray();
        $answeredQuestionIds = [];
        if (!empty($sessionIds)) {
            $answeredQuestionIds = ExamAnswer::whereIn('session_id', $sessionIds)
                ->whereNotNull('question_id')
                ->distinct()
                ->pluck('question_id')
                ->toArray();
        }

        if (!empty($answeredQuestionIds)) {
            // HANYA ambil butir soal yang benar-benar dikerjakan oleh siswa pada sesi-sesi ujian ini
            $allQuestions = Question::whereIn('id', $answeredQuestionIds)
                ->orderByRaw('(CASE WHEN urutan > 0 THEN urutan ELSE 9999999 END) ASC, id ASC')
                ->get();
        } else {
            // Fallback jika belum ada siswa yang mengerjakan atau jawaban belum tersimpan, ambil paket soal rilis yang relevan
            $targetKelas = $exam?->target_kelas;
            $allQuestionsQuery = Question::query();
            if ($subjectId) {
                $allQuestionsQuery->where('subject_id', $subjectId);
            }
            if (!empty($targetKelas)) {
                $allQuestionsQuery->where(function ($query) use ($targetKelas) {
                    $query->whereNull('bank_soal_id')
                        ->orWhereHas('bankSoal', function ($bQuery) use ($targetKelas) {
                            $bQuery->where('status_rilis', 'rilis')
                                ->where(function ($sub) use ($targetKelas) {
                                    $sub->whereNull('kelas_rilis')
                                        ->orWhere('kelas_rilis', 'LIKE', "%$targetKelas%");
                                });
                        });
                })->where(function ($query) use ($targetKelas) {
                    $query->whereNull('kelas')
                        ->orWhere('kelas', 'LIKE', "%$targetKelas%")
                        ->orWhere('kelas', 'ALL')
                        ->orWhere('kelas', 'Semua Kelas');
                });
            }
            $allQuestions = $allQuestionsQuery
                ->orderByRaw('(CASE WHEN urutan > 0 THEN urutan ELSE 9999999 END) ASC, id ASC')
                ->get();
        }

        // Helper untuk mengambil daftar butir soal yang spesifik dikerjakan oleh kelas / siswa tertentu
        $getClassQuestions = function ($kls, $studentsInClass = null) use (&$allQuestions) {
            $klsUpper = strtoupper(trim($kls));

            // Jika daftar siswa diberikan, ambil soal yang secara spesifik dikerjakan oleh siswa dalam kelompok ini
            if (!empty($studentsInClass) && (is_array($studentsInClass) ? count($studentsInClass) > 0 : $studentsInClass->isNotEmpty())) {
                $cSessionIds = collect($studentsInClass)->pluck('id')->toArray();
                $cQids = ExamAnswer::whereIn('session_id', $cSessionIds)
                    ->whereNotNull('question_id')
                    ->distinct()
                    ->pluck('question_id')
                    ->toArray();

                if (!empty($cQids)) {
                    $classSpecificQuestions = $allQuestions->whereIn('id', $cQids)->values();
                    if ($classSpecificQuestions->isNotEmpty()) {
                        return $classSpecificQuestions;
                    }
                }
            }

            // Fallback jika tidak ada data sesi spesifik, filter dari $allQuestions berdasarkan atribut kelas
            $filtered = $allQuestions->filter(function ($q) use ($klsUpper) {
                $qKelas = strtoupper(trim($q->kelas ?? ''));
                return $qKelas === $klsUpper
                    || $qKelas === ''
                    || $qKelas === 'UMUM'
                    || $qKelas === 'ALL'
                    || $qKelas === 'SEMUA KELAS'
                    || str_contains($qKelas, $klsUpper);
            })->values();

            return $filtered->isNotEmpty() ? $filtered : $allQuestions;
        };

        // 4. Map Jawaban Siswa dari exam_answers
        $answersMap = [];
        foreach ($results as $s) {
            foreach ($s->answers as $ans) {
                $answersMap[$s->id][$ans->question_id] = [
                    'jawaban_siswa' => $ans->jawaban_siswa,
                    'is_correct' => $ans->is_correct,
                ];
            }
        }

        // 5. Kalkulasi Jawaban, Benar, Salah, Kosong, Skor, dan Statistik
        $calcDetail = [];
        $calcBenar = [];
        $calcSalah = [];
        $calcKosong = [];
        $calcTotalSoal = [];
        $calcSkor = [];

        $statBenarByClass = [];
        $statSalahByClass = [];
        $statKosongByClass = [];

        foreach ($results as $row) {
            $sid = $row->id;
            $user = $row->user;
            $sKelas = strtoupper(trim($user->kelas ?? ($row->exam->target_kelas ?? '')));
            $sQuestions = $getClassQuestions($sKelas, collect([$row]));
            $totalSoalSiswa = count($sQuestions);

            $jmlB = 0;
            $jmlS = 0;
            $jmlK = 0;

            foreach ($sQuestions as $q) {
                $qid = $q->id;
                $kunci = strtoupper(trim($q->kunci_jawaban ?? ''));

                $hasAns = isset($answersMap[$sid][$qid]);
                $jwbRaw = $hasAns ? trim($answersMap[$sid][$qid]['jawaban_siswa'] ?? '') : '';
                $jwb = strtoupper($jwbRaw);

                if ($jwb !== '' && $jwb !== '-') {
                    if ($jwb === $kunci) {
                        $jmlB++;
                        $st = 'Benar';
                        $statBenarByClass[$sKelas][$qid] = ($statBenarByClass[$sKelas][$qid] ?? 0) + 1;
                    } else {
                        $jmlS++;
                        $st = 'Salah';
                        $statSalahByClass[$sKelas][$qid] = ($statSalahByClass[$sKelas][$qid] ?? 0) + 1;
                    }
                } else {
                    $jwb = '-';
                    $jmlK++;
                    $st = 'Kosong';
                    $statKosongByClass[$sKelas][$qid] = ($statKosongByClass[$sKelas][$qid] ?? 0) + 1;
                }

                $calcDetail[$sid][$qid] = [
                    'jawaban' => $jwb,
                    'status' => $st,
                ];
            }

            $calcBenar[$sid] = $jmlB;
            $calcSalah[$sid] = $jmlS;
            $calcKosong[$sid] = $jmlK;
            $calcTotalSoal[$sid] = $totalSoalSiswa;

            if ($row->status_siswa === 'selesai' && isset($row->skor) && is_numeric($row->skor) && $row->skor > 0) {
                $calcSkor[$sid] = round((float)$row->skor, 1);
            } else {
                $calcSkor[$sid] = ($totalSoalSiswa > 0) ? round(($jmlB / $totalSoalSiswa) * 100, 1) : 0;
            }
        }

        // Helper function untuk membangun sheet tabel per kelas (Rekap dan Koreksi)
        $buildClassSheet = function ($namaSheetTitle, $kelasName, $studentsInClass, $classQuestions)
            use (&$calcDetail, &$calcBenar, &$calcSalah, &$calcKosong, &$calcSkor,
                 &$statBenarByClass, &$statSalahByClass, &$statKosongByClass, $namaMapel) {

            $sheet = [];
            $sheet[] = ['<b><style font-size="14">' . $namaSheetTitle . '</style></b>'];
            $sheet[] = [
                '<b>Mata Pelajaran:</b> ' . htmlspecialchars($namaMapel) .
                ' | <b>Kelas:</b> ' . htmlspecialchars($kelasName) .
                ' | <b>Tanggal Export:</b> ' . date('d-m-Y H:i') . ' WIB' .
                ' | <b>Total Siswa:</b> ' . count($studentsInClass) .
                ' | <b>Total Soal:</b> ' . count($classQuestions)
            ];
            $sheet[] = []; // Baris pemisah

            $header = [
                '<style bgcolor="#1e293b" color="#ffffff"><b><center>No</center></b></style>',
                '<style bgcolor="#1e293b" color="#ffffff"><b><center>NIS / Username</center></b></style>',
                '<style bgcolor="#1e293b" color="#ffffff"><b><center>Nama Lengkap Siswa</center></b></style>',
                '<style bgcolor="#1e293b" color="#ffffff"><b><center>Kelas</center></b></style>',
                '<style bgcolor="#1e293b" color="#ffffff"><b><center>Nama Ujian</center></b></style>',
                '<style bgcolor="#1e293b" color="#ffffff"><b><center>Jml Soal</center></b></style>',
                '<style bgcolor="#1e293b" color="#ffffff"><b><center>Benar</center></b></style>',
                '<style bgcolor="#1e293b" color="#ffffff"><b><center>Salah</center></b></style>',
                '<style bgcolor="#1e293b" color="#ffffff"><b><center>Kosong</center></b></style>',
                '<style bgcolor="#1e293b" color="#ffffff"><b><center>Nilai Akhir</center></b></style>',
                '<style bgcolor="#1e293b" color="#ffffff"><b><center>Status</center></b></style>'
            ];
            foreach ($classQuestions as $idx => $q) {
                $noS = $idx + 1;
                $kunci = strtoupper(trim($q->kunci_jawaban ?? '-'));
                $header[] = '<style bgcolor="#334155" color="#ffffff"><b><center>Soal ' . $noS . '<br>[Kunci: ' . $kunci . ']</center></b></style>';
            }
            $sheet[] = $header;

            if (!empty($classQuestions)) {
                // Baris Kunci Jawaban
                $rowKunci = [
                    '<style bgcolor="#fef08a" color="#854d0e"><center><b>-</b></center></style>',
                    '<style bgcolor="#fef08a" color="#854d0e"><center><b>-</b></center></style>',
                    '<style bgcolor="#fef08a" color="#854d0e"><b>KUNCI JAWABAN RESMI</b></style>',
                    '<style bgcolor="#fef08a" color="#854d0e"><center><b>' . htmlspecialchars($kelasName) . '</b></center></style>',
                    '<style bgcolor="#fef08a" color="#854d0e"><center><b>-</b></center></style>',
                    '<style bgcolor="#fef08a" color="#854d0e"><center><b>' . count($classQuestions) . '</b></center></style>',
                    '<style bgcolor="#fef08a" color="#854d0e"><center><b>-</b></center></style>',
                    '<style bgcolor="#fef08a" color="#854d0e"><center><b>-</b></center></style>',
                    '<style bgcolor="#fef08a" color="#854d0e"><center><b>-</b></center></style>',
                    '<style bgcolor="#fef08a" color="#854d0e"><center><b>-</b></center></style>',
                    '<style bgcolor="#fef08a" color="#854d0e"><center><b>-</b></center></style>'
                ];
                foreach ($classQuestions as $q) {
                    $kunci = strtoupper(trim($q->kunci_jawaban ?? '-'));
                    $rowKunci[] = '<style bgcolor="#fef08a" color="#854d0e"><b><center>Kunci: ' . $kunci . '</center></b></style>';
                }
                $sheet[] = $rowKunci;
            }

            // Data Siswa
            $no = 1;
            foreach ($studentsInClass as $row) {
                $sid = $row->id;
                $user = $row->user;
                $statusLabel = match ($row->status_siswa) {
                    'selesai' => 'Selesai',
                    'terblokir' => 'Terblokir',
                    'pengerjaan' => 'Pengerjaan',
                    default => 'Belum Mulai',
                };
                $statusColor = ($row->status_siswa === 'selesai') ? '#15803d' : '#b45309';
                $statusBg = ($row->status_siswa === 'selesai') ? '#dcfce7' : '#fef3c7';

                $rowData = [
                    '<center>' . ($no++) . '</center>',
                    '<center>' . htmlspecialchars($user->username ?? '-') . '</center>',
                    htmlspecialchars($user->nama_lengkap ?? '-'),
                    '<center>' . htmlspecialchars($user->kelas ?? '-') . '</center>',
                    htmlspecialchars($row->exam->nama_ujian ?? '-'),
                    '<center>' . count($classQuestions) . '</center>',
                    '<style color="#15803d"><b><center>' . ($calcBenar[$sid] ?? 0) . '</center></b></style>',
                    '<style color="#b91c1c"><b><center>' . ($calcSalah[$sid] ?? 0) . '</center></b></style>',
                    '<style color="#64748b"><center>' . ($calcKosong[$sid] ?? 0) . '</center></style>',
                    '<style bgcolor="#e0e7ff" color="#4338ca"><b><center>' . ($calcSkor[$sid] ?? 0) . '</center></b></style>',
                    '<style bgcolor="' . $statusBg . '" color="' . $statusColor . '"><b><center>' . $statusLabel . '</center></b></style>'
                ];

                foreach ($classQuestions as $q) {
                    $qid = $q->id;
                    $detail = $calcDetail[$sid][$qid] ?? ['jawaban' => '-', 'status' => 'Kosong'];
                    $jwb = $detail['jawaban'];
                    $st = $detail['status'];

                    if ($st === 'Benar') {
                        $rowData[] = '<style bgcolor="#dcfce7" color="#166534"><b><center>' . $jwb . ' (Benar)</center></b></style>';
                    } elseif ($st === 'Salah') {
                        $rowData[] = '<style bgcolor="#fee2e2" color="#991b1b"><b><center>' . $jwb . ' (Salah)</center></b></style>';
                    } else {
                        $rowData[] = '<style bgcolor="#f1f5f9" color="#64748b"><center>- (Kosong)</center></style>';
                    }
                }
                $sheet[] = $rowData;
            }

            // Statistik Bawah Sheet Kelas (Tingkat Kesukaran & Ketuntasan)
            $nSiswa = count($studentsInClass);
            $kKey = strtoupper(trim($kelasName));
            if ($nSiswa > 0 && !empty($classQuestions)) {
                $sheet[] = []; // spasi pemisah

                $rowB = ['', '', '<b><style bgcolor="#e2e8f0" color="#1e293b">TOTAL SISWA JAWAB BENAR (B)</style></b>', '', '', '', '', '', '', '', ''];
                foreach ($classQuestions as $q) {
                    $qid = $q->id;
                    $bVal = $statBenarByClass[$kKey][$qid] ?? 0;
                    $rowB[] = '<style bgcolor="#dcfce7" color="#166534"><b><center>' . $bVal . '</center></b></style>';
                }
                $sheet[] = $rowB;

                $rowS = ['', '', '<b><style bgcolor="#e2e8f0" color="#1e293b">TOTAL SISWA JAWAB SALAH / KOSONG (S)</style></b>', '', '', '', '', '', '', '', ''];
                foreach ($classQuestions as $q) {
                    $qid = $q->id;
                    $sVal = ($statSalahByClass[$kKey][$qid] ?? 0) + ($statKosongByClass[$kKey][$qid] ?? 0);
                    $rowS[] = '<style bgcolor="#fee2e2" color="#991b1b"><b><center>' . $sVal . '</center></b></style>';
                }
                $sheet[] = $rowS;

                $rowP = ['', '', '<b><style bgcolor="#e2e8f0" color="#1e293b">PERSENTASE KETUNTASAN SOAL (%)</style></b>', '', '', '', '', '', '', '', ''];
                foreach ($classQuestions as $q) {
                    $qid = $q->id;
                    $bVal = $statBenarByClass[$kKey][$qid] ?? 0;
                    $pct = round(($bVal / $nSiswa) * 100, 1);
                    $rowP[] = '<style bgcolor="#e0e7ff" color="#3730a3"><b><center>' . $pct . '%</center></b></style>';
                }
                $sheet[] = $rowP;

                $rowDiff = ['', '', '<b><style bgcolor="#e2e8f0" color="#1e293b">INDEKS KESUKARAN (P = B / N)</style></b>', '', '', '', '', '', '', '', ''];
                foreach ($classQuestions as $q) {
                    $qid = $q->id;
                    $bVal = $statBenarByClass[$kKey][$qid] ?? 0;
                    $pVal = round($bVal / $nSiswa, 2);
                    $rowDiff[] = '<style bgcolor="#f1f5f9" color="#1e293b"><b><center>' . number_format($pVal, 2) . '</center></b></style>';
                }
                $sheet[] = $rowDiff;

                $rowKat = ['', '', '<b><style bgcolor="#e2e8f0" color="#1e293b">KATEGORI TINGKAT KESUKARAN</style></b>', '', '', '', '', '', '', '', ''];
                foreach ($classQuestions as $q) {
                    $qid = $q->id;
                    $bVal = $statBenarByClass[$kKey][$qid] ?? 0;
                    $pVal = $bVal / $nSiswa;
                    if ($pVal >= 0.70) {
                        $kat = 'Mudah'; $bg = '#dcfce7'; $col = '#15803d';
                    } elseif ($pVal >= 0.30) {
                        $kat = 'Sedang'; $bg = '#fef9c3'; $col = '#854d0e';
                    } else {
                        $kat = 'Sukar'; $bg = '#fee2e2'; $col = '#b91c1c';
                    }
                    $rowKat[] = '<style bgcolor="' . $bg . '" color="' . $col . '"><b><center>' . $kat . '</center></b></style>';
                }
                $sheet[] = $rowKat;
            }

            return $sheet;
        };

        // Identifikasi Daftar Kelas dari Hasil Ujian
        $distinctClasses = [];
        foreach ($results as $r) {
            $k = trim($r->user->kelas ?? ($r->exam->target_kelas ?? ''));
            if ($k !== '' && !in_array($k, $distinctClasses)) {
                $distinctClasses[] = $k;
            }
        }
        sort($distinctClasses);

        $studentsByClass = [];
        foreach ($results as $r) {
            $k = trim($r->user->kelas ?? ($r->exam->target_kelas ?? ''));
            $studentsByClass[$k][] = $r;
        }

        if (class_exists('Shuchkin\SimpleXLSXGen')) {
            $xlsx = new \Shuchkin\SimpleXLSXGen();

            // Cek apakah mode single class atau multi-class
            $isSingleClass = ($targetKelas !== '') || (count($distinctClasses) <= 1);
            $effectiveClass = $targetKelas !== '' ? $targetKelas : ($distinctClasses[0] ?? ($exam->target_kelas ?? 'Umum'));

            if ($isSingleClass) {
                // ==========================================
                // KASUS 1: 1 Kelas Tertentu (3 Sheet Lengkap: Rekap, Jawaban Siswa, Analisis Butir Soal)
                // ==========================================
                $filteredStudents = $targetKelas !== '' ? $results : ($studentsByClass[$effectiveClass] ?? $results);
                $cQuestions = $getClassQuestions($effectiveClass, $filteredStudents);

                // Sheet 1: Rekap dan Koreksi Jawaban Kelas Tersebut
                $s1 = $buildClassSheet(
                    'REKAPITULASI HASIL UJIAN & ANALISIS JAWABAN - KELAS ' . htmlspecialchars($effectiveClass),
                    $effectiveClass,
                    $filteredStudents,
                    $cQuestions
                );
                $xlsx->addSheet($s1, 'Rekap dan Koreksi');

                // Sheet 2: Matriks Jawaban Siswa (Huruf A-B-C-D-E)
                $sheet2 = [];
                $sheet2[] = ['<b><style font-size="13">MATRIKS JAWABAN SISWA (PILIHAN GANDA) - KELAS ' . htmlspecialchars($effectiveClass) . '</style></b>'];
                $sheet2[] = ['Mata Pelajaran: ' . htmlspecialchars($namaMapel) . ' | Kelas: ' . htmlspecialchars($effectiveClass)];
                $sheet2[] = [];
                $h2 = [
                    '<style bgcolor="#1e293b" color="#ffffff"><b><center>No</center></b></style>',
                    '<style bgcolor="#1e293b" color="#ffffff"><b><center>NIS</center></b></style>',
                    '<style bgcolor="#1e293b" color="#ffffff"><b><center>Nama Siswa</center></b></style>',
                    '<style bgcolor="#1e293b" color="#ffffff"><b><center>Kelas</center></b></style>'
                ];
                foreach ($cQuestions as $idx => $q) {
                    $h2[] = '<style bgcolor="#334155" color="#ffffff"><b><center>S' . ($idx + 1) . '</center></b></style>';
                }
                $sheet2[] = $h2;

                if (!empty($cQuestions)) {
                    $k2 = ['', '', '<style bgcolor="#fef08a" color="#854d0e"><b>KUNCI</b></style>', ''];
                    foreach ($cQuestions as $q) {
                        $k2[] = '<style bgcolor="#fef08a" color="#854d0e"><b><center>' . strtoupper(trim($q->kunci_jawaban ?? '-')) . '</center></b></style>';
                    }
                    $sheet2[] = $k2;
                }

                $no2 = 1;
                foreach ($filteredStudents as $row) {
                    $sid = $row->id;
                    $user = $row->user;
                    $r2 = [
                        '<center>' . ($no2++) . '</center>',
                        '<center>' . htmlspecialchars($user->username ?? '-') . '</center>',
                        htmlspecialchars($user->nama_lengkap ?? '-'),
                        '<center>' . htmlspecialchars($user->kelas ?? '-') . '</center>'
                    ];
                    foreach ($cQuestions as $q) {
                        $qid = $q->id;
                        $jwb = $calcDetail[$sid][$qid]['jawaban'] ?? '-';
                        $st = $calcDetail[$sid][$qid]['status'] ?? 'Kosong';
                        if ($st === 'Benar') {
                            $r2[] = '<style color="#15803d"><b><center>' . $jwb . '</center></b></style>';
                        } else {
                            $r2[] = '<style color="#b91c1c"><center>' . $jwb . '</center></style>';
                        }
                    }
                    $sheet2[] = $r2;
                }
                if (!empty($cQuestions)) {
                    $xlsx->addSheet($sheet2, 'Jawaban Siswa');
                }

                // Sheet 3: Analisis Butir Soal (Matriks 1-0)
                $sheet3 = [];
                $sheet3[] = ['<b><style font-size="13">ANALISIS BUTIR SOAL & TINGKAT KESUKARAN (SKOR 1 / 0) - KELAS ' . htmlspecialchars($effectiveClass) . '</style></b>'];
                $sheet3[] = ['Mata Pelajaran: ' . htmlspecialchars($namaMapel) . ' | Kelas: ' . htmlspecialchars($effectiveClass)];
                $sheet3[] = [];
                $h3 = [
                    '<style bgcolor="#1e293b" color="#ffffff"><b><center>No</center></b></style>',
                    '<style bgcolor="#1e293b" color="#ffffff"><b><center>NIS</center></b></style>',
                    '<style bgcolor="#1e293b" color="#ffffff"><b><center>Nama Siswa</center></b></style>',
                    '<style bgcolor="#1e293b" color="#ffffff"><b><center>Kelas</center></b></style>',
                    '<style bgcolor="#1e293b" color="#ffffff"><b><center>Total Benar</center></b></style>',
                    '<style bgcolor="#1e293b" color="#ffffff"><b><center>Total Salah</center></b></style>',
                    '<style bgcolor="#1e293b" color="#ffffff"><b><center>Nilai</center></b></style>'
                ];
                foreach ($cQuestions as $idx => $q) {
                    $h3[] = '<style bgcolor="#334155" color="#ffffff"><b><center>S' . ($idx + 1) . '</center></b></style>';
                }
                $sheet3[] = $h3;

                $no3 = 1;
                foreach ($filteredStudents as $row) {
                    $sid = $row->id;
                    $user = $row->user;
                    $r3 = [
                        '<center>' . ($no3++) . '</center>',
                        '<center>' . htmlspecialchars($user->username ?? '-') . '</center>',
                        htmlspecialchars($user->nama_lengkap ?? '-'),
                        '<center>' . htmlspecialchars($user->kelas ?? '-') . '</center>',
                        '<style color="#15803d"><b><center>' . ($calcBenar[$sid] ?? 0) . '</center></b></style>',
                        '<style color="#b91c1c"><b><center>' . (($calcSalah[$sid] ?? 0) + ($calcKosong[$sid] ?? 0)) . '</center></b></style>',
                        '<style bgcolor="#e0e7ff" color="#4338ca"><b><center>' . ($calcSkor[$sid] ?? 0) . '</center></b></style>'
                    ];
                    foreach ($cQuestions as $q) {
                        $qid = $q->id;
                        $st = $calcDetail[$sid][$qid]['status'] ?? 'Kosong';
                        if ($st === 'Benar') {
                            $r3[] = '<style bgcolor="#dcfce7" color="#15803d"><b><center>1</center></b></style>';
                        } else {
                            $r3[] = '<style bgcolor="#fee2e2" color="#b91c1c"><center>0</center></style>';
                        }
                    }
                    $sheet3[] = $r3;
                }

                // Tambahkan ringkasan analisis di bagian bawah Sheet 3
                $n3 = count($filteredStudents);
                if ($n3 > 0 && !empty($cQuestions)) {
                    $sheet3[] = [];
                    $kKey = strtoupper(trim($effectiveClass));

                    $r3B = ['', '', '<b><style bgcolor="#e2e8f0" color="#1e293b">TOTAL BENAR (1)</style></b>', '', '', '', ''];
                    foreach ($cQuestions as $q) {
                        $qid = $q->id;
                        $bVal = $statBenarByClass[$kKey][$qid] ?? 0;
                        $r3B[] = '<style bgcolor="#dcfce7" color="#15803d"><b><center>' . $bVal . '</center></b></style>';
                    }
                    $sheet3[] = $r3B;

                    $r3Diff = ['', '', '<b><style bgcolor="#e2e8f0" color="#1e293b">INDEKS KESUKARAN (P = B / N)</style></b>', '', '', '', ''];
                    foreach ($cQuestions as $q) {
                        $qid = $q->id;
                        $bVal = $statBenarByClass[$kKey][$qid] ?? 0;
                        $pVal = round($bVal / $n3, 2);
                        $r3Diff[] = '<style bgcolor="#f1f5f9" color="#1e293b"><b><center>' . number_format($pVal, 2) . '</center></b></style>';
                    }
                    $sheet3[] = $r3Diff;

                    $r3Kat = ['', '', '<b><style bgcolor="#e2e8f0" color="#1e293b">KATEGORI TINGKAT KESUKARAN</style></b>', '', '', '', ''];
                    foreach ($cQuestions as $q) {
                        $qid = $q->id;
                        $bVal = $statBenarByClass[$kKey][$qid] ?? 0;
                        $pVal = $bVal / $n3;
                        if ($pVal >= 0.70) {
                            $kat = 'Mudah'; $bg = '#dcfce7'; $col = '#15803d';
                        } elseif ($pVal >= 0.30) {
                            $kat = 'Sedang'; $bg = '#fef9c3'; $col = '#854d0e';
                        } else {
                            $kat = 'Sukar'; $bg = '#fee2e2'; $col = '#b91c1c';
                        }
                        $r3Kat[] = '<style bgcolor="' . $bg . '" color="' . $col . '"><b><center>' . $kat . '</center></b></style>';
                    }
                    $sheet3[] = $r3Kat;
                }

                if (!empty($cQuestions)) {
                    $xlsx->addSheet($sheet3, 'Analisis Butir Soal');
                }

                $cleanTitle = Str::slug($namaMapel . '_' . $effectiveClass, '_');
                $namaFile = 'Rekap_Nilai_' . $cleanTitle . '_' . date('Y-m-d_H-i') . '.xlsx';

            } else {
                // ==========================================
                // KASUS 2: Download Semua Kelas (Banyak Rombel)
                // ==========================================
                // SHEET 1: REKAPITULASI SEMUA SISWA
                $sheetRekapAll = [];
                $sheetRekapAll[] = ['<b><style font-size="14">REKAPITULASI NILAI HASIL UJIAN (SEMUA KELAS)</style></b>'];
                $sheetRekapAll[] = [
                    '<b>Mata Pelajaran:</b> ' . htmlspecialchars($namaMapel) .
                    ' | <b>Tanggal Export:</b> ' . date('d-m-Y H:i') . ' WIB' .
                    ' | <b>Total Siswa:</b> ' . count($results) .
                    ' | <b>Total Kelas:</b> ' . count($distinctClasses)
                ];
                $sheetRekapAll[] = [];

                $hAll = [
                    '<style bgcolor="#1e293b" color="#ffffff"><b><center>No</center></b></style>',
                    '<style bgcolor="#1e293b" color="#ffffff"><b><center>NIS / Username</center></b></style>',
                    '<style bgcolor="#1e293b" color="#ffffff"><b><center>Nama Lengkap Siswa</center></b></style>',
                    '<style bgcolor="#1e293b" color="#ffffff"><b><center>Kelas</center></b></style>',
                    '<style bgcolor="#1e293b" color="#ffffff"><b><center>Nama Ujian</center></b></style>',
                    '<style bgcolor="#1e293b" color="#ffffff"><b><center>Total Soal</center></b></style>',
                    '<style bgcolor="#1e293b" color="#ffffff"><b><center>Benar</center></b></style>',
                    '<style bgcolor="#1e293b" color="#ffffff"><b><center>Salah</center></b></style>',
                    '<style bgcolor="#1e293b" color="#ffffff"><b><center>Kosong</center></b></style>',
                    '<style bgcolor="#1e293b" color="#ffffff"><b><center>Nilai Akhir</center></b></style>',
                    '<style bgcolor="#1e293b" color="#ffffff"><b><center>Status</center></b></style>'
                ];
                $sheetRekapAll[] = $hAll;

                $noAll = 1;
                foreach ($results as $row) {
                    $sid = $row->id;
                    $user = $row->user;
                    $statusLabel = match ($row->status_siswa) {
                        'selesai' => 'Selesai',
                        'terblokir' => 'Terblokir',
                        'pengerjaan' => 'Pengerjaan',
                        default => 'Belum Mulai',
                    };
                    $statusColor = ($row->status_siswa === 'selesai') ? '#15803d' : '#b45309';
                    $statusBg = ($row->status_siswa === 'selesai') ? '#dcfce7' : '#fef3c7';

                    $sheetRekapAll[] = [
                        '<center>' . ($noAll++) . '</center>',
                        '<center>' . htmlspecialchars($user->username ?? '-') . '</center>',
                        htmlspecialchars($user->nama_lengkap ?? '-'),
                        '<center><b>' . htmlspecialchars($user->kelas ?? '-') . '</b></center>',
                        htmlspecialchars($row->exam->nama_ujian ?? '-'),
                        '<center>' . ($calcTotalSoal[$sid] ?? 0) . '</center>',
                        '<style color="#15803d"><b><center>' . ($calcBenar[$sid] ?? 0) . '</center></b></style>',
                        '<style color="#b91c1c"><b><center>' . ($calcSalah[$sid] ?? 0) . '</center></b></style>',
                        '<style color="#64748b"><center>' . ($calcKosong[$sid] ?? 0) . '</center></style>',
                        '<style bgcolor="#e0e7ff" color="#4338ca"><b><center>' . ($calcSkor[$sid] ?? 0) . '</center></b></style>',
                        '<style bgcolor="' . $statusBg . '" color="' . $statusColor . '"><b><center>' . $statusLabel . '</center></b></style>'
                    ];
                }
                $xlsx->addSheet($sheetRekapAll, 'Rekap Semua Siswa');

                // SHEET 2, 3, ...: SATU SHEET KHUSUS PER KELAS DENGAN SOAL, JAWABAN & ANALISIS TINGKAT KESUKARAN AKURAT
                foreach ($distinctClasses as $kls) {
                    $cStudents = $studentsByClass[$kls] ?? [];
                    $cQuestions = $getClassQuestions($kls, $cStudents);

                    $classSheet = $buildClassSheet(
                        'REKAPITULASI HASIL UJIAN & ANALISIS JAWABAN - KELAS ' . htmlspecialchars($kls),
                        $kls,
                        $cStudents,
                        $cQuestions
                    );
                    $cleanKls = preg_replace('/[\\\\\\/\\?\\*\\:\\[\\]]/', '', $kls);
                    $sheetName = mb_substr('Kelas ' . $cleanKls, 0, 31);
                    $xlsx->addSheet($classSheet, $sheetName);
                }

                $cleanTitle = Str::slug($namaMapel, '_');
                $namaFile = 'Rekap_Nilai_' . $cleanTitle . '_Semua_Kelas_' . date('Y-m-d_H-i') . '.xlsx';
            }

            $xlsx->downloadAs($namaFile);
            exit;
        }

        // Fallback CSV jika SimpleXLSXGen tidak tersedia
        $csvRows = [
            ['LAPORAN HASIL UJIAN', $namaMapel, $namaUjian],
            ['Tanggal Cetak: ' . date('d/m/Y H:i') . ' WIB'],
            [],
            ['No', 'NIS', 'Nama Lengkap', 'Kelas', 'Total Soal', 'Benar', 'Salah', 'Kosong', 'Nilai Akhir', 'Status']
        ];
        $noCsv = 1;
        foreach ($results as $row) {
            $sid = $row->id;
            $csvRows[] = [
                $noCsv++,
                $row->user->username ?? '-',
                $row->user->nama_lengkap ?? '-',
                $row->user->kelas ?? '-',
                $calcTotalSoal[$sid] ?? 0,
                $calcBenar[$sid] ?? 0,
                $calcSalah[$sid] ?? 0,
                $calcKosong[$sid] ?? 0,
                $calcSkor[$sid] ?? 0,
                $row->status_siswa ?? '-'
            ];
        }
        $filename = 'Rekap_Nilai_' . Str::slug($namaMapel, '_') . '_' . date('Y-m-d') . '.csv';
        return self::downloadCsv($filename, $csvRows);
    }

    /**
     * Fallback CSV stream generator
     */
    protected static function downloadCsv(string $filename, array $rows)
    {
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->stream(function () use ($rows) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM for UTF-8 in Excel
            foreach ($rows as $row) {
                $cleanRow = array_map(function ($val) {
                    return strip_tags((string)$val);
                }, (array)$row);
                fputcsv($handle, $cleanRow);
            }
            fclose($handle);
        }, 200, $headers);
    }
}
