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
        Schema::create('instance_sub_unit', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['bagian', 'bidang'])->default('bidang');
            $table->integer('instance_id');
            $table->string('name');
            $table->string('alias')->nullable();
            $table->string('code')->nullable();
            $table->integer('created_by')->nullable();
            $table->integer('updated_by')->nullable();
            $table->integer('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('instance_sub_unit');
    }
};
