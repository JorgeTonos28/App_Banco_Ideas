<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('category_dimensions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('selection_mode')->default('multiple');
            $table->boolean('is_required')->default(false);
            $table->boolean('is_hierarchical')->default(false);
            $table->boolean('is_primary')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->foreignId('category_dimension_id')->nullable()->after('id')->constrained('category_dimensions')->restrictOnDelete();
            $table->foreignId('parent_id')->nullable()->after('category_dimension_id')->constrained('categories')->nullOnDelete();
            $table->boolean('is_active')->default(true)->after('description');
            $table->unsignedSmallInteger('sort_order')->default(0)->after('is_active');
            $table->index(['category_dimension_id', 'parent_id', 'is_active'], 'categories_dimension_tree_index');
        });

        Schema::create('idea_category', function (Blueprint $table) {
            $table->foreignId('idea_id')->constrained('ideas')->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('categories')->cascadeOnDelete();
            $table->timestamps();

            $table->primary(['idea_id', 'category_id']);
            $table->index(['category_id', 'idea_id']);
        });

        $now = now();
        $primaryDimensionId = DB::table('category_dimensions')->insertGetId([
            'name' => 'Área de innovación',
            'slug' => 'area-de-innovacion',
            'description' => 'Clasificación temática principal heredada del sistema de categorías original.',
            'selection_mode' => 'single',
            'is_required' => true,
            'is_hierarchical' => true,
            'is_primary' => true,
            'is_active' => true,
            'sort_order' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('categories')->update(['category_dimension_id' => $primaryDimensionId]);

        DB::table('ideas')
            ->whereNotNull('category_id')
            ->orderBy('id')
            ->chunkById(500, function ($ideas) use ($now): void {
                DB::table('idea_category')->insertOrIgnore(
                    $ideas->map(fn ($idea) => [
                        'idea_id' => $idea->id,
                        'category_id' => $idea->category_id,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ])->all()
                );
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('idea_category');

        Schema::table('categories', function (Blueprint $table) {
            $table->dropIndex('categories_dimension_tree_index');
            $table->dropConstrainedForeignId('parent_id');
            $table->dropConstrainedForeignId('category_dimension_id');
            $table->dropColumn(['is_active', 'sort_order']);
        });

        Schema::dropIfExists('category_dimensions');
    }
};
