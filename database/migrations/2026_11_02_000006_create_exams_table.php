<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('exams')) {
            Schema::create('exams', function (Blueprint $table) {
                $table->id();
                $table->string('nama_ujian', 150);
                $table->string('target_kelas', 50)->nullable();
                $table->unsignedBigInteger('subject_id')->nullable();
                $table->dateTime('waktu_mulai')->nullable();
                $table->dateTime('waktu_selesai')->nullable();
                $table->integer('durasi')->nullable()->comment('dalam menit');
                $table->string('token_ujian', 10)->nullable();
                $table->timestamps();

                $table->foreign('subject_id')->references('id')->on('subjects')->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('exams');
    }
};

