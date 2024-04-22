<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->string('location',1500)->nullable();
            $table->string('period')->nullable();
            $table->string('description',3000)->nullable();
            $table->string('url',1000)->nullable();
            $table->string('social_media_link',1000)->nullable();
            $table->string('positives_question',3000)->nullable();
            $table->string('goals_question',3000)->nullable();
            $table->string('competitors_question',3000)->nullable();
            $table->string('advertising_question')->nullable();
            $table->string('profile_image')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};
