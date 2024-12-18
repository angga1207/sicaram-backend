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
        Schema::create('target_perubahan_sasaran', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('periode_id')->nullable()->unsigned()->index();
            $table->integer('instance_id')->nullable()->unsigned()->index();
            $table->integer('tujuan_id')->nullable()->unsigned()->index();
            $table->integer('sasaran_id')->nullable()->unsigned()->index();
            $table->integer('ref_id')->nullable()->unsigned()->index()->comment('Indikator Sasaran');
            $table->integer('parent_id')->unsigned()->index();
            $table->year('year');
            $table->text('value_1')->nullable();
            $table->text('value_2')->nullable();
            $table->text('value_3')->nullable();
            $table->text('value_4')->nullable();
            $table->text('value_5')->nullable();
            $table->text('value_6')->nullable();
            $table->text('value_7')->nullable();
            $table->text('value_8')->nullable();
            $table->text('value_9')->nullable();
            $table->text('value_10')->nullable();
            $table->text('value_11')->nullable();
            $table->text('value_12')->nullable();
            $table->string('status')->default('active');
            $table->unsignedBigInteger('created_by')->index()->nullable();
            $table->unsignedBigInteger('updated_by')->index()->nullable();
            $table->unsignedBigInteger('deleted_by')->index()->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('instance_id')->references('id')->on('instances');
            $table->foreign('periode_id')->references('id')->on('ref_periode');
            $table->foreign('tujuan_id')->references('id')->on('master_tujuan');
            $table->foreign('sasaran_id')->references('id')->on('master_sasaran');
            $table->foreign('parent_id')->references('id')->on('target_sasaran');
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
        Schema::dropIfExists('target_perubahan_sasaran');
    }
};
