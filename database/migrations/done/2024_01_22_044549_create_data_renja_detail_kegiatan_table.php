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
        Schema::create('data_renja_detail_kegiatan', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('renstra_id')->nullable()->unsigned()->index();
            $table->integer('renja_id')->nullable()->unsigned()->index();
            $table->integer('program_id')->nullable()->unsigned()->index();
            $table->integer('kegiatan_id')->nullable()->unsigned()->index();
            $table->json('anggaran_json')->nullable();
            $table->json('anggaran_detail_json')->nullable();
            $table->json('kinerja_json')->nullable();
            $table->json('satuan_json')->nullable();
            $table->year('year')->nullable();

            $table->string('anggaran_modal')->default(0);
            $table->string('anggaran_operasi')->default(0);
            $table->string('anggaran_transfer')->default(0);
            $table->string('anggaran_tidak_terduga')->default(0);
            $table->string('total_anggaran')->default(0);
            $table->string('total_kinerja')->default(0);
            $table->float('percent_anggaran')->default(0);
            $table->float('percent_kinerja')->default(0);
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
        Schema::dropIfExists('data_renja_detail_kegiatan');
    }
};
