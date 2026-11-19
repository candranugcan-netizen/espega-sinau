<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('subjects')) {
            Schema::create('subjects', function (Blueprint $table) {
                $table->id();
                $table->string('nama_mapel', 100);
                $table->string('kelas', 255)->nullable();
                $table->unsignedBigInteger('guru_id')->nullable();
                $table->timestamps();

                $table->foreign('guru_id')->references('id')->on('users')->onDelete('set null');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('subjects');
    }
};

