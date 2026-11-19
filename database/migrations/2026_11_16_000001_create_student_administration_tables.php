<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('nik', 30)->nullable();
            $table->string('nomor_kk', 30)->nullable();
            $table->string('tempat_lahir', 100)->nullable();
            $table->date('tanggal_lahir')->nullable();
            $table->string('agama', 40)->nullable();
            $table->unsignedSmallInteger('anak_ke')->nullable();
            $table->unsignedSmallInteger('jumlah_saudara')->nullable();
            $table->text('alamat_detail')->nullable();
            $table->string('kode_pos', 10)->nullable();
            $table->string('asal_sekolah', 150)->nullable();
            $table->date('tanggal_masuk')->nullable();
            $table->string('nama_ayah', 100)->nullable();
            $table->string('pekerjaan_ayah', 100)->nullable();
            $table->string('telepon_ayah', 30)->nullable();
            $table->string('nama_ibu', 100)->nullable();
            $table->string('pekerjaan_ibu', 100)->nullable();
            $table->string('telepon_ibu', 30)->nullable();
            $table->string('nama_wali', 100)->nullable();
            $table->string('pekerjaan_wali', 100)->nullable();
            $table->string('telepon_wali', 30)->nullable();
            $table->text('alamat_wali')->nullable();
            $table->text('catatan')->nullable();
            $table->timestamps();
        });

        Schema::create('student_document_types', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('slug', 120)->unique();
            $table->boolean('is_required')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('student_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('document_type_id')->constrained('student_document_types')->restrictOnDelete();
            $table->string('original_name', 255);
            $table->string('path', 500);
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('size_bytes');
            $table->timestamps();

            $table->index(['user_id', 'document_type_id']);
        });

        DB::table('student_document_types')->insert([
            ['name' => 'Kartu Keluarga', 'slug' => 'kartu-keluarga', 'is_required' => true, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Ijazah Terakhir', 'slug' => 'ijazah-terakhir', 'is_required' => true, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Akta Kelahiran', 'slug' => 'akta-kelahiran', 'is_required' => true, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('student_documents');
        Schema::dropIfExists('student_document_types');
        Schema::dropIfExists('student_profiles');
    }
};
