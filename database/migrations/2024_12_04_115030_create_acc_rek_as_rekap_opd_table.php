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
        Schema::create('acc_rek_as_rekap_opd', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('periode_id')->index()->nullable();
            $table->year('year')->nullable();
            $table->integer('instance_id')->nullable();
            $table->integer('unit_id')->nullable();

            $table->double('tanah', 20, 2)->nullable();
            $table->double('tanah_last_year', 20, 2)->nullable();

            $table->double('peralatan_mesin', 20, 2)->nullable();
            $table->double('peralatan_mesin_last_year', 20, 2)->nullable();

            $table->double('gedung_bangunan', 20, 2)->nullable();
            $table->double('gedung_bangunan_last_year', 20, 2)->nullable();

            $table->double('jalan_jaringan_irigasi', 20, 2)->nullable();
            $table->double('jalan_jaringan_irigasi_last_year', 20, 2)->nullable();

            $table->double('aset_tetap_lainnya', 20, 2)->nullable();
            $table->double('aset_tetap_lainnya_last_year', 20, 2)->nullable();

            $table->double('kdp', 20, 2)->nullable();
            $table->double('kdp_last_year', 20, 2)->nullable();

            $table->double('aset_lainnya', 20, 2)->nullable();
            $table->double('aset_lainnya_last_year', 20, 2)->nullable();

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
        Schema::dropIfExists('acc_rek_as_rekap_opd');
    }
};
