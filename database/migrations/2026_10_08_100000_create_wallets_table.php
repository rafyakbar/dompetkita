<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallets', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->softDeletes();
            $table->foreignId('account_id')->constrained('accounts')->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->string('icon', 100)->nullable();
            $table->string('color', 50)->nullable();
            $table->decimal('current_balance', 24, 2)->default(0.00);
            $table->boolean('allow_minus')->default(false);

            $table->index(['account_id', 'slug']);
            $table->index(['account_id', 'current_balance']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallets');
    }
};
