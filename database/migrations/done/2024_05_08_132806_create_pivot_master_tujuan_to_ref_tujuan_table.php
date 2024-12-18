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
        Schema::create('pivot_master_tujuan_to_ref_tujuan', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('tujuan_id')->index()->nullable();
            $table->unsignedBigInteger('ref_id')->index()->nullable();
            $table->text('rumus')->nullable();

            $table->foreign('tujuan_id')->references('id')->on('master_tujuan');
            $table->foreign('ref_id')->references('id')->on('ref_indikator_tujuan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pivot_master_tujuan_to_ref_tujuan');
    }
};
