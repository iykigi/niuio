<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('servers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('hostname');
            $table->string('ip_address', 45);
            $table->string('internal_ip', 45)->nullable();
            $table->string('region')->nullable();
            $table->enum('role', ['web', 'database', 'combined', 'backup'])->default('combined');
            $table->enum('status', ['pending', 'online', 'degraded', 'offline', 'maintenance'])->default('pending');
            $table->enum('web_server', ['nginx', 'apache'])->default('nginx');
            $table->json('installed_php_versions')->nullable();
            $table->json('installed_node_versions')->nullable();

            // Capacity used to pick a node when creating a new website.
            $table->unsignedInteger('max_sites')->default(500);
            $table->unsignedInteger('current_sites')->default(0);
            $table->unsignedInteger('disk_total_mb')->nullable();
            $table->unsignedInteger('disk_used_mb')->nullable();
            $table->decimal('cpu_load', 5, 2)->nullable();
            $table->decimal('memory_used_percent', 5, 2)->nullable();

            // Connection details for the "ssh" provisioner driver. The
            // private key/passphrase are encrypted at rest.
            $table->unsignedInteger('ssh_port')->default(22);
            $table->string('ssh_user')->default('portway');
            $table->text('ssh_private_key')->nullable();
            $table->boolean('is_control_plane')->default(false);
            $table->timestamp('last_heartbeat_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('servers');
    }
};
