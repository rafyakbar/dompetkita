<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->softDeletes();
            $table->timestamp('happened_at')->index();
            $table->foreignId('account_id')->constrained('accounts')->cascadeOnDelete();
            $table->foreignId('wallet_id')->constrained('wallets')->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->unsignedBigInteger('transfer_id')->nullable()->index();
            $table->string('type', 20)->default('transaction')->index();
            $table->smallInteger('direction')->index();
            $table->decimal('amount', 24, 2)->index();
            $table->string('category_name')->nullable();
            $table->string('wallet_name');
            $table->text('note')->nullable();

            $table->index(['account_id', 'happened_at']);
            $table->index(['wallet_id', 'happened_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
