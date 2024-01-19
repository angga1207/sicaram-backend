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
        Schema::create('data_rpjmd_anggaran', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('rpjmd_id')->nullable()->unsigned()->index();
            $table->year('year')->nullable();
            $table->string('anggaran')->nullable();
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
        Schema::dropIfExists('data_rpjmd_anggaran');
    }
};
