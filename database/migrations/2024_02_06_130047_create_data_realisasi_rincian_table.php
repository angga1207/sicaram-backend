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
        Schema::create('data_realisasi_rincian', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('data_realisasi_id');
            $table->unsignedBigInteger('ref_kode_rekening_1')->nullable();
            $table->unsignedBigInteger('ref_kode_rekening_2')->nullable();
            $table->unsignedBigInteger('ref_kode_rekening_3')->nullable();
            $table->unsignedBigInteger('ref_kode_rekening_4')->nullable();
            $table->unsignedBigInteger('ref_kode_rekening_5')->nullable();
            $table->unsignedBigInteger('ref_kode_rekening_6')->nullable();
            $table->string('type', 20)->default('detail')->comment('detail, title');
            $table->longText('uraian')->nullable();

            $table->double('koefisien', 100, 2)->default(0);
            $table->unsignedInteger('satuan_id')->nullable();
            $table->double('harga_satuan', 100, 2)->default(0);
            $table->double('ppn', 100, 2)->default(0);
            $table->double('pph', 100, 2)->default(0);
            $table->double('pph_final', 100, 2)->default(0);
            $table->double('total', 100, 2)->default(0);

            $table->double('persentase', 100, 2)->default(0);
            $table->string('status', 20)->default('active');
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->foreign('data_realisasi_id')->references('id')->on('data_realisasi');
            $table->foreign('ref_kode_rekening_1')->references('id')->on('ref_kode_rekening_1');
            $table->foreign('ref_kode_rekening_2')->references('id')->on('ref_kode_rekening_2');
            $table->foreign('ref_kode_rekening_3')->references('id')->on('ref_kode_rekening_3');
            $table->foreign('ref_kode_rekening_4')->references('id')->on('ref_kode_rekening_4');
            $table->foreign('ref_kode_rekening_5')->references('id')->on('ref_kode_rekening_5');
            $table->foreign('ref_kode_rekening_6')->references('id')->on('ref_kode_rekening_6');
            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('updated_by')->references('id')->on('users');
            $table->foreign('deleted_by')->references('id')->on('users');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('data_realisasi_rincian');
    }
};
