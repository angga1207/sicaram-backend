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
        Schema::create('ref_program', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('instance_id')->nullable()->unsigned()->index();
            $table->integer('urusan_id')->nullable()->unsigned()->index();
            $table->integer('bidang_id')->nullable()->unsigned()->index();
            $table->string('code')->nullable();
            $table->string('fullcode')->nullable();
            $table->text('name')->nullable();
            $table->text('description')->nullable();
            $table->integer('periode_id');
            $table->enum('status', ['active', 'inactive'])->default('inactive');
            $table->integer('created_by')->nullable();
            $table->integer('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ref_program');
    }
};
