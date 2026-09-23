<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('server_id')->nullable()->constrained('servers')->nullOnDelete();

            $table->string('name');
            $table->string('slug')->unique(); // used in the filesystem path & temp domain
            $table->string('project_type'); // key from config('portway.project_types')
            $table->enum('runtime', ['php', 'static', 'node'])->default('php');

            $table->string('document_root')->default('public');
            $table->string('php_version')->nullable();
            $table->string('node_version')->nullable();

            // Node.js process configuration.
            $table->string('node_install_command')->nullable();
            $table->string('node_build_command')->nullable();
            $table->string('node_start_command')->nullable();
            $table->unsignedInteger('node_port')->nullable();
            $table->enum('node_process_status', ['stopped', 'starting', 'running', 'crashed'])->nullable();

            // PHP runtime settings (safe subset exposed to users).
            $table->unsignedInteger('php_memory_limit_mb')->default(256);
            $table->unsignedInteger('php_upload_max_mb')->default(64);
            $table->unsignedInteger('php_max_execution_seconds')->default(30);
            $table->json('php_extensions')->nullable();

            $table->enum('status', [
                'provisioning', 'active', 'suspended', 'failed', 'deleting',
            ])->default('provisioning');
            $table->text('status_message')->nullable();
            $table->unsignedTinyInteger('provisioning_progress')->default(0);

            $table->boolean('force_https')->default(true);
            $table->unsignedBigInteger('disk_usage_bytes')->default(0);
            $table->unsignedBigInteger('bandwidth_used_mb')->default(0);
            $table->timestamp('disk_usage_calculated_at')->nullable();

            $table->foreignId('git_repository_id')->nullable();

            $table->timestamp('last_deployed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sites');
    }
};
