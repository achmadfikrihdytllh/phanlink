<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('theme')->default('phantom');          // phantom | midnight | paper
            $table->string('density')->default('comfortable');    // comfortable | compact
            $table->boolean('desktop_notifications')->default(true);
            $table->boolean('message_previews')->default(false);
            $table->string('who_can_message')->default('contacts'); // contacts | everyone
            $table->boolean('read_receipts')->default(true);
            $table->boolean('show_online_status')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_settings');
    }
};
