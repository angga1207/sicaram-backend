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
        Schema::create('data_realisasi_status', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('sub_kegiatan_id')->index()->nullable();
            $table->integer('month')->nullable();
            $table->year('year')->nullable();
            $table->string('status')->nullable();
            $table->string('status_leader')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('sub_kegiatan_id')->references('id')->on('ref_sub_kegiatan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('data_realisasi_status');
    }
};
