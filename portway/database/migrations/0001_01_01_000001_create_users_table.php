<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');

            // --- Hosting account fields ---------------------------------
            $table->unsignedInteger('storage_quota_mb')->default(config('portway.defaults.storage_quota_mb'));
            $table->unsignedInteger('bandwidth_quota_mb')->default(config('portway.defaults.bandwidth_quota_mb'));
            $table->unsignedInteger('max_websites')->default(config('portway.defaults.max_websites'));
            $table->unsignedInteger('max_databases')->default(config('portway.defaults.max_databases'));
            $table->unsignedInteger('max_domains')->default(config('portway.defaults.max_domains'));
            $table->unsignedInteger('max_cron_jobs')->default(config('portway.defaults.max_cron_jobs'));
            $table->unsignedInteger('max_backups')->default(config('portway.defaults.max_backups'));
            $table->unsignedInteger('max_email_accounts')->default(config('portway.defaults.max_email_accounts'));
            $table->string('timezone')->default('UTC');
            $table->string('locale', 10)->default('en');

            // --- Suspension ----------------------------------------------
            $table->boolean('is_suspended')->default(false);
            $table->string('suspension_reason')->nullable();
            $table->timestamp('suspended_at')->nullable();
            $table->foreignId('suspended_by')->nullable()->constrained('users')->nullOnDelete();

            // --- Two factor authentication ---------------------------------
            $table->text('two_factor_secret')->nullable();
            $table->text('two_factor_recovery_codes')->nullable();
            $table->timestamp('two_factor_confirmed_at')->nullable();

            // --- Misc --------------------------------------------------
            $table->timestamp('last_seen_at')->nullable();
            $table->string('last_seen_ip', 45)->nullable();
            $table->rememberToken();
            $table->softDeletes();
            $table->timestamps();

            $table->index('is_suspended');
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};
