<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Guru;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\Siswa;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes - Sistem Informasi SMP Negeri 3 Ngawen (Espega Sinau)
|--------------------------------------------------------------------------
*/

// Dynamic Stream Fallback for Legacy Question Images
Route::get('/assets/img/soal/{filename}', function ($filename) {
    $path = public_path('assets/img/soal/' . $filename);
    if (!File::exists($path)) {
        $path = base_path('espega_old/assets/img/soal/' . $filename);
    }
    if (File::exists($path)) {
        return response()->file($path);
    }
    abort(404);
})->where('filename', '.*');

// Route Logo Sekolah Resmi SMPN 3 Ngawen
Route::get('/assets/img/logo-espega.png', function () {
    $uploaded = 'C:/Users/PC-ESPEGA CANDRA/.gemini/antigravity/brain/a1c0231b-f73a-4f48-848f-82565028b109/.user_uploaded/media_1794463450830_9c7c4028.jpg';
    $targetDir = public_path('assets/img');
    $target = $targetDir . '/logo-espega.png';
    if (!File::exists($target) && File::exists($uploaded)) {
        if (!File::isDirectory($targetDir)) {
            File::makeDirectory($targetDir, 0755, true);
        }
        File::copy($uploaded, $target);
    }
    if (File::exists($target)) {
        return response()->file($target);
    }
    if (File::exists($uploaded)) {
        return response()->file($uploaded);
    }
    abort(404);
});

// 1. Landing Page Profesional SMP Negeri 3 Ngawen & Berita Publik
Route::get('/', [LandingController::class, 'index'])->name('landing');
Route::get('/berita/{slug}', [LandingController::class, 'show'])->name('berita.show');
Route::post('/permohonan-informasi', [LandingController::class, 'storePermohonan'])->name('permohonan.store');
Route::post('/permohonan-informasi/cek', [LandingController::class, 'cekPermohonan'])->name('permohonan.cek');

// 2. Autentikasi Pengguna
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::match(['get', 'post'], '/logout', [LoginController::class, 'logout'])->name('logout');

