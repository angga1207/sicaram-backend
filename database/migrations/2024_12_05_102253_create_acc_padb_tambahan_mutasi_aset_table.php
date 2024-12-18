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
        Schema::create('acc_padb_tambahan_mutasi_aset', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('periode_id')->index()->nullable();
            $table->year('year')->nullable();
            $table->integer('instance_id')->nullable();
            $table->integer('unit_id')->nullable();

            $table->integer('from_instance_id')->nullable();
            $table->integer('to_instance_id')->nullable();
            $table->text('kelompok_aset')->nullable();
            $table->text('nama_barang')->nullable();
            $table->year('tahun_perolehan')->nullable();
            $table->double('nilai_perolehan')->nullable();
            $table->double('akumulasi_penyusutan')->nullable();
            $table->text('bast_number')->nullable();
            $table->date('bast_date')->nullable();

            $table->double('plus_aset_tetap_tanah')->nullable();
            $table->double('plus_aset_tetap_perlatan_mesin')->nullable();
            $table->double('plus_aset_tetap_gedung_bangunan')->nullable();
            $table->double('plus_aset_tetap_jalan_jaringan_irigasi')->nullable();
            $table->double('plus_aset_tetap_lainnya')->nullable();
            $table->double('plus_kdp')->nullable();
            $table->double('plus_aset_lainnya')->nullable();

            $table->double('min_aset_tetap_tanah')->nullable();
            $table->double('min_aset_tetap_perlatan_mesin')->nullable();
            $table->double('min_aset_tetap_gedung_bangunan')->nullable();
            $table->double('min_aset_tetap_jalan_jaringan_irigasi')->nullable();
            $table->double('min_aset_tetap_lainnya')->nullable();
            $table->double('min_kdp')->nullable();
            $table->double('min_aset_lainnya')->nullable();

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
        Schema::dropIfExists('acc_padb_tambahan_mutasi_aset');
    }
};
