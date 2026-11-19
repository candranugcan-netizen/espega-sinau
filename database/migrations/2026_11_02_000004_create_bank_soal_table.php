<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('bank_soal')) {
            Schema::create('bank_soal', function (Blueprint $table) {
                $table->id();
                $table->string('nama_bank', 150);
                $table->unsignedBigInteger('subject_id');
                $table->unsignedBigInteger('guru_id');
                $table->string('kelas_rilis', 255)->nullable();
                $table->enum('status_rilis', ['draft', 'rilis'])->default('draft');
                $table->timestamps();

                $table->foreign('subject_id')->references('id')->on('subjects')->onDelete('cascade');
                $table->foreign('guru_id')->references('id')->on('users')->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_soal');
    }
};