// 3. Modul Administrator (SIAKAD & CBT Admin)
Route::prefix('admin')->name('admin.')->middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/dashboard', [Admin\DashboardController::class, 'index'])->name('dashboard');

    // Manajemen Tahun Ajaran & SIAKAD Sekolah
    Route::get('/academic-years', [Admin\AcademicYearController::class, 'index'])->name('academic_years.index');
    Route::post('/academic-years', [Admin\AcademicYearController::class, 'store'])->name('academic_years.store');
    Route::put('/academic-years/{academicYear}', [Admin\AcademicYearController::class, 'update'])->name('academic_years.update');
    Route::delete('/academic-years/{academicYear}', [Admin\AcademicYearController::class, 'destroy'])->name('academic_years.destroy');
    Route::post('/academic-years/{academicYear}/set-active', [Admin\AcademicYearController::class, 'setActive'])->name('academic_years.set_active');
    Route::post('/academic-years/kenaikan-kelas', [Admin\AcademicYearController::class, 'kenaikanKelas'])->name('academic_years.kenaikan_kelas');
    Route::get('/alumni', [Admin\AcademicYearController::class, 'alumni'])->name('alumni.index');

    // Manajemen Rombongan Belajar (Rombel / Kelas)
    Route::get('/rombels', [Admin\RombelController::class, 'index'])->name('rombels.index');
    Route::post('/rombels', [Admin\RombelController::class, 'store'])->name('rombels.store');
    Route::get('/rombels/{rombel}', [Admin\RombelController::class, 'show'])->name('rombels.show');
    Route::put('/rombels/{rombel}', [Admin\RombelController::class, 'update'])->name('rombels.update');
    Route::delete('/rombels/{rombel}', [Admin\RombelController::class, 'destroy'])->name('rombels.destroy');
    Route::post('/rombels/sync', [Admin\RombelController::class, 'syncFromStudents'])->name('rombels.sync');

    // Manajemen Mata Pelajaran (Mapel)
    Route::get('/subjects', [Admin\SubjectController::class, 'index'])->name('subjects.index');
    Route::post('/subjects', [Admin\SubjectController::class, 'store'])->name('subjects.store');
    Route::put('/subjects/{subject}', [Admin\SubjectController::class, 'update'])->name('subjects.update');
    Route::delete('/subjects/{subject}', [Admin\SubjectController::class, 'destroy'])->name('subjects.destroy');

    // Jadwal Ujian
    Route::get('/exams', [Admin\ExamController::class, 'index'])->name('exams.index');
    Route::post('/exams', [Admin\ExamController::class, 'store'])->name('exams.store');
    Route::put('/exams/{exam}', [Admin\ExamController::class, 'update'])->name('exams.update');
    Route::delete('/exams/{exam}', [Admin\ExamController::class, 'destroy'])->name('exams.destroy');
    Route::post('/exams/{exam}/regenerate-token', [Admin\ExamController::class, 'regenerateToken'])->name('exams.regenerate_token');

    // Sesi Ujian & Live Monitoring
    Route::get('/sessions', [Admin\ExamSessionController::class, 'index'])->name('sessions.index');
    Route::post('/sessions/bulk-action', [Admin\ExamSessionController::class, 'bulkAction'])->name('sessions.bulk_action');
    Route::post('/sessions/{session}/unblock', [Admin\ExamSessionController::class, 'unblock'])->name('sessions.unblock');
    Route::post('/sessions/{session}/force-submit', [Admin\ExamSessionController::class, 'forceSubmit'])->name('sessions.force_submit');
    Route::post('/sessions/{session}/reset', [Admin\ExamSessionController::class, 'resetSession'])->name('sessions.reset');
    Route::delete('/sessions/{session}', [Admin\ExamSessionController::class, 'destroy'])->name('sessions.destroy');

    // Bank Soal & Butir Soal Pusat
    Route::get('/bank-soal', [Admin\BankSoalController::class, 'index'])->name('bank_soal.index');
    Route::post('/bank-soal', [Admin\BankSoalController::class, 'storeBank'])->name('bank_soal.store');
    Route::post('/bank-soal/{bank}/toggle-rilis', [Admin\BankSoalController::class, 'toggleRilis'])->name('bank_soal.toggle_rilis');
    Route::delete('/bank-soal/{bank}', [Admin\BankSoalController::class, 'destroyBank'])->name('bank_soal.destroy');
    Route::post('/bank-soal/{bank}/acak-soal', [Admin\BankSoalController::class, 'acakSemuaSoal'])->name('bank_soal.acak_soal');
    Route::post('/bank-soal/{bank}/reset-urutan', [Admin\BankSoalController::class, 'resetUrutanSoal'])->name('bank_soal.reset_urutan');
    Route::post('/bank-soal/questions', [Admin\BankSoalController::class, 'storeQuestion'])->name('bank_soal.questions.store');
    Route::post('/bank-soal/questions/bulk-action', [Admin\BankSoalController::class, 'bulkActionQuestions'])->name('bank_soal.questions.bulk_action');
    Route::put('/bank-soal/questions/{question}', [Admin\BankSoalController::class, 'updateQuestion'])->name('bank_soal.questions.update');
    Route::delete('/bank-soal/questions/{question}', [Admin\BankSoalController::class, 'destroyQuestion'])->name('bank_soal.questions.destroy');

    // Manajemen Siswa (Import, Export, Cetak Kartu, Bulk Action)
    Route::get('/users/siswa', [Admin\UserController::class, 'siswa'])->name('users.siswa');
    Route::post('/users/siswa', [Admin\UserController::class, 'storeSiswa'])->name('users.store_siswa');
    Route::put('/users/siswa/{user}', [Admin\UserController::class, 'updateSiswa'])->name('users.update_siswa');
    Route::post('/users/siswa/import', [Admin\UserController::class, 'importSiswa'])->name('users.siswa.import');
    Route::get('/users/siswa/export', [Admin\UserController::class, 'exportSiswa'])->name('users.siswa.export');
    Route::get('/users/siswa/template', [Admin\UserController::class, 'templateSiswa'])->name('users.siswa.template');
    Route::get('/users/siswa/cetak-kartu', [Admin\UserController::class, 'cetakKartu'])->name('users.siswa.cetak_kartu');
    Route::post('/users/siswa/bulk-action', [Admin\UserController::class, 'bulkAction'])->name('users.siswa.bulk_action');

    // Administrasi lengkap dan arsip dokumen siswa
    Route::get('/student-administration', [Admin\StudentAdministrationController::class, 'index'])->name('student_administration.index');
    Route::get('/student-administration/{student}', [Admin\StudentAdministrationController::class, 'show'])->name('student_administration.show');
    Route::put('/student-administration/{student}', [Admin\StudentAdministrationController::class, 'update'])->name('student_administration.update');
    Route::post('/student-administration/{student}/documents', [Admin\StudentAdministrationController::class, 'storeDocument'])->name('student_administration.documents.store');
    Route::get('/student-administration/{student}/documents/{document}', [Admin\StudentAdministrationController::class, 'downloadDocument'])->name('student_administration.documents.download');
    Route::delete('/student-administration/{student}/documents/{document}', [Admin\StudentAdministrationController::class, 'destroyDocument'])->name('student_administration.documents.destroy');
    Route::post('/student-administration/document-types', [Admin\StudentAdministrationController::class, 'storeDocumentType'])->name('student_administration.document_types.store');

    // Manajemen Guru
    Route::get('/users/guru', [Admin\UserController::class, 'guru'])->name('users.guru');
    Route::post('/users/guru', [Admin\UserController::class, 'storeGuru'])->name('users.store_guru');
    Route::put('/users/guru/{user}', [Admin\UserController::class, 'updateGuru'])->name('users.update_guru');
    Route::delete('/users/guru/{user}', [Admin\UserController::class, 'destroyGuru'])->name('users.destroy_guru');
    Route::post('/users/{user}/reset-token', [Admin\UserController::class, 'resetToken'])->name('users.reset_token');
    Route::post('/users/{user}/toggle-blokir', [Admin\UserController::class, 'toggleBlokir'])->name('users.toggle_blokir');
    Route::delete('/users/{user}', [Admin\UserController::class, 'destroy'])->name('users.destroy');

    // Laporan & Nilai (Export Excel Lengkap)
    Route::get('/reports', [Admin\ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/export-excel', [Admin\ReportController::class, 'exportExcel'])->name('reports.export_excel');

    // Berita Harian & Buku Harian Sekolah
    Route::get('/berita', [Admin\BeritaController::class, 'index'])->name('berita.index');
    Route::post('/berita', [Admin\BeritaController::class, 'store'])->name('berita.store');
    Route::put('/berita/{berita}', [Admin\BeritaController::class, 'update'])->name('berita.update');
    Route::post('/berita/{berita}/toggle-web', [Admin\BeritaController::class, 'toggleWeb'])->name('berita.toggle_web');
    Route::delete('/berita/{berita}', [Admin\BeritaController::class, 'destroy'])->name('berita.destroy');

    // Layanan Permohonan Informasi & Pengaduan Publik
    Route::get('/permohonan-informasi', [Admin\PermohonanInformasiController::class, 'index'])->name('permohonan.index');
    Route::put('/permohonan-informasi/{permohonan}', [Admin\PermohonanInformasiController::class, 'update'])->name('permohonan.update');
    Route::delete('/permohonan-informasi/{permohonan}', [Admin\PermohonanInformasiController::class, 'destroy'])->name('permohonan.destroy');
});

