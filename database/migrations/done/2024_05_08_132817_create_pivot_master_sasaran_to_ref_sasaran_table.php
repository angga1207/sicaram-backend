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
        Schema::create('pivot_master_sasaran_to_ref_sasaran', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('sasaran_id')->index()->nullable();
            $table->unsignedBigInteger('ref_id')->index()->nullable();
            $table->text('rumus')->nullable();

            $table->foreign('sasaran_id')->references('id')->on('master_sasaran');
            $table->foreign('ref_id')->references('id')->on('ref_indikator_sasaran');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pivot_master_sasaran_to_ref_sasaran');
    }
};
