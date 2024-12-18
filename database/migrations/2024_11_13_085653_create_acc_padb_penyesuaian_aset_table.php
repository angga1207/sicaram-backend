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
        Schema::create('acc_padb_penyesuaian_aset', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('periode_id')->index()->nullable();
            $table->year('year')->nullable();
            $table->integer('instance_id')->nullable();
            $table->integer('unit_id')->nullable();
            $table->integer('kode_rekening_id')->nullable();
            $table->text('nama_barang_pekerjaan')->nullable();
            $table->text('nomor_kontrak')->nullable();
            $table->text('nomor_sp2d')->nullable();

            $table->double('plus_aset_tetap_tanah')->nullable();
            $table->double('plus_aset_tetap_peralatan_mesin')->nullable();
            $table->double('plus_aset_tetap_gedung_bangunan')->nullable();
            $table->double('plus_aset_tetap_jalan_jaringan_irigasi')->nullable();
            $table->double('plus_aset_tetap_lainnya')->nullable();
            $table->double('plus_konstruksi_dalam_pekerjaan')->nullable();
            $table->double('plus_aset_lain_lain')->nullable();
            $table->double('plus_jumlah_penyesuaian')->nullable();

            $table->double('min_aset_tetap_tanah')->nullable();
            $table->double('min_aset_tetap_peralatan_mesin')->nullable();
            $table->double('min_aset_tetap_gedung_bangunan')->nullable();
            $table->double('min_aset_tetap_jalan_jaringan_irigasi')->nullable();
            $table->double('min_aset_tetap_lainnya')->nullable();
            $table->double('min_konstruksi_dalam_pekerjaan')->nullable();
            $table->double('min_aset_lain_lain')->nullable();
            $table->double('min_jumlah_penyesuaian')->nullable();

            $table->text('keterangan')->nullable();
            $table->json('change_logs')->nullable();
            $table->integer('created_by')->nullable();
            $table->integer('updated_by')->nullable();
            $table->integer('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('acc_padb_penyesuaian_aset');
    }
};