// 4. Modul Guru Pengampu
Route::prefix('guru')->name('guru.')->middleware(['auth', 'role:guru'])->group(function () {
    Route::get('/dashboard', [Guru\DashboardController::class, 'index'])->name('dashboard');

    // Paket Bank Soal Guru
    Route::get('/bank-soal', [Guru\BankSoalController::class, 'index'])->name('bank_soal.index');
    Route::post('/bank-soal', [Guru\BankSoalController::class, 'store'])->name('bank_soal.store');
    Route::put('/bank-soal/{bank}', [Guru\BankSoalController::class, 'update'])->name('bank_soal.update');
    Route::delete('/bank-soal/{bank}', [Guru\BankSoalController::class, 'destroy'])->name('bank_soal.destroy');

    // Butir Soal per Paket (Import, Export Excel/PDF, Template, Acak, Bulk Action)
    Route::get('/bank-soal/{bank}/questions', [Guru\QuestionController::class, 'index'])->name('questions.index');
    Route::post('/bank-soal/{bank}/questions', [Guru\QuestionController::class, 'store'])->name('questions.store');
    Route::post('/bank-soal/{bank}/questions/import', [Guru\QuestionController::class, 'import'])->name('questions.import');
    Route::get('/bank-soal/{bank}/questions/export-excel', [Guru\QuestionController::class, 'exportExcel'])->name('questions.export_excel');
    Route::get('/bank-soal/{bank}/questions/export-pdf', [Guru\QuestionController::class, 'exportPdf'])->name('questions.export_pdf');
    Route::get('/questions/template', [Guru\QuestionController::class, 'template'])->name('questions.template');
    Route::post('/bank-soal/{bank}/questions/bulk-action', [Guru\QuestionController::class, 'bulkAction'])->name('questions.bulk_action');
    Route::post('/bank-soal/{bank}/questions/acak-semua', [Guru\QuestionController::class, 'acakSemua'])->name('questions.acak_semua');
    Route::post('/bank-soal/{bank}/questions/reset-urutan', [Guru\QuestionController::class, 'resetUrutan'])->name('questions.reset_urutan');
    Route::put('/questions/{question}', [Guru\QuestionController::class, 'update'])->name('questions.update');
    Route::delete('/questions/{question}', [Guru\QuestionController::class, 'destroy'])->name('questions.destroy');

    // Hasil & Nilai Siswa
    Route::get('/results', [Guru\ExamResultController::class, 'index'])->name('results.index');
    Route::get('/results/export-excel', [Guru\ExamResultController::class, 'exportExcel'])->name('results.export_excel');

    // Buku Harian Guru & Warta Sekolah
    Route::get('/berita', [Guru\BeritaController::class, 'index'])->name('berita.index');
    Route::post('/berita', [Guru\BeritaController::class, 'store'])->name('berita.store');
    Route::put('/berita/{berita}', [Guru\BeritaController::class, 'update'])->name('berita.update');
    Route::post('/berita/{berita}/toggle-web', [Guru\BeritaController::class, 'toggleWeb'])->name('berita.toggle_web');
    Route::delete('/berita/{berita}', [Guru\BeritaController::class, 'destroy'])->name('berita.destroy');
});

