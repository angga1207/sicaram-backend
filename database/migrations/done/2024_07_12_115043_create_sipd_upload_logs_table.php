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
        Schema::create('sipd_upload_logs', function (Blueprint $table) {
            $table->id();
            $table->text('file_name');
            $table->text('file_path');
            $table->string('status');
            $table->string('type');
            $table->text('message')->nullable();
            $table->integer('user_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sipd_upload_logs');
    }
};
