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
        Schema::create('acc_belanja_bayar_dimuka', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('periode_id')->index()->nullable();
            $table->year('year')->nullable();
            $table->integer('instance_id')->nullable();
            $table->integer('unit_id')->nullable();

            $table->integer('kode_rekening_id')->nullable();
            $table->text('uraian')->nullable();
            $table->text('nomor_perjanjian')->nullable();
            $table->date('tanggal_perjanjian')->nullable();
            $table->text('rekanan')->nullable();
            $table->text('jangka_waktu')->nullable();

            $table->date('kontrak_date_start')->nullable();
            $table->date('kontrak_date_end')->nullable();
            $table->double('kontrak_value', 20, 2)->nullable();

            $table->double('sudah_jatuh_tempo', 20, 2)->nullable();
            $table->double('belum_jatuh_tempo', 20, 2)->nullable();

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
        Schema::dropIfExists('acc_belanja_bayar_dimuka');
    }
};
