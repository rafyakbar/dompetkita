<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('account_member', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->timestamp('invited_at')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('left_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->foreignId('account_id')->constrained('accounts')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->string('email')->index();
            $table->string('invitation_token', 64)->nullable()->unique();
            $table->string('role', 20)->default('member')->index();
            $table->string('status', 20)->default('invited')->index();

            $table->unique(['account_id', 'email']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_member');
    }
};
