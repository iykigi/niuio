<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('domains', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained('sites')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            $table->string('hostname')->unique(); // e.g. example.com, www.example.com, api.example.com
            $table->enum('type', ['temporary', 'primary', 'alias', 'www', 'subdomain', 'wildcard'])->default('primary');
            $table->string('document_root_override')->nullable();

            $table->enum('status', [
                'pending_dns', 'dns_detected', 'connected', 'ssl_installing', 'active', 'failed', 'suspended',
            ])->default('pending_dns');

            // Ownership verification: a random token the user must publish
            // as a TXT record (or that we accept once A/CNAME already
            // resolves to one of our nodes) before the domain is trusted.
            $table->string('verification_token', 64)->nullable();
            $table->timestamp('verified_at')->nullable();

            $table->timestamp('last_dns_check_at')->nullable();
            $table->json('last_dns_check_result')->nullable();

            $table->boolean('force_https')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'status']);
            $table->index('site_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('domains');
    }
};
