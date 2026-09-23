<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('domain_id')->constrained('domains')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            $table->string('local_part'); // "admin" in admin@example.com
            $table->string('address')->unique(); // full "admin@example.com"
            $table->text('password'); // encrypted
            $table->unsignedInteger('quota_mb')->default(1024);
            $table->unsignedBigInteger('used_bytes')->default(0);
            $table->string('forward_to')->nullable();
            $table->boolean('autoresponder_enabled')->default(false);
            $table->string('autoresponder_subject')->nullable();
            $table->text('autoresponder_body')->nullable();
            $table->enum('status', ['provisioning', 'active', 'suspended'])->default('provisioning');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_accounts');
    }
};
