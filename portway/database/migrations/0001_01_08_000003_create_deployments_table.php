<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deployments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained('sites')->cascadeOnDelete();
            $table->foreignId('git_repository_id')->nullable()->constrained('git_repositories')->nullOnDelete();
            $table->foreignId('triggered_by')->nullable()->constrained('users')->nullOnDelete();

            $table->enum('trigger', ['manual', 'webhook', 'system'])->default('manual');
            $table->string('commit_sha', 64)->nullable();
            $table->string('commit_message')->nullable();
            $table->string('branch')->nullable();
            $table->enum('status', ['queued', 'running', 'succeeded', 'failed'])->default('queued');
            $table->longText('log')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->index(['site_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deployments');
    }
};
