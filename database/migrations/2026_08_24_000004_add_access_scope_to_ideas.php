<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ideas', function (Blueprint $table): void {
            $table->string('access_scope')->default('only_me')->after('visibility');
            $table->index(['access_scope', 'parent_idea_id'], 'ideas_access_scope_parent_index');
        });

        DB::table('ideas')
            ->where('publication_status', 'published')
            ->update(['access_scope' => 'profile']);
    }

    public function down(): void
    {
        Schema::table('ideas', function (Blueprint $table): void {
            $table->dropIndex('ideas_access_scope_parent_index');
            $table->dropColumn('access_scope');
        });
    }
};
