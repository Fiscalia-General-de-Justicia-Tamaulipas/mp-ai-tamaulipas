<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('case_analyses', function (Blueprint $table): void {
            $table->json('source_snapshot')->nullable();
            $table->string('source_hash', 64)->nullable()->index();
            $table->timestamp('source_changed_at')->nullable();
            $table->boolean('requires_reanalysis')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('case_analyses', function (Blueprint $table): void {
            $table->dropIndex(['source_hash']);
            $table->dropColumn([
                'source_snapshot',
                'source_hash',
                'source_changed_at',
                'requires_reanalysis',
            ]);
        });
    }
};
