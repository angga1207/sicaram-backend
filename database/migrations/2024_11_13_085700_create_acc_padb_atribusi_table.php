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
        Schema::create('acc_padb_atribusi', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('periode_id')->index()->nullable();
            $table->year('year')->nullable();
            $table->integer('instance_id')->nullable();
            $table->integer('unit_id')->nullable();

            $table->integer('bel_peg_kode_rekening_id')->nullable();
            $table->text('bel_peg_nama_rekening')->nullable();
            $table->double('bel_peg_belanja_last_year')->nullable();
            $table->double('bel_peg_hutang_last_year')->nullable();
            $table->double('bel_peg_jumlah')->nullable();

            $table->integer('bel_barjas_kode_rekening_id')->nullable();
            $table->text('bel_barjas_nama_rekening_rincian_paket')->nullable();
            $table->double('bel_barjas_belanja')->nullable();
            $table->double('bel_barjas_hutang')->nullable();
            $table->double('bel_barjas_jumlah')->nullable();

            $table->integer('bel_modal_kode_rekening_id')->nullable();
            $table->text('bel_modal_nama_rekening_rincian_paket')->nullable();
            $table->double('bel_modal_belanja')->nullable();
            $table->double('bel_modal_hutang')->nullable();
            $table->double('bel_modal_jumlah')->nullable();

            $table->text('ket_no_kontrak_pegawai_barang_jasa')->nullable();
            $table->text('ket_no_sp2d_pegawai_barang_jasa')->nullable();

            $table->double('atri_aset_tetap_tanah')->nullable();
            $table->double('atri_aset_tetap_peralatan_mesin')->nullable();
            $table->double('atri_aset_tetap_gedung_bangunan')->nullable();
            $table->double('atri_aset_tetap_jalan_jaringan_irigasi')->nullable();
            $table->double('atri_aset_tetap_tetap_lainnya')->nullable();
            $table->double('atri_konstruksi_dalam_pekerjaan')->nullable();
            $table->double('atri_aset_lain_lain')->nullable();
            $table->text('atri_ket_no_kontrak_sp2d')->nullable();

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
        Schema::dropIfExists('acc_padb_atribusi');
    }
};
