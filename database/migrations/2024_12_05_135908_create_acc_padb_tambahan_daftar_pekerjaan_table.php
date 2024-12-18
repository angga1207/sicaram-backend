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
        Schema::create('acc_padb_tambahan_daftar_pekerjaan', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('periode_id')->index()->nullable();
            $table->year('year')->nullable();
            $table->integer('instance_id')->nullable();
            $table->integer('unit_id')->nullable();

            $table->integer('kode_rekening_id')->nullable();
            $table->text('kode_rekening_name')->nullable();
            $table->text('nama_kegiatan_paket')->nullable();
            $table->text('pelaksana_pekerjaan')->nullable();
            $table->text('no_kontrak')->nullable();
            $table->text('periode_kontrak')->nullable();
            $table->double('nilai_belanja_kontrak')->nullable();

            $table->text('payment_1_sp2d')->nullable();
            $table->date('payment_1_tanggal')->nullable();
            $table->double('payment_1_jumlah')->nullable();

            $table->text('payment_2_sp2d')->nullable();
            $table->date('payment_2_tanggal')->nullable();
            $table->double('payment_2_jumlah')->nullable();

            $table->text('payment_3_sp2d')->nullable();
            $table->date('payment_3_tanggal')->nullable();
            $table->double('payment_3_jumlah')->nullable();

            $table->text('payment_4_sp2d')->nullable();
            $table->date('payment_4_tanggal')->nullable();
            $table->double('payment_4_jumlah')->nullable();

            $table->double('jumlah_pembayaran_sd_desember')->nullable();
            $table->double('kewajiban_tidak_terbayar_sd_desember')->nullable();
            $table->date('tanggal_berita_acara')->nullable();
            $table->date('tanggal_surat_pengakuan_hutang')->nullable();

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
        Schema::dropIfExists('acc_padb_tambahan_daftar_pekerjaan');
    }
};
