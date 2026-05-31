<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workspaces', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('name', 120);
            $table->string('slug', 140);
            $table->text('description')->nullable();
            $table->string('plan_model', 20)->default('micro');
            $table->timestamp('guidelines_accepted_at')->nullable();
            $table->json('security_manifest')->nullable();
            $table->timestamps();

            $table->unique(['owner_user_id', 'slug']);
            $table->index(['owner_user_id', 'plan_model']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workspaces');
    }
};
