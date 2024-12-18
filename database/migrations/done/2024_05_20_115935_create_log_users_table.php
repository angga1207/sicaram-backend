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
        Schema::create('log_users', function (Blueprint $table) {
            $table->bigIncrements('id')->index();
            $table->date('date')->useCurrent();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('ip_address', 45)->index();
            $table->string('user_agent')->nullable();
            $table->json('logs');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('log_users');
    }
};
