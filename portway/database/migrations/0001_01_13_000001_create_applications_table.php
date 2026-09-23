<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('site_id')->nullable()->constrained('sites')->nullOnDelete();

            $table->string('name');
            $table->string('slug')->unique();
            $table->string('tagline')->nullable();
            $table->text('description')->nullable();
            $table->string('website_url')->nullable();
            $table->string('support_email')->nullable();
            $table->string('icon_path')->nullable();

            // When false the public download page 404s even if live builds
            // exist — the owner's private kill switch for the whole listing.
            $table->boolean('is_listed')->default(true);

            $table->unsignedBigInteger('downloads_count')->default(0);
            $table->timestamps();

            $table->index(['user_id', 'is_listed']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('applications');
    }
};
