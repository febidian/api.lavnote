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
        Schema::create('notes', function (Blueprint $table) {
            $table->id();
            $table->foreignUlid('user_id')->constrained('users', 'notes_user_id')
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->uuid('note_id')->unique();
            $table->uuid('duplicate_id')->nullable();
            $table->string('title');
            $table->string('category')->nullable();
            $table->foreignUlid('star_notes_id')->unique()->index();
            $table->foreignUlid('images_notes_id')->unique()->index()->nullable();
            $table->text('note_content');
            $table->timestamps();
            $table->SoftDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notes');
    }
};
