<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notes_renstra', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('renstra_id')->unsigned();
            $table->integer('user_id')->unsigned();
            $table->string('status')->nullable();
            $table->string('type')->nullable();
            $table->longText('message')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notes_renstra');
    }
};
