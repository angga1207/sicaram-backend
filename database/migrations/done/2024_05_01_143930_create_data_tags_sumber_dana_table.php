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
        Schema::create('data_tags_sumber_dana', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('sub_kegiatan_id')->nullable();
            $table->unsignedBigInteger('ref_tag_id')->nullable();
            $table->double('nominal', 15, 2)->nullable();
            $table->year('year')->nullable();
            $table->text('notes')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('inactive');
            $table->integer('created_by')->nullable();
            $table->integer('updated_by')->nullable();
            $table->integer('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('sub_kegiatan_id')->references('id')->on('ref_sub_kegiatan');
            $table->foreign('ref_tag_id')->references('id')->on('ref_tag_sumber_dana');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('data_tags_sumber_dana');
    }
};
