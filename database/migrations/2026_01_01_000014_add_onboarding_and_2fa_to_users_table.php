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
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('regional_id')->nullable()->after('regional')->constrained('regionals')->nullOnDelete();
            $table->boolean('must_change_password')->default(false)->after('password');
            $table->boolean('two_factor_enabled')->default(false)->after('is_active');
            $table->enum('two_factor_type', ['totp', 'email'])->nullable()->after('two_factor_enabled');
            $table->text('two_factor_secret')->nullable()->after('two_factor_type');
            $table->text('two_factor_recovery_codes')->nullable()->after('two_factor_secret');
            $table->string('two_factor_code', 10)->nullable()->after('two_factor_recovery_codes');
            $table->timestamp('two_factor_expires_at')->nullable()->after('two_factor_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['regional_id']);
            $table->dropColumn([
                'regional_id',
                'must_change_password',
                'two_factor_enabled',
                'two_factor_type',
                'two_factor_secret',
                'two_factor_recovery_codes',
                'two_factor_code',
                'two_factor_expires_at',
            ]);
        });
    }
};
