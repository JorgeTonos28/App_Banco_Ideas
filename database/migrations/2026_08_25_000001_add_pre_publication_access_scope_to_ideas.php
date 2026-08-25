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
            $table->string('pre_publication_access_scope', 30)
                ->nullable()
                ->after('access_scope');
        });

        DB::table('ideas')
            ->where('publication_status', 'published')
            ->update(['pre_publication_access_scope' => DB::raw('access_scope')]);
    }

    public function down(): void
    {
        Schema::table('ideas', function (Blueprint $table): void {
            $table->dropColumn('pre_publication_access_scope');
        });
    }
};
