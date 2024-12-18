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
        Schema::create('target_tujuan', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('periode_id')->nullable()->unsigned()->index();
            $table->integer('instance_id')->nullable()->unsigned()->index();
            $table->integer('tujuan_id')->nullable()->unsigned()->index();
            $table->integer('ref_id')->nullable()->unsigned()->index()->comment('Indikator Tujuan');
            $table->year('year');
            $table->text('value')->nullable();
            $table->text('last_value')->nullable();
            $table->string('status')->default('active');
            $table->unsignedBigInteger('created_by')->index()->nullable();
            $table->unsignedBigInteger('updated_by')->index()->nullable();
            $table->unsignedBigInteger('deleted_by')->index()->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('instance_id')->references('id')->on('instances');
            $table->foreign('periode_id')->references('id')->on('ref_periode');
            $table->foreign('tujuan_id')->references('id')->on('master_tujuan');
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
        Schema::dropIfExists('target_tujuan');
    }
};
