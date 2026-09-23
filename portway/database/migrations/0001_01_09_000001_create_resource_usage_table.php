<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resource_usage', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('site_id')->nullable()->constrained('sites')->nullOnDelete();

            $table->date('date');
            $table->unsignedBigInteger('storage_bytes')->default(0);
            $table->unsignedBigInteger('bandwidth_bytes')->default(0);
            $table->unsignedInteger('requests')->default(0);
            $table->unsignedInteger('errors_5xx')->default(0);
            $table->unsignedInteger('errors_4xx')->default(0);
            $table->decimal('cpu_seconds', 10, 2)->default(0);
            $table->decimal('avg_memory_mb', 10, 2)->nullable();
            $table->unsignedInteger('db_connections_peak')->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'site_id', 'date']);
            $table->index('date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resource_usage');
    }
};
