<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('questions')) {
            Schema::create('questions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('subject_id')->nullable();
                $table->unsignedBigInteger('bank_soal_id')->nullable();
                $table->integer('urutan')->default(0);
                $table->string('kelas', 50)->nullable();
                $table->text('pertanyaan')->nullable();
                $table->string('img_pertanyaan', 255)->nullable();
                $table->text('pil_a')->nullable();
                $table->string('img_a', 255)->nullable();
                $table->text('pil_b')->nullable();
                $table->string('img_b', 255)->nullable();
                $table->text('pil_c')->nullable();
                $table->string('img_c', 255)->nullable();
                $table->text('pil_d')->nullable();
                $table->string('img_d', 255)->nullable();
                $table->text('pil_e')->nullable();
                $table->string('img_e', 255)->nullable();
                $table->char('kunci_jawaban', 1)->nullable();
                $table->timestamps();

                $table->foreign('subject_id')->references('id')->on('subjects')->onDelete('cascade');
                $table->foreign('bank_soal_id')->references('id')->on('bank_soal')->onDelete('cascade');
                $table->index(['bank_soal_id', 'urutan']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};

