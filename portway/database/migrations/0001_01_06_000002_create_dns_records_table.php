<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dns_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('domain_id')->constrained('domains')->cascadeOnDelete();
            $table->enum('type', ['A', 'AAAA', 'CNAME', 'MX', 'TXT', 'SRV', 'CAA', 'NS']);
            $table->string('name'); // "@", "www", "api", ...
            $table->text('content');
            $table->unsignedInteger('ttl')->default(3600);
            $table->unsignedInteger('priority')->nullable(); // MX / SRV
            $table->unsignedInteger('weight')->nullable();   // SRV
            $table->unsignedInteger('port')->nullable();     // SRV
            $table->boolean('is_managed_by_portway')->default(true);
            $table->enum('status', ['pending', 'propagating', 'active', 'error'])->default('pending');
            $table->timestamps();

            $table->index(['domain_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dns_records');
    }
};