// 5. Modul Siswa (CBT Engine)
Route::prefix('siswa')->name('siswa.')->middleware(['auth', 'role:siswa'])->group(function () {
    Route::get('/dashboard', [Siswa\DashboardController::class, 'index'])->name('dashboard');

    // Ujian CBT
    Route::post('/exam/{exam}/verify-token', [Siswa\ExamController::class, 'verifyToken'])->name('exam.verify_token');
    Route::get('/exam/{exam}', [Siswa\ExamController::class, 'show'])->name('exam.show');
    Route::get('/exam/{exam}/get-soal', [Siswa\ExamController::class, 'getSoal'])->name('exam.get_soal');
    Route::post('/exam/{exam}/save-answer', [Siswa\ExamController::class, 'saveAnswer'])->name('exam.save_answer');
    Route::post('/exam/{exam}/update-timer', [Siswa\ExamController::class, 'updateTimer'])->name('exam.update_timer');
    Route::post('/exam/{exam}/submit', [Siswa\ExamController::class, 'submit'])->name('exam.submit');
    Route::get('/exam/{exam}/result', [Siswa\ExamController::class, 'result'])->name('exam.result');
    Route::post('/exam/{exam}/report-cheating', [Siswa\ExamController::class, 'reportCheating'])->name('exam.report_cheating');
});
