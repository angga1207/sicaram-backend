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
        Schema::create('acc_hutang_belanja', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('periode_id')->index()->nullable();
            $table->year('year')->nullable();
            $table->integer('instance_id')->nullable();
            $table->integer('unit_id')->nullable();

            $table->integer('kode_rekening_id')->nullable();
            $table->text('nama_kegiatan')->nullable();
            $table->text('pelaksana_pekerjaan')->nullable();
            $table->text('nomor_kontrak')->nullable();
            $table->double('nilai_kontrak')->nullable();
            $table->double('kewajiban_tidak_terbayar')->nullable();
            $table->double('kewajiban_tidak_terbayar_last_year')->nullable();

            $table->text('p1_nomor_sp2d')->nullable();
            $table->date('p1_tanggal')->nullable();
            $table->double('p1_jumlah')->nullable();

            $table->text('p2_nomor_sp2d')->nullable();
            $table->date('p2_tanggal')->nullable();
            $table->double('p2_jumlah')->nullable();

            $table->text('p3_nomor_sp2d')->nullable();
            $table->date('p3_tanggal')->nullable();
            $table->double('p3_jumlah')->nullable();

            $table->double('jumlah_pembayaran_hutang')->nullable();
            $table->double('hutang_baru')->nullable();

            $table->double('pegawai')->nullable();
            $table->double('persediaan')->nullable();
            $table->double('perjadin')->nullable();
            $table->double('jasa')->nullable();
            $table->double('pemeliharaan')->nullable();
            $table->double('hibah')->nullable();

            $table->double('aset_tetap_tanah')->nullable();
            $table->double('aset_tetap_peralatan_mesin')->nullable();
            $table->double('aset_tetap_gedung_bangunan')->nullable();
            $table->double('aset_tetap_jalan_jaringan_irigasi')->nullable();
            $table->double('aset_tetap_lainnya')->nullable();
            $table->double('konstruksi_dalam_pekerjaan')->nullable();
            $table->double('aset_lain_lain')->nullable();

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
        Schema::dropIfExists('acc_hutang_belanja');
    }
};
