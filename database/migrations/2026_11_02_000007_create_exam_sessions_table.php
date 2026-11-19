<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('exam_sessions')) {
            Schema::create('exam_sessions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('exam_id');
                $table->unsignedBigInteger('user_id');
                $table->decimal('skor', 5, 2)->default(0.00);
                $table->enum('status_siswa', ['belum', 'pengerjaan', 'selesai', 'terblokir'])->default('belum');
                $table->dateTime('mulai_ujian')->nullable();
                $table->dateTime('selesai_ujian')->nullable();
                $table->integer('sisa_waktu')->nullable()->comment('dalam detik');
                $table->timestamps();

                $table->foreign('exam_id')->references('id')->on('exams')->onDelete('cascade');
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_sessions');
    }
};

