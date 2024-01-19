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
        Schema::create('data_rpjmd', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('periode_id')->nullable()->unsigned()->index();
            $table->integer('instance_id')->nullable()->unsigned()->index();
            $table->integer('program_id')->nullable()->unsigned()->index();
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
        Schema::dropIfExists('data_rpjmd');
    }
};
