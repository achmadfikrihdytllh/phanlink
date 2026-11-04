<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->text('description')->nullable()->after('avatar');
        });

        Schema::table('conversation_participants', function (Blueprint $table) {
            $table->boolean('is_pinned')->default(false)->after('is_muted');
        });
    }

    public function down(): void
    {
        Schema::table('conversation_participants', function (Blueprint $table) {
            $table->dropColumn('is_pinned');
        });

        Schema::table('conversations', function (Blueprint $table) {
            $table->dropColumn('description');
        });
    }
};
