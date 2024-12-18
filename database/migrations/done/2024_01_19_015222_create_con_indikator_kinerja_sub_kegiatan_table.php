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
        Schema::create('con_indikator_kinerja_sub_kegiatan', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('instance_id')->unsigned()->index();
            $table->integer('program_id')->unsigned()->index();
            $table->integer('kegiatan_id')->unsigned()->index();
            $table->integer('sub_kegiatan_id')->unsigned()->index();
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
        Schema::dropIfExists('con_indikator_kinerja_sub_kegiatan');
    }
};
