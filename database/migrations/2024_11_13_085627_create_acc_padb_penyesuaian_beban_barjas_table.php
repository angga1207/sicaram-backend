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
        Schema::create('acc_padb_penyesuaian_beban_barjas', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('periode_id')->index()->nullable();
            $table->year('year')->nullable();
            $table->integer('instance_id')->nullable();
            $table->integer('unit_id')->nullable();
            $table->integer('kode_rekening_id')->nullable();
            $table->text('nama_barang_pekerjaan')->nullable();
            $table->text('nomor_kontrak')->nullable();
            $table->text('nomor_sp2d')->nullable();

            $table->double('plus_beban_pegawai')->nullable();
            $table->double('plus_beban_persediaan')->nullable();
            $table->double('plus_beban_jasa')->nullable();
            $table->double('plus_beban_pemeliharaan')->nullable();
            $table->double('plus_beban_perjalanan_dinas')->nullable();
            $table->double('plus_beban_hibah')->nullable();
            $table->double('plus_beban_lain_lain')->nullable();
            $table->double('plus_jumlah_penyesuaian')->nullable();

            $table->double('min_beban_pegawai')->nullable();
            $table->double('min_beban_persediaan')->nullable();
            $table->double('min_beban_jasa')->nullable();
            $table->double('min_beban_pemeliharaan')->nullable();
            $table->double('min_beban_perjalanan_dinas')->nullable();
            $table->double('min_beban_hibah')->nullable();
            $table->double('min_beban_lain_lain')->nullable();
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
        Schema::dropIfExists('acc_padb_penyesuaian_beban_barjas');
    }
};
