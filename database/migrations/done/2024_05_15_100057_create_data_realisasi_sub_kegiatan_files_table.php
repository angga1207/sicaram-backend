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
        Schema::create('data_realisasi_sub_kegiatan_files', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('parent_id')
                ->index()
                ->comment('data_realisasi_sub_kegiatan_id')
                ->nullable();
            $table->string('type')->nullable();
            $table->string('save_to')->nullable();
            $table->longText('file')->nullable();
            $table->longText('filename')->nullable();
            $table->longText('path')->nullable();
            $table->double('size', 100, 2)->default(0);
            $table->string('extension')->nullable();
            $table->string('mime_type')->nullable();

            $table->unsignedBigInteger('created_by')->index()->nullable();
            $table->unsignedBigInteger('updated_by')->index()->nullable();
            $table->unsignedBigInteger('deleted_by')->index()->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('data_realisasi_sub_kegiatan_files');
    }
};
