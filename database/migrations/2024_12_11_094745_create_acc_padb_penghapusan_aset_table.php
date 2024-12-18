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
        Schema::create('acc_padb_penghapusan_aset', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('periode_id')->index()->nullable();
            $table->year('year')->nullable();
            $table->integer('instance_id')->nullable();
            $table->integer('unit_id')->nullable();

            $table->text('kelompok_barang_aset')->nullable();
            $table->text('nama_barang')->nullable();
            $table->year('tahun_perolehan')->nullable();
            $table->double('nilai_perolehan')->nullable();
            $table->double('akumulasi_penyusutan')->nullable();
            $table->text('nomor_berita_acara')->nullable();
            $table->date('tanggal_berita_acara')->nullable();

            $table->double('persediaan')->nullable();
            $table->double('aset_tetap_tanah')->nullable();
            $table->double('aset_tetap_peralatan_mesin')->nullable();
            $table->double('aset_tetap_gedung_bangunan')->nullable();
            $table->double('aset_tetap_jalan_jaringan_irigasi')->nullable();
            $table->double('aset_tetap_lainnya')->nullable();
            $table->double('konstruksi_dalam_pekerjaan')->nullable();
            $table->double('aset_lainnya')->nullable();

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
        Schema::dropIfExists('acc_padb_penghapusan_aset');
    }
};
