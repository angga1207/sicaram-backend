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
        Schema::create('acc_persediaan_nilai_persediaan_neraca', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('periode_id')->index()->nullable();
            $table->year('year')->nullable();
            $table->integer('instance_id')->nullable();
            $table->integer('unit_id')->nullable();

            $table->text('nama_persediaan')->nullable();
            $table->double('saldo_awal')->nullable();
            $table->integer('kode_rekening_id')->nullable();
            $table->double('realisasi_lra')->nullable();
            $table->double('hutang_belanja')->nullable();
            $table->double('perolehan_hibah')->nullable();
            $table->double('saldo_akhir')->nullable();
            $table->double('beban_hibah')->nullable();

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
        Schema::dropIfExists('acc_persediaan_nilai_persediaan_neraca');
    }
};
