<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contact_id')->constrained('users')->cascadeOnDelete();
            $table->string('nickname')->nullable(); // shown as the "codename" in the UI
            $table->text('note')->nullable();       // personal note, visible only to the owner
            $table->boolean('is_muted')->default(false);
            $table->boolean('is_blocked')->default(false);
            $table->timestamps();

            $table->unique(['user_id', 'contact_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contacts');
    }
};
