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
        Schema::create('notes_realisasi', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('data_id')->index()->nullable();
            $table->integer('user_id')->unsigned();
            $table->string('status')->nullable();
            $table->string('type')->nullable();
            $table->longText('message')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('data_id')->references('id')->on('data_realisasi_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notes_realisasi');
    }
};
