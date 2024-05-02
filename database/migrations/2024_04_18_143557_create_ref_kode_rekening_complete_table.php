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
        Schema::create('ref_kode_rekening_complete', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->integer('periode_id');
            $table->year('year');
            $table->string('code_1')->nullable();
            $table->string('code_2')->nullable();
            $table->string('code_3')->nullable();
            $table->string('code_4')->nullable();
            $table->string('code_5')->nullable();
            $table->string('code_6')->nullable();
            $table->string('fullcode')->nullable();
            $table->text('name')->nullable();
            $table->text('description')->nullable();
            $table->double('pagu_sebelum_pergeseran', 100, 2)->default(0);
            $table->double('pagu_sesudah_pergeseran', 100, 2)->default(0);
            $table->double('pagu_selisih', 100, 2)->default(0);
            $table->enum('status', ['active', 'inactive'])->default('inactive');
            $table->integer('created_by')->nullable();
            $table->integer('updated_by')->nullable();
            $table->integer('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('parent_id')->references('id')->on('ref_kode_rekening_complete');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ref_kode_rekening_complete');
    }
};
