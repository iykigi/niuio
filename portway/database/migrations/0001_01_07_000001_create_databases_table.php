<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('databases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('site_id')->nullable()->constrained('sites')->nullOnDelete();
            $table->foreignId('server_id')->nullable()->constrained('servers')->nullOnDelete();

            $table->string('name')->unique(); // physical schema name, prefixed per account
            $table->enum('engine', ['mysql', 'mariadb'])->default('mariadb');
            $table->string('host')->default('127.0.0.1');
            $table->unsignedInteger('port')->default(3306);
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->timestamp('size_calculated_at')->nullable();
            $table->enum('status', ['provisioning', 'active', 'failed', 'deleting'])->default('provisioning');
            $table->timestamps();
            $table->softDeletes();

            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('databases');
    }
};
