<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('exam_answers')) {
            Schema::create('exam_answers', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('session_id');
                $table->unsignedBigInteger('question_id');
                $table->char('jawaban_siswa', 1)->nullable();
                $table->boolean('is_correct')->nullable();
                $table->timestamps();

                $table->foreign('session_id')->references('id')->on('exam_sessions')->onDelete('cascade');
                $table->foreign('question_id')->references('id')->on('questions')->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_answers');
    }
};

