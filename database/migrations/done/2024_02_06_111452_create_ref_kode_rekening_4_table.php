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
        Schema::create('ref_kode_rekening_4', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('periode_id');
            $table->integer('ref_kode_rekening_1')->nullable()->unsigned()->index();
            $table->integer('ref_kode_rekening_2')->nullable()->unsigned()->index();
            $table->integer('ref_kode_rekening_3')->nullable()->unsigned()->index();
            $table->string('code')->nullable();
            $table->string('fullcode')->nullable();
            $table->text('name')->nullable();
            $table->text('description')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('inactive');
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
        Schema::dropIfExists('ref_kode_rekening_4');
    }
};
