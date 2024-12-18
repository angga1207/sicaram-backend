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
        Schema::create('data_apbd', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('periode_id')->nullable()->unsigned()->index();
            $table->integer('instance_id')->nullable()->unsigned()->index();
            $table->integer('program_id')->nullable()->unsigned()->index();
            $table->year('year')->nullable();
            $table->integer('month')->nullable();
            $table->string('total_anggaran')->default(0);
            $table->string('total_kinerja')->default(0);
            $table->float('percent_anggaran')->default(0);
            $table->float('percent_kinerja')->default(0);
            $table->enum('status', ['draft', 'verified', 'reject', 'return', 'sent', 'waiting'])->default('draft');
            $table->enum('status_leader', ['draft', 'verified', 'reject', 'return', 'sent', 'waiting'])->default('draft');
            $table->longText('notes_verificator')->nullable();
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
        Schema::dropIfExists('data_apbd');
    }
};
