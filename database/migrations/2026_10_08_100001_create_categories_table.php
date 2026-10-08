<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->softDeletes();
            $table->foreignId('account_id')->constrained('accounts')->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->string('type', 20)->index();
            $table->boolean('is_system')->default(false)->index();
            $table->string('icon', 100)->nullable();
            $table->string('color', 50)->nullable();
            $table->unsignedInteger('order')->default(0);
            $table->string('status', 20)->default('active')->index();

            $table->index(['account_id', 'type']);
            $table->index(['account_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
