<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('regionals', function (Blueprint $table) {
            $table->foreignId('parent_id')
                ->nullable()
                ->after('id')
                ->constrained('regionals')
                ->nullOnDelete();
            $table->string('type', 20)->default('regional')->after('parent_id');
            $table->index(['parent_id', 'type', 'is_active'], 'regionals_hierarchy_index');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('organizational_unit_id')
                ->nullable()
                ->after('regional_id')
                ->constrained('regionals')
                ->nullOnDelete();
        });

        Schema::table('user_invitations', function (Blueprint $table) {
            $table->foreignId('organizational_unit_id')
                ->nullable()
                ->after('regional_id')
                ->constrained('regionals')
                ->nullOnDelete();
        });

        Schema::table('ideas', function (Blueprint $table) {
            $table->string('requested_community_display', 32)
                ->default('standalone')
                ->after('community_display');
        });

        Schema::create('idea_community_shares', function (Blueprint $table) {
            $table->id();
            $table->foreignId('idea_id')->constrained('ideas')->cascadeOnDelete();
            $table->foreignId('organizational_unit_id')->constrained('regionals')->cascadeOnDelete();
            $table->boolean('include_descendants')->default(false);
            $table->foreignId('shared_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['idea_id', 'organizational_unit_id'], 'idea_community_share_unique');
            $table->index(
                ['organizational_unit_id', 'include_descendants'],
                'idea_community_audience_index'
            );
        });

        DB::table('regionals')->update(['type' => 'regional']);

        DB::table('users')
            ->whereNotNull('regional_id')
            ->update(['organizational_unit_id' => DB::raw('regional_id')]);

        DB::table('user_invitations')
            ->whereNotNull('regional_id')
            ->update(['organizational_unit_id' => DB::raw('regional_id')]);

        DB::table('ideas')->whereNull('parent_idea_id')->update([
            'requested_community_display' => 'standalone',
        ]);
        DB::table('ideas')->whereNotNull('parent_idea_id')->update([
            'requested_community_display' => 'represented_by_parent',
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('idea_community_shares');

        Schema::table('ideas', function (Blueprint $table) {
            $table->dropColumn('requested_community_display');
        });

        Schema::table('user_invitations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('organizational_unit_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('organizational_unit_id');
        });

        Schema::table('regionals', function (Blueprint $table) {
            $table->dropIndex('regionals_hierarchy_index');
            $table->dropConstrainedForeignId('parent_id');
            $table->dropColumn('type');
        });
    }
};
