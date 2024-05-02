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
        Schema::create('notes_apbd', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('apbd_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('status')->nullable();
            $table->string('type')->nullable();
            $table->longText('message')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('apbd_id')->references('id')->on('data_apbd');
            $table->foreign('user_id')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notes_apbd');
    }
};
