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
        Schema::create('data_realisasi_rincian', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('periode_id')->index()->nullable();
            $table->unsignedBigInteger('realisasi_id')->index()->nullable();
            $table->unsignedBigInteger('target_rincian_id')->index()->nullable();
            $table->text('title')->nullable();

            $table->double('pagu_sipd', 100, 2)->default(0);

            $table->double('anggaran', 100, 2)->default(0);
            $table->double('kinerja', 100, 2)->default(0);
            $table->double('persentase_kinerja', 100, 2)->default(0);

            $table->unsignedBigInteger('created_by')->index()->nullable();
            $table->unsignedBigInteger('updated_by')->index()->nullable();
            $table->unsignedBigInteger('deleted_by')->index()->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('periode_id')->references('id')->on('ref_periode');
            $table->foreign('realisasi_id')->references('id')->on('data_realisasi');
            $table->foreign('target_rincian_id')->references('id')->on('data_target_kinerja_rincian');
            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('updated_by')->references('id')->on('users');
            $table->foreign('deleted_by')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('data_realisasi_rincian');
    }
};
