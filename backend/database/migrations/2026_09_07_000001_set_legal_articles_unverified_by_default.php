<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('legal_articles', function (Blueprint $table): void {
            $table->boolean('is_verified')->default(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('legal_articles', function (Blueprint $table): void {
            $table->boolean('is_verified')->default(true)->change();
        });
    }
};
