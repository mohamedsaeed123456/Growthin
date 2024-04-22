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
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->string('channel')->nullable();
            $table->string('campaign')->nullable();
            $table->string('content_goal')->nullable();
            $table->json('content_type')->nullable();
            $table->string('post_content',3000)->nullable();
            $table->string('content_image')->nullable();
            $table->date('publication_date')->nullable();
            $table->string('client_status')->nullable();
            $table->string('operation_status')->nullable();
            $table->string('manager_status')->nullable();
            $table->boolean('isPublished')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->foreign('user_id')->references('id')->on('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
