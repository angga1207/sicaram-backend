<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('data_realisasi_sasaran', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('periode_id')->nullable()->unsigned()->index();
            $table->integer('instance_id')->nullable()->unsigned()->index();
            $table->integer('tujuan_id')->nullable()->unsigned()->index();
            $table->integer('sasaran_id')->nullable()->unsigned()->index();
            $table->integer('ref_id')->nullable()->unsigned()->index()->comment('Indikator Sasaran');
            $table->year('year');

            $table->text('realisasi_1')->nullable();
            $table->text('keterangan_1')->nullable();
            $table->json('files_1')->nullable();

            $table->text('realisasi_2')->nullable();
            $table->text('keterangan_2')->nullable();
            $table->json('files_2')->nullable();

            $table->text('realisasi_3')->nullable();
            $table->text('keterangan_3')->nullable();
            $table->json('files_3')->nullable();

            $table->text('realisasi_4')->nullable();
            $table->text('keterangan_4')->nullable();
            $table->json('files_4')->nullable();

            $table->text('realisasi_5')->nullable();
            $table->text('keterangan_5')->nullable();
            $table->json('files_5')->nullable();

            $table->text('realisasi_6')->nullable();
            $table->text('keterangan_6')->nullable();
            $table->json('files_6')->nullable();

            $table->text('realisasi_7')->nullable();
            $table->text('keterangan_7')->nullable();
            $table->json('files_7')->nullable();

            $table->text('realisasi_8')->nullable();
            $table->text('keterangan_8')->nullable();
            $table->json('files_8')->nullable();

            $table->text('realisasi_9')->nullable();
            $table->text('keterangan_9')->nullable();
            $table->json('files_9')->nullable();

            $table->text('realisasi_10')->nullable();
            $table->text('keterangan_10')->nullable();
            $table->json('files_10')->nullable();

            $table->text('realisasi_11')->nullable();
            $table->text('keterangan_11')->nullable();
            $table->json('files_11')->nullable();

            $table->text('realisasi_12')->nullable();
            $table->text('keterangan_12')->nullable();
            $table->json('files_12')->nullable();

            $table->text('realisasi')->nullable();
            $table->text('keterangan')->nullable();
            $table->json('files')->nullable();

            $table->string('status')->default('active');
            $table->unsignedBigInteger('created_by')->index()->nullable();
            $table->unsignedBigInteger('updated_by')->index()->nullable();
            $table->unsignedBigInteger('deleted_by')->index()->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('instance_id')->references('id')->on('instances');
            $table->foreign('periode_id')->references('id')->on('ref_periode');
            $table->foreign('tujuan_id')->references('id')->on('master_tujuan');
            $table->foreign('sasaran_id')->references('id')->on('master_sasaran');
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
        Schema::dropIfExists('data_realisasi_sasaran');
    }
};
