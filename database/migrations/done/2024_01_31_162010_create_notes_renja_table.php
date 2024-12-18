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
        Schema::create('notes_renja', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('renja_id')->unsigned();
            $table->integer('user_id')->unsigned();
            $table->string('status')->nullable();
            $table->string('type')->nullable();
            $table->longText('message')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notes_renja');
    }
};
