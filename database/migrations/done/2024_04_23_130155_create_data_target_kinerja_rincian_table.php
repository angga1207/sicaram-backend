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
        Schema::create('data_target_kinerja_rincian', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('periode_id')->index()->nullable();
            $table->unsignedBigInteger('target_kinerja_id')->index()->nullable();
            $table->unsignedBigInteger('urusan_id')->index()->nullable();
            $table->unsignedBigInteger('bidang_urusan_id')->index()->nullable();
            $table->unsignedBigInteger('program_id')->index()->nullable();
            $table->unsignedBigInteger('kegiatan_id')->index()->nullable();
            $table->unsignedBigInteger('sub_kegiatan_id')->index()->nullable();
            $table->unsignedBigInteger('kode_rekening_id')->index()->nullable();
            $table->unsignedBigInteger('sumber_dana_id')->index()->nullable();
            $table->year('year')->nullable();
            $table->integer('month')->index();
            $table->text('title')->nullable();
            $table->double('pagu_sebelum_pergeseran', 100, 2)->default(0);
            $table->double('pagu_sesudah_pergeseran', 100, 2)->default(0);
            $table->double('pagu_selisih', 100, 2)->default(0);
            $table->double('pagu_sipd', 100, 2)->default(0);
            $table->unsignedBigInteger('created_by')->index()->nullable();
            $table->unsignedBigInteger('updated_by')->index()->nullable();
            $table->unsignedBigInteger('deleted_by')->index()->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('periode_id')->references('id')->on('ref_periode');
            $table->foreign('target_kinerja_id')->references('id')->on('data_target_kinerja');
            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('updated_by')->references('id')->on('users');
            $table->foreign('deleted_by')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('data_target_kinerja_rincian');
    }
};
