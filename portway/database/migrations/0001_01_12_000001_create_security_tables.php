<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('login_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('email')->nullable();
            $table->string('ip_address', 45);
            $table->string('user_agent')->nullable();
            $table->boolean('successful')->default(false);
            $table->string('failure_reason')->nullable(); // "invalid_credentials", "2fa_failed", ...
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['ip_address', 'created_at']);
        });

        Schema::create('blocked_ips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('ip_address', 45);
            $table->string('cidr')->nullable();
            $table->string('reason')->nullable();
            $table->enum('scope', ['account', 'platform'])->default('account');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'ip_address']);
        });

        Schema::create('file_change_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained('sites')->cascadeOnDelete();
            $table->string('path');
            $table->enum('event', ['created', 'modified', 'deleted', 'permissions_changed']);
            $table->string('hash_before', 64)->nullable();
            $table->string('hash_after', 64)->nullable();
            $table->boolean('flagged')->default(false);
            $table->timestamp('detected_at');

            $table->index(['site_id', 'flagged']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('file_change_events');
        Schema::dropIfExists('blocked_ips');
        Schema::dropIfExists('login_attempts');
    }
};
