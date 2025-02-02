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
        Schema::create('data_realisasi_sub_kegiatan', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('periode_id')->index()->nullable();
            $table->year('year')->nullable();
            $table->integer('month')->index();
            $table->unsignedBigInteger('instance_id')->index()->nullable();

            $table->unsignedBigInteger('urusan_id')->index()->nullable();
            $table->unsignedBigInteger('bidang_urusan_id')->index()->nullable();
            $table->unsignedBigInteger('program_id')->index()->nullable();
            $table->unsignedBigInteger('kegiatan_id')->index()->nullable();
            $table->unsignedBigInteger('sub_kegiatan_id')->index()->nullable();

            $table->double('realisasi_anggaran', 100, 2)->default(0);
            $table->double('persentase_realisasi_anggaran', 100, 2)->default(0);
            $table->double('realisasi_kinerja', 100, 2)->default(0);
            $table->json('realisasi_kinerja_json')->nullable();
            $table->double('persentase_realisasi_kinerja', 100, 2)->default(0);

            $table->enum('status', ['draft', 'verified', 'reject', 'return', 'sent', 'waiting'])->default('draft');
            $table->enum('status_leader', ['draft', 'verified', 'reject', 'return', 'sent', 'waiting'])->default('draft');

            $table->unsignedBigInteger('created_by')->index()->nullable();
            $table->unsignedBigInteger('updated_by')->index()->nullable();
            $table->unsignedBigInteger('deleted_by')->index()->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('periode_id')->references('id')->on('ref_periode');
            $table->foreign('instance_id')->references('id')->on('instances');
            // $table->foreign('target_id')->references('id')->on('data_target_kinerja');
            $table->foreign('urusan_id')->references('id')->on('ref_urusan');
            $table->foreign('bidang_urusan_id')->references('id')->on('ref_bidang_urusan');
            $table->foreign('program_id')->references('id')->on('ref_program');
            $table->foreign('kegiatan_id')->references('id')->on('ref_kegiatan');
            $table->foreign('sub_kegiatan_id')->references('id')->on('ref_sub_kegiatan');
            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('updated_by')->references('id')->on('users');
            $table->foreign('deleted_by')->references('id')->on('users');
            $table->index(['periode_id', 'year', 'month', 'instance_id', 'urusan_id', 'bidang_urusan_id', 'program_id', 'kegiatan_id', 'sub_kegiatan_id'], 'realisasi_sub_kegiatan_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('data_realisasi_sub_kegiatan');
    }
};
