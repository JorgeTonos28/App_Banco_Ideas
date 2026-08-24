<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ideas', function (Blueprint $table) {
            $table->foreignId('parent_idea_id')->nullable()->after('category_id')->constrained('ideas')->nullOnDelete();
            $table->index(['parent_idea_id', 'created_at']);
        });

        Schema::create('idea_relations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('source_idea_id')->constrained('ideas')->cascadeOnDelete();
            $table->foreignId('target_idea_id')->constrained('ideas')->cascadeOnDelete();
            $table->string('type');
            $table->string('status')->default('pending');
            $table->text('rationale')->nullable();
            $table->foreignId('created_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('reviewed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->unique(['source_idea_id', 'target_idea_id', 'type'], 'idea_relations_unique_edge');
            $table->index(['target_idea_id', 'status']);
            $table->index(['source_idea_id', 'status']);
        });

        Schema::create('idea_hierarchy_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('idea_id')->constrained('ideas')->cascadeOnDelete();
            $table->foreignId('old_parent_idea_id')->nullable()->constrained('ideas')->nullOnDelete();
            $table->foreignId('new_parent_idea_id')->nullable()->constrained('ideas')->nullOnDelete();
            $table->foreignId('changed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['idea_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('idea_hierarchy_histories');
        Schema::dropIfExists('idea_relations');

        Schema::table('ideas', function (Blueprint $table) {
            $table->dropIndex(['parent_idea_id', 'created_at']);
            $table->dropConstrainedForeignId('parent_idea_id');
        });
    }
};
