<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('books', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->string('author')->nullable();
            $table->string('category');
            $table->enum('type', ['sale', 'exchange']);
            $table->decimal('price', 10, 2)->nullable();
            $table->text('description')->nullable();
            $table->string('condition')->nullable();
            $table->enum('status', ['available', 'sold', 'exchanged'])->default('available');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('books');
    }
};