<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('git_repositories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained('sites')->cascadeOnDelete();
            $table->enum('provider', ['github', 'gitlab', 'bitbucket', 'generic'])->default('generic');
            $table->string('url');
            $table->string('branch')->default('main');
            $table->text('credentials')->nullable(); // encrypted (PAT / deploy key)
            $table->string('install_command')->nullable();
            $table->string('build_command')->nullable();
            $table->boolean('auto_deploy_on_push')->default(false);
            $table->string('webhook_secret', 64)->nullable();
            $table->string('last_commit_sha', 64)->nullable();
            $table->timestamp('last_deployed_at')->nullable();
            $table->timestamps();
        });

        Schema::table('sites', function (Blueprint $table) {
            $table->foreign('git_repository_id')->references('id')->on('git_repositories')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('sites', function (Blueprint $table) {
            $table->dropForeign(['git_repository_id']);
        });
        Schema::dropIfExists('git_repositories');
    }
};
