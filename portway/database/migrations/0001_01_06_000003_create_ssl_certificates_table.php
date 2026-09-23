<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ssl_certificates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('domain_id')->constrained('domains')->cascadeOnDelete();
            $table->enum('provider', ['lets_encrypt', 'custom'])->default('lets_encrypt');
            $table->enum('status', [
                'pending', 'issuing', 'active', 'renewing', 'expired', 'failed', 'revoked',
            ])->default('pending');
            $table->string('issuer')->nullable();
            $table->text('certificate')->nullable();
            $table->text('private_key')->nullable(); // encrypted at rest via model cast
            $table->text('chain')->nullable();
            $table->timestamp('issued_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('last_renewal_attempt_at')->nullable();
            $table->text('last_error')->nullable();
            $table->boolean('auto_renew')->default(true);
            $table->timestamps();

            $table->index(['status', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ssl_certificates');
    }
};
