<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('database_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('database_id')->constrained('databases')->cascadeOnDelete();
            $table->string('username')->unique();
            $table->text('password'); // encrypted at rest via model cast
            $table->json('privileges')->nullable(); // e.g. ["ALL"] or ["SELECT","INSERT",...]
            $table->string('host')->default('%');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('database_users');
    }
};
