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
        Schema::create('acc_rek_as_kib_d', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('periode_id')->index()->nullable();
            $table->year('year')->nullable();
            $table->integer('instance_id')->nullable();
            $table->integer('unit_id')->nullable();

            $table->double('saldo_awal', 20, 2)->nullable();
            $table->double('saldo_akhir', 20, 2)->nullable();

            $table->double('plus_realisasi_belanja', 20, 2)->nullable();
            $table->double('plus_hutang_kegiatan', 20, 2)->nullable();
            $table->double('plus_atribusi', 20, 2)->nullable();
            $table->double('plus_reklasifikasi_barang_habis_pakai', 20, 2)->nullable();
            $table->double('plus_reklasifikasi_pemeliharaan', 20, 2)->nullable();
            $table->double('plus_reklasifikasi_jasa', 20, 2)->nullable();
            $table->double('plus_reklasifikasi_kib_a', 20, 2)->nullable();
            $table->double('plus_reklasifikasi_kib_b', 20, 2)->nullable();
            $table->double('plus_reklasifikasi_kib_c', 20, 2)->nullable();
            $table->double('plus_reklasifikasi_kib_d', 20, 2)->nullable();
            $table->double('plus_reklasifikasi_kib_e', 20, 2)->nullable();
            $table->double('plus_reklasifikasi_kdp', 20, 2)->nullable();
            $table->double('plus_reklasifikasi_aset_lain_lain', 20, 2)->nullable();
            $table->double('plus_hibah_masuk', 20, 2)->nullable();
            $table->double('plus_penilaian', 20, 2)->nullable();
            $table->double('plus_mutasi_antar_opd', 20, 2)->nullable();

            $table->double('min_pembayaran_utang', 20, 2)->nullable();
            $table->double('min_reklasifikasi_beban_persediaan', 20, 2)->nullable();
            $table->double('min_reklasifikasi_beban_pemeliharaan', 20, 2)->nullable();
            $table->double('min_reklasifikasi_beban_hibah', 20, 2)->nullable();
            $table->double('min_reklasifikasi_beban_kib_a', 20, 2)->nullable();
            $table->double('min_reklasifikasi_beban_kib_b', 20, 2)->nullable();
            $table->double('min_reklasifikasi_beban_kib_c', 20, 2)->nullable();
            $table->double('min_reklasifikasi_beban_kib_d', 20, 2)->nullable();
            $table->double('min_reklasifikasi_beban_kib_e', 20, 2)->nullable();
            $table->double('min_reklasifikasi_beban_kdp', 20, 2)->nullable();
            $table->double('min_reklasifikasi_beban_aset_lain_lain', 20, 2)->nullable();
            $table->double('min_penghapusan', 20, 2)->nullable();
            $table->double('min_mutasi_antar_opd', 20, 2)->nullable();
            $table->double('min_tptgr', 20, 2)->nullable();

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
        Schema::dropIfExists('acc_rek_as_kib_d');
    }
};
