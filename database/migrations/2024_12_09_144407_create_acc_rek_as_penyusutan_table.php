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
        Schema::create('acc_rek_as_penyusutan', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('periode_id')->index()->nullable();
            $table->year('year')->nullable();
            $table->integer('instance_id')->nullable();
            $table->integer('unit_id')->nullable();

            $table->text('type')->nullable();
            $table->text('nama_aset')->nullable();

            $table->double('akumulasi_penyusutan', 20, 2)->nullable();
            $table->double('akumulasi_penyusutan_last_year', 20, 2)->nullable();
            $table->double('mutasi_tambah', 20, 2)->nullable();
            $table->double('mutasi_kurang', 20, 2)->nullable();

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
        Schema::dropIfExists('acc_rek_as_penyusutan');
    }
};
