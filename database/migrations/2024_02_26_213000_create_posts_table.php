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
            $table->string('social_id')->nullable();
            $table->string('page_name');
            $table->string('page_id');
            $table->text('message');
            $table->string('media_path', 1000)->nullable();
            $table->json('media_paths')->nullable();
            $table->string('access_token')->nullable();
            $table->string('Programming_options')->default('Publier');
            $table->dateTime('scheduledDateTime')->nullable();
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
