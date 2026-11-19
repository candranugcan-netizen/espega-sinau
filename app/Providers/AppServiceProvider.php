<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        try {
            // Sinkronisasi logo resmi SMPN 3 Ngawen ke folder public
            $uploadedLogo = 'C:/Users/PC-ESPEGA CANDRA/.gemini/antigravity/brain/a1c0231b-f73a-4f48-848f-82565028b109/.user_uploaded/media_1794463450830_9c7c4028.jpg';
            $publicImgDir = public_path('assets/img');
            $logoTarget = $publicImgDir . '/logo-espega.png';
            if (!\Illuminate\Support\Facades\File::exists($logoTarget) && \Illuminate\Support\Facades\File::exists($uploadedLogo)) {
                if (!\Illuminate\Support\Facades\File::isDirectory($publicImgDir)) {
                    \Illuminate\Support\Facades\File::makeDirectory($publicImgDir, 0755, true);
                }
                \Illuminate\Support\Facades\File::copy($uploadedLogo, $logoTarget);
            }

            if (\Illuminate\Support\Facades\Schema::hasTable('exam_sessions') && !\Illuminate\Support\Facades\Schema::hasColumn('exam_sessions', 'jumlah_blokir')) {
                \Illuminate\Support\Facades\Schema::table('exam_sessions', function (\Illuminate\Database\Schema\Blueprint $table) {
                    $table->unsignedInteger('jumlah_blokir')->default(0)->after('status_siswa');
                });
            }

            if (\Illuminate\Support\Facades\Schema::hasTable('exams') && !\Illuminate\Support\Facades\Schema::hasColumn('exams', 'bank_soal_id')) {
                \Illuminate\Support\Facades\Schema::table('exams', function (\Illuminate\Database\Schema\Blueprint $table) {
                    $table->unsignedBigInteger('bank_soal_id')->nullable()->after('subject_id');
                });
            }

            // 1. Skema Tabel Academic Years (Tahun Ajaran)
            if (!\Illuminate\Support\Facades\Schema::hasTable('academic_years')) {
                \Illuminate\Support\Facades\Schema::create('academic_years', function (\Illuminate\Database\Schema\Blueprint $table) {
                    $table->id();
                    $table->string('nama', 50); // e.g. "2026/2027"
                    $table->string('semester', 20)->default('ganjil'); // 'ganjil' | 'genap'
                    $table->boolean('is_active')->default(false)->index();
                    $table->date('tanggal_mulai')->nullable();
                    $table->date('tanggal_selesai')->nullable();
                    $table->text('keterangan')->nullable();
                    $table->timestamps();
                });
            }

            // 2. Skema Tabel Student Class Histories (Rekam Jejak Kenaikan Kelas & Alumni)
            if (!\Illuminate\Support\Facades\Schema::hasTable('student_class_histories')) {
                \Illuminate\Support\Facades\Schema::create('student_class_histories', function (\Illuminate\Database\Schema\Blueprint $table) {
                    $table->id();
                    $table->unsignedBigInteger('user_id')->index();
                    $table->unsignedBigInteger('academic_year_id')->index();
                    $table->string('kelas', 50);
                    $table->string('status', 30)->default('aktif'); // aktif, naik_kelas, tinggal_kelas, lulus, mutasi
                    $table->text('catatan')->nullable();
                    $table->timestamps();
                });
            }

            // 3. Tambahkan kolom pendukung SIAKAD di tabel users
            if (\Illuminate\Support\Facades\Schema::hasTable('users')) {
                \Illuminate\Support\Facades\Schema::table('users', function (\Illuminate\Database\Schema\Blueprint $table) {
                    if (!\Illuminate\Support\Facades\Schema::hasColumn('users', 'nip')) {
                        $table->string('nip', 50)->nullable()->after('nama_lengkap');
                    }
                    if (!\Illuminate\Support\Facades\Schema::hasColumn('users', 'nisn')) {
                        $table->string('nisn', 30)->nullable()->after('kelas');
                    }
                    if (!\Illuminate\Support\Facades\Schema::hasColumn('users', 'nis')) {
                        $table->string('nis', 30)->nullable()->after('nisn');
                    }
                    if (!\Illuminate\Support\Facades\Schema::hasColumn('users', 'jenis_kelamin')) {
                        $table->enum('jenis_kelamin', ['L', 'P'])->nullable()->after('nis');
                    }
                    if (!\Illuminate\Support\Facades\Schema::hasColumn('users', 'status_siswa')) {
                        $table->string('status_siswa', 30)->default('aktif')->after('jenis_kelamin');
                    }
                    if (!\Illuminate\Support\Facades\Schema::hasColumn('users', 'academic_year_id')) {
                        $table->unsignedBigInteger('academic_year_id')->nullable()->after('status_siswa');
                    }
                    if (!\Illuminate\Support\Facades\Schema::hasColumn('users', 'tahun_masuk_id')) {
                        $table->unsignedBigInteger('tahun_masuk_id')->nullable()->after('academic_year_id');
                    }
                    if (!\Illuminate\Support\Facades\Schema::hasColumn('users', 'tahun_lulus_id')) {
                        $table->unsignedBigInteger('tahun_lulus_id')->nullable()->after('tahun_masuk_id');
                    }
                    if (!\Illuminate\Support\Facades\Schema::hasColumn('users', 'telepon')) {
                        $table->string('telepon', 30)->nullable()->after('tahun_lulus_id');
                    }
                    if (!\Illuminate\Support\Facades\Schema::hasColumn('users', 'alamat')) {
                        $table->text('alamat')->nullable()->after('telepon');
                    }
                });

                // Auto-Hash seluruh password dalam database (Admin, Guru, Siswa) jika belum berupa Hash Bcrypt
                $unhashedUsers = \App\Models\User::whereNotNull('password')
                    ->where('password', '!=', '')
                    ->where(function ($q) {
                        $q->where('password', 'not like', '$2y$%')
                          ->where('password', 'not like', '$2a$%')
                          ->where('password', 'not like', '$argon%');
                    })
                    ->get();

                foreach ($unhashedUsers as $u) {
                    \Illuminate\Support\Facades\DB::table('users')
                        ->where('id', $u->id)
                        ->update(['password' => \Illuminate\Support\Facades\Hash::make($u->password)]);
                }

                // Jika ada user yang password-nya masih null/kosong, isi dengan hash dari token atau username
                $nullPasswordUsers = \App\Models\User::where(function ($q) {
                    $q->whereNull('password')->orWhere('password', '');
                })->get();

                foreach ($nullPasswordUsers as $u) {
                    $secret = !empty($u->token) ? $u->token : ($u->username ?: '123456');
                    \Illuminate\Support\Facades\DB::table('users')
                        ->where('id', $u->id)
                        ->update(['password' => \Illuminate\Support\Facades\Hash::make($secret)]);
                }

                // Pulihkan akun siswa yang sempat terblokir akibat bug navigasi setelah selesai ujian
                if (\Illuminate\Support\Facades\Schema::hasTable('exam_sessions')) {
                    $accidentallyBlockedUsers = \App\Models\User::where('role_id', 3)
                        ->where('status_blokir', '1')
                        ->whereDoesntHave('examSessions', function ($q) {
                            $q->where('status_siswa', 'terblokir');
                        })
                        ->get();

                    foreach ($accidentallyBlockedUsers as $abu) {
                        \Illuminate\Support\Facades\DB::table('users')
                            ->where('id', $abu->id)
                            ->update(['status_blokir' => '0']);
                    }
                }
            }

            // 4. Hubungkan Ujian & Bank Soal ke Tahun Ajaran
            if (\Illuminate\Support\Facades\Schema::hasTable('exams') && !\Illuminate\Support\Facades\Schema::hasColumn('exams', 'academic_year_id')) {
                \Illuminate\Support\Facades\Schema::table('exams', function (\Illuminate\Database\Schema\Blueprint $table) {
                    $table->unsignedBigInteger('academic_year_id')->nullable()->after('id');
                });
            }

            if (\Illuminate\Support\Facades\Schema::hasTable('bank_soal') && !\Illuminate\Support\Facades\Schema::hasColumn('bank_soal', 'academic_year_id')) {
                \Illuminate\Support\Facades\Schema::table('bank_soal', function (\Illuminate\Database\Schema\Blueprint $table) {
                    $table->unsignedBigInteger('academic_year_id')->nullable()->after('id');
                });
            }

            // 5. Inisialisasi Tahun Ajaran Default jika belum ada data
            if (\Illuminate\Support\Facades\Schema::hasTable('academic_years')) {
                $activeAY = \App\Models\AcademicYear::where('is_active', true)->first();
                if (!$activeAY) {
                    $activeAY = \App\Models\AcademicYear::firstOrCreate(
                        ['nama' => '2026/2027', 'semester' => 'ganjil'],
                        [
                            'is_active' => true,
                            'tanggal_mulai' => now()->startOfYear()->toDateString(),
                            'tanggal_selesai' => now()->endOfYear()->toDateString(),
                            'keterangan' => 'Tahun Ajaran Baru (Sistem Informasi Terpadu SMPN 3 Ngawen)',
                        ]
                    );
                    $activeAY->update(['is_active' => true]);
                }

                // Sinkronkan data siswa lama yang belum terikat tahun ajaran
                if (\Illuminate\Support\Facades\Schema::hasTable('users') && $activeAY) {
                    \App\Models\User::where('role_id', 3)
                        ->whereNull('academic_year_id')
                        ->update(['academic_year_id' => $activeAY->id, 'status_siswa' => 'aktif']);

                    // Buat initial class history jika belum pernah ada
                    if (\Illuminate\Support\Facades\Schema::hasTable('student_class_histories')) {
                        $studentsWithoutHistory = \App\Models\User::where('role_id', 3)
                            ->whereNotNull('kelas')
                            ->where('kelas', '!=', '')
                            ->where('kelas', '!=', 'Alumni')
                            ->whereDoesntHave('classHistories')
                            ->get();

                        foreach ($studentsWithoutHistory as $stu) {
                            \App\Models\StudentClassHistory::create([
                                'user_id' => $stu->id,
                                'academic_year_id' => $activeAY->id,
                                'kelas' => $stu->kelas,
                                'status' => 'aktif',
                                'catatan' => 'Sinkronisasi Awal Sistem',
                            ]);
                        }
                    }
                }

                // Sinkronkan ujian & bank soal lama
                if (\Illuminate\Support\Facades\Schema::hasTable('exams') && $activeAY) {
                    \App\Models\Exam::whereNull('academic_year_id')->update(['academic_year_id' => $activeAY->id]);
                }
                if (\Illuminate\Support\Facades\Schema::hasTable('bank_soal') && $activeAY) {
                    \App\Models\BankSoal::whereNull('academic_year_id')->update(['academic_year_id' => $activeAY->id]);
                }

                // Bagikan data tahun ajaran aktif secara global ke seluruh Blade views
                \Illuminate\Support\Facades\View::share('activeAcademicYear', $activeAY);
            }

            // 6. Skema Tabel Berita Harian & Buku Harian Guru
            if (!\Illuminate\Support\Facades\Schema::hasTable('berita')) {
                \Illuminate\Support\Facades\Schema::create('berita', function (\Illuminate\Database\Schema\Blueprint $table) {
                    $table->id();
                    $table->unsignedBigInteger('academic_year_id')->nullable()->index();
                    $table->unsignedBigInteger('user_id')->index();
                    $table->string('kategori', 50)->default('berita'); // 'berita', 'buku_harian', 'pengumuman', 'prestasi'
                    $table->string('judul', 255);
                    $table->string('slug', 255)->unique();
                    $table->text('ringkasan')->nullable();
                    $table->longText('konten');
                    $table->string('gambar', 255)->nullable();
                    $table->date('tanggal');
                    $table->boolean('tampil_di_web')->default(true)->index();
                    $table->unsignedInteger('dibaca')->default(0);
                    $table->timestamps();
                });
            }

            // Pastikan folder uploads/berita tersedia
            $uploadBeritaDir = public_path('uploads/berita');
            if (!\Illuminate\Support\Facades\File::isDirectory($uploadBeritaDir)) {
                \Illuminate\Support\Facades\File::makeDirectory($uploadBeritaDir, 0755, true);
            }

            // Seed sampel berita harian jika tabel berita masih kosong
            if (\Illuminate\Support\Facades\Schema::hasTable('berita') && \App\Models\Berita::count() === 0) {
                $author = \App\Models\User::where('role_id', 1)->first() ?: \App\Models\User::first();
                $curAY = \App\Models\AcademicYear::getActive();

                if ($author) {
                    \App\Models\Berita::create([
                        'academic_year_id' => $curAY?->id,
                        'user_id' => $author->id,
                        'kategori' => 'berita',
                        'judul' => 'Pelaksanaan Asesmen Sumatif Tengah Semester (ASTS) Berbasis Digital',
                        'slug' => 'pelaksanaan-asesmen-sumatif-tengah-semester-asts-berbasis-digital',
                        'ringkasan' => 'SMP Negeri 3 Ngawen menyelenggarakan asesmen terintegrasi menggunakan platform Espega Sinau guna mendukung kejujuran dan kemandirian siswa.',
                        'konten' => '<p>SMP Negeri 3 Ngawen sukses melaksanakan Asesmen Sumatif Tengah Semester (ASTS) berbasis komputer dan gawai digital. Pelaksanaan asesmen memanfaatkan sistem CBT Espega Sinau dengan pemantauan integritas secara langsung, pengacakan butir soal, dan perlindungan keamanan layar.</p><p>Kepala Sekolah dan para guru mengapresiasi antusiasme dan kedisiplinan para siswa dalam mengikuti asesmen ini secara mandiri, jujur, dan berintegritas tinggi.</p>',
                        'tanggal' => now()->toDateString(),
                        'tampil_di_web' => true,
                        'dibaca' => 124,
                    ]);

                    \App\Models\Berita::create([
                        'academic_year_id' => $curAY?->id,
                        'user_id' => $author->id,
                        'kategori' => 'prestasi',
                        'judul' => 'Prestasi Gemilang Kontingen Espega dalam Ajang FLS2N & O2SN Tingkat Kabupaten',
                        'slug' => 'prestasi-gemilang-kontingen-espega-dalam-ajang-fls2n-o2sn',
                        'ringkasan' => 'Siswa-siswi SMP Negeri 3 Ngawen kembali menorehkan prestasi membanggakan pada perlombaan seni budaya dan olahraga.',
                        'konten' => '<p>Selamat dan sukses kepada kontingen siswa SMP Negeri 3 Ngawen yang berhasil meraih kejuaraan pada Festival dan Lomba Seni Siswa Nasional (FLS2N) serta Olimpiade Olahraga Siswa Nasional (O2SN) tingkat kabupaten.</p><p>Pencapaian ini mencerminkan komitmen kuat sekolah dalam memfasilitasi bakat dan minat pelajar sesuai dengan pilar Profil Pelajar Pancasila yang religius, berprestasi, dan berbudaya.</p>',
                        'tanggal' => now()->subDays(5)->toDateString(),
                        'tampil_di_web' => true,
                        'dibaca' => 98,
                    ]);

                    \App\Models\Berita::create([
                        'academic_year_id' => $curAY?->id,
                        'user_id' => $author->id,
                        'kategori' => 'buku_harian',
                        'judul' => 'Jurnal Guru: Pembelajaran Interaktif Berbasis Eksperimen Sains di Kelas 7',
                        'slug' => 'jurnal-guru-pembelajaran-interaktif-berbasis-eksperimen-sains',
                        'ringkasan' => 'Catatan harian guru mengenai penerapan model project-based learning pada materi ekosistem lingkungan sekitar sekolah.',
                        'konten' => '<p>Hari ini pembelajaran IPA di kelas 7 berlangsung sangat menarik. Siswa diajak melakukan observasi biotik dan abiotik secara langsung di area taman hijau sekolah. Antusiasme siswa sangat tinggi saat mendokumentasikan interaksi makhluk hidup dan mempresentasikannya di depan kelas.</p><p>Metode ini terbukti meningkatkan daya nalar kritis dan kolaborasi antar-siswa secara menyenangkan.</p>',
                        'tanggal' => now()->subDays(2)->toDateString(),
                        'tampil_di_web' => true,
                        'dibaca' => 65,
                    ]);
                }
            }

            // 7. Skema Tabel Rombongan Belajar (Rombel / Kelas)
            if (!\Illuminate\Support\Facades\Schema::hasTable('rombels')) {
                \Illuminate\Support\Facades\Schema::create('rombels', function (\Illuminate\Database\Schema\Blueprint $table) {
                    $table->id();
                    $table->string('nama_rombel', 50)->unique();
                    $table->string('tingkat', 10)->default('7'); // 7, 8, 9
                    $table->unsignedBigInteger('wali_kelas_id')->nullable()->index();
                    $table->unsignedBigInteger('academic_year_id')->nullable()->index();
                    $table->string('ruang', 50)->nullable();
                    $table->text('keterangan')->nullable();
                    $table->timestamps();
                });
            }

            // Sinkronisasi Rombel otomatis dari data siswa yang sudah ada
            if (\Illuminate\Support\Facades\Schema::hasTable('rombels') && \Illuminate\Support\Facades\Schema::hasTable('users')) {
                $activeAY = \App\Models\AcademicYear::getActive();
                $existingClasses = \App\Models\User::where('role_id', 3)
                    ->whereNotNull('kelas')
                    ->where('kelas', '!=', '')
                    ->where('kelas', '!=', 'Alumni')
                    ->distinct()
                    ->pluck('kelas');

                foreach ($existingClasses as $cls) {
                    $tingkat = preg_replace('/[^0-9]/', '', $cls) ?: substr($cls, 0, 1);
                    \App\Models\Rombel::firstOrCreate(
                        ['nama_rombel' => $cls],
                        [
                            'tingkat' => $tingkat ?: '7',
                            'academic_year_id' => $activeAY?->id,
                            'keterangan' => 'Rombel ' . $cls . ' SMPN 3 Ngawen',
                        ]
                    );
                }
            }

            // 8. Kolom tambahan pada tabel subjects (Mata Pelajaran)
            if (\Illuminate\Support\Facades\Schema::hasTable('subjects')) {
                \Illuminate\Support\Facades\Schema::table('subjects', function (\Illuminate\Database\Schema\Blueprint $table) {
                    if (!\Illuminate\Support\Facades\Schema::hasColumn('subjects', 'kode_mapel')) {
                        $table->string('kode_mapel', 20)->nullable()->after('nama_mapel');
                    }
                    if (!\Illuminate\Support\Facades\Schema::hasColumn('subjects', 'kelompok')) {
                        $table->string('kelompok', 50)->default('Umum (A)')->after('kode_mapel');
                    }
                    if (!\Illuminate\Support\Facades\Schema::hasColumn('subjects', 'kkm')) {
                        $table->unsignedInteger('kkm')->default(75)->after('kelompok');
                    }
                    if (!\Illuminate\Support\Facades\Schema::hasColumn('subjects', 'keterangan')) {
                        $table->text('keterangan')->nullable()->after('kkm');
                    }
                });

                // Auto generate kode mapel jika masih kosong
                $subjectsWithoutCode = \App\Models\Subject::whereNull('kode_mapel')->orWhere('kode_mapel', '')->get();
                foreach ($subjectsWithoutCode as $sub) {
                    $words = explode(' ', trim($sub->nama_mapel));
                    $code = '';
                    if (count($words) === 1) {
                        $code = strtoupper(substr($words[0], 0, 4));
                    } else {
                        foreach ($words as $w) {
                            $code .= strtoupper(substr($w, 0, 1));
                        }
                    }
                    $sub->update(['kode_mapel' => substr($code, 0, 10)]);
                }
            }

            // 9. Skema Tabel Permohonan Informasi & Pengaduan Publik
            if (!\Illuminate\Support\Facades\Schema::hasTable('permohonan_informasi')) {
                \Illuminate\Support\Facades\Schema::create('permohonan_informasi', function (\Illuminate\Database\Schema\Blueprint $table) {
                    $table->id();
                    $table->string('nomor_laporan', 35)->unique()->index();
                    $table->string('nama_lengkap', 100);
                    $table->string('kontak', 100);
                    $table->string('subjek', 150);
                    $table->text('pesan');
                    $table->string('status', 30)->default('pending')->index(); // pending, proses, selesai, ditolak
                    $table->text('tanggapan')->nullable();
                    $table->timestamp('tanggal_tanggapan')->nullable();
                    $table->unsignedBigInteger('petugas_id')->nullable()->index();
                    $table->timestamps();
                });
            }
        } catch (\Throwable $e) {
            // Abaikan jika database belum siap
        }
    }
}
