<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_provider_configs', function (Blueprint $table) {
            $table->id();
            $table->string('provider')->unique();
            $table->text('api_key')->nullable();
            $table->boolean('enabled')->default(false);
            $table->foreignId('configured_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('last_tested_at')->nullable();
            $table->timestamps();
        });

        Schema::create('ai_feature_settings', function (Blueprint $table) {
            $table->id();
            $table->string('feature')->unique();
            $table->string('provider');
            $table->string('model');
            $table->string('reasoning_effort')->nullable();
            $table->string('fallback_model')->nullable();
            $table->string('fallback_reasoning_effort')->nullable();
            $table->boolean('enabled')->default(true);
            $table->json('options')->nullable();
            $table->timestamps();

            $table->index(['provider', 'enabled']);
        });

        Schema::create('ai_usage_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('feature');
            $table->string('provider');
            $table->string('model');
            $table->string('prompt_version')->nullable();
            $table->string('request_id')->nullable();
            $table->boolean('success');
            $table->boolean('escalated')->default(false);
            $table->unsignedInteger('latency_ms')->nullable();
            $table->unsignedInteger('input_units')->nullable();
            $table->unsignedInteger('output_units')->nullable();
            $table->decimal('estimated_cost_usd', 12, 6)->nullable();
            $table->string('error_code')->nullable();
            $table->timestamps();

            $table->index(['feature', 'created_at']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_usage_logs');
        Schema::dropIfExists('ai_feature_settings');
        Schema::dropIfExists('ai_provider_configs');
    }
};
