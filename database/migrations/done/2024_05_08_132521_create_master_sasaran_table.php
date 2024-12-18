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
        Schema::create('master_sasaran', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('instance_id')->index()->nullable();
            $table->unsignedBigInteger('tujuan_id')->index()->nullable();
            $table->unsignedBigInteger('parent_id')->index()->nullable();
            $table->unsignedBigInteger('ref_sasaran_id')->index()->nullable();
            $table->string('status')->default('active');
            $table->unsignedBigInteger('created_by')->index()->nullable();
            $table->unsignedBigInteger('updated_by')->index()->nullable();
            $table->unsignedBigInteger('deleted_by')->index()->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('instance_id')->references('id')->on('instances');
            // $table->foreign('tujuan_id')->references('id')->on('master_tujuan');
            // $table->foreign('parent_id')->references('id')->on('master_sasaran');
            // $table->foreign('ref_sasaran_id')->references('id')->on('ref_sasaran');
            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('updated_by')->references('id')->on('users');
            $table->foreign('deleted_by')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('master_sasaran');
    }
};
