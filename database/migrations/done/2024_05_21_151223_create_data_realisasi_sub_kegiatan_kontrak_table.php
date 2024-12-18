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
        Schema::create('data_realisasi_sub_kegiatan_kontrak', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('instance_id');
            $table->unsignedBigInteger('urusan_id');
            $table->unsignedBigInteger('bidang_urusan_id');
            $table->unsignedBigInteger('program_id');
            $table->unsignedBigInteger('kegiatan_id');
            $table->unsignedBigInteger('sub_kegiatan_id');
            $table->year('year');
            $table->integer('month');
            $table->text('no_kontrak');
            $table->text('kd_tender');
            $table->longText('data_spse');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('data_realisasi_sub_kegiatan_kontrak');
    }
};
