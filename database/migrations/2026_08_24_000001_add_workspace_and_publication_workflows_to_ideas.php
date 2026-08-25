<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ideas', function (Blueprint $table) {
            $table->string('visibility')->default('private')->change();
            $table->string('workspace_status')->default('capturada')->after('visibility');
            $table->string('publication_status')->default('not_submitted')->after('workspace_status');
            $table->string('community_display')->default('hidden')->after('publication_status');
            $table->timestamp('publication_requested_at')->nullable()->after('community_display');
            $table->foreignId('publication_requested_by_user_id')->nullable()->after('publication_requested_at')->constrained('users')->nullOnDelete();
            $table->timestamp('publication_reviewed_at')->nullable()->after('publication_requested_by_user_id');
            $table->foreignId('publication_reviewed_by_user_id')->nullable()->after('publication_reviewed_at')->constrained('users')->nullOnDelete();
            $table->timestamp('published_at')->nullable()->after('publication_reviewed_by_user_id');
            $table->text('publication_notes')->nullable()->after('published_at');

            $table->index(['publication_status', 'community_display'], 'ideas_publication_display_index');
            $table->index(['visibility', 'workspace_status'], 'ideas_workspace_visibility_index');
        });

        Schema::table('idea_status_histories', function (Blueprint $table) {
            $table->string('workflow')->default('community')->after('user_id');
            $table->index(['idea_id', 'workflow']);
        });

        DB::table('ideas')
            ->where('visibility', 'public')
            ->update([
                'publication_status' => 'published',
                'community_display' => 'standalone',
                'publication_requested_at' => DB::raw('created_at'),
                'publication_reviewed_at' => DB::raw('created_at'),
                'published_at' => DB::raw('created_at'),
            ]);
    }

    public function down(): void
    {
        Schema::table('idea_status_histories', function (Blueprint $table) {
            $table->dropIndex(['idea_id', 'workflow']);
            $table->dropColumn('workflow');
        });

        Schema::table('ideas', function (Blueprint $table) {
            $table->dropIndex('ideas_publication_display_index');
            $table->dropIndex('ideas_workspace_visibility_index');
            $table->dropConstrainedForeignId('publication_requested_by_user_id');
            $table->dropConstrainedForeignId('publication_reviewed_by_user_id');
            $table->dropColumn([
                'workspace_status',
                'publication_status',
                'community_display',
                'publication_requested_at',
                'publication_reviewed_at',
                'published_at',
                'publication_notes',
            ]);
            $table->string('visibility')->default('public')->change();
        });
    }
};
