<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('instance_summary', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('periode_id')->index()->nullable();
            $table->year('year')->nullable();
            $table->integer('month')->nullable();
            $table->integer('instance_id')->nullable();

            $table->double('pagu_anggaran', 15, 2)->nullable();
            $table->double('realisasi_anggaran', 15, 2)->nullable();
            $table->double('persentase_realisasi', 15, 2)->nullable();

            $table->double('sisa_anggaran', 15, 2)->nullable();
            $table->double('persentase_sisa', 15, 2)->nullable();

            $table->double('target_kinerja', 15, 2)->nullable();
            $table->double('realisasi_kinerja', 15, 2)->nullable();
            $table->double('persentase_kinerja', 15, 2)->nullable();

            $table->dateTime('tanggal_update')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('instance_summary');
    }
};
