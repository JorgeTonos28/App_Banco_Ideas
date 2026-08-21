<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ideas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('summary')->nullable();
            $table->longText('description');
            $table->longText('problem_opportunity')->nullable();
            $table->string('status')->default('nueva'); // 'nueva', 'en_revision', 'priorizada', 'en_desarrollo', 'implementada', 'descartada', 'archivada'
            $table->string('visibility')->default('public'); // 'public', 'draft'
            $table->boolean('is_featured')->default(false);
            $table->string('priority')->nullable(); // 'baja', 'media', 'alta', 'estrategica'
            $table->foreignId('assigned_to_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('admin_observations')->nullable();
            $table->string('next_action')->nullable();
            $table->date('follow_up_date')->nullable();
            $table->unsignedInteger('views_count')->default(0);
            $table->unsignedInteger('votes_count')->default(0);
            $table->decimal('average_rating', 3, 2)->default(0.00);
            $table->unsignedInteger('innovation_score')->default(0);
            $table->timestamp('implemented_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'visibility']);
            $table->index('innovation_score');
            $table->index('average_rating');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ideas');
    }
};
