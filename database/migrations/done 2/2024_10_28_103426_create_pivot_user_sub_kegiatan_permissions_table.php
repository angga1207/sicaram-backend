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
        Schema::create('pivot_user_sub_kegiatan_permissions', function (Blueprint $table) {
            $table->integer('user_id');
            $table->integer('periode_id');
            $table->integer('program_id');
            $table->integer('kegiatan_id');
            $table->integer('sub_kegiatan_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pivot_user_sub_kegiatan_permissions');
    }
};
