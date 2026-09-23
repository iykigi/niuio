<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('releases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained('applications')->cascadeOnDelete();
            // Denormalised owner so ownership scoping never needs a join, and
            // so a release surviving in the admin archive still knows who it
            // belonged to.
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            $table->enum('platform', ['windows', 'macos', 'linux']);
            $table->string('version');
            $table->enum('status', ['draft', 'active', 'archived', 'trashed'])->default('draft');

            $table->string('disk')->default('releases');
            $table->string('path')->nullable();
            $table->string('original_filename')->nullable();
            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->string('checksum_sha256', 64)->nullable();
            $table->string('architecture')->nullable();
            $table->string('minimum_os')->nullable();
            $table->text('changelog')->nullable();

            $table->unsignedBigInteger('downloads_count')->default(0);

            $table->timestamp('published_at')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->timestamp('trashed_at')->nullable();
            $table->foreignId('trashed_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            // One build per version per platform, per application.
            $table->unique(['application_id', 'platform', 'version']);
            // The public page's lookup: "the active build for this platform".
            $table->index(['application_id', 'platform', 'status']);
            // The admin archive's lookup.
            $table->index(['status', 'trashed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('releases');
    }
};
