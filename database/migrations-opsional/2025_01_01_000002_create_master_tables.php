<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Master atribut: fandom, fandom_character, merchandise_type + voting desain. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fandom', function (Blueprint $t) {
            $t->id();
            $t->string('fandom_name', 120)->unique('uq_fandom_name');
            $t->enum('status', ['active', 'pending', 'rejected'])->default('active');
            $t->foreignId('proposed_by')->nullable()->constrained('user')->nullOnDelete()->cascadeOnUpdate();
            $t->index('status', 'idx_fandom_status');
        });

        Schema::create('fandom_character', function (Blueprint $t) {
            $t->id();
            $t->foreignId('fandom_id')->constrained('fandom')->cascadeOnDelete()->cascadeOnUpdate();
            $t->string('character_name', 120);
            $t->enum('status', ['active', 'pending', 'rejected'])->default('active');
            $t->foreignId('proposed_by')->nullable()->constrained('user')->nullOnDelete()->cascadeOnUpdate();
            $t->unique(['fandom_id', 'character_name'], 'uq_character_fandom_name');
            $t->index('status', 'idx_character_status');
        });

        Schema::create('merchandise_type', function (Blueprint $t) {
            $t->id();
            $t->string('type_name', 80)->unique('uq_type_name');
            $t->enum('status', ['active', 'pending', 'rejected'])->default('active');
            $t->foreignId('proposed_by')->nullable()->constrained('user')->nullOnDelete()->cascadeOnUpdate();
            $t->index('status', 'idx_type_status');
        });

        Schema::create('design_poll', function (Blueprint $t) {
            $t->id();
            $t->foreignId('artist_profile_id')->constrained('artist_profile')->cascadeOnUpdate();
            $t->string('poll_title', 150);
            $t->text('description')->nullable();
            $t->dateTime('start_date');
            $t->dateTime('end_date');
            $t->enum('poll_status', ['running', 'completed', 'cancelled'])->default('running');
            $t->dateTime('created_at')->useCurrent();
            $t->dateTime('updated_at')->nullable();
        });

        Schema::create('design_option', function (Blueprint $t) {
            $t->id();
            $t->foreignId('design_poll_id')->constrained('design_poll')->cascadeOnDelete()->cascadeOnUpdate();
            $t->string('design_image', 255);
            $t->text('description')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('design_option');
        Schema::dropIfExists('design_poll');
        Schema::dropIfExists('merchandise_type');
        Schema::dropIfExists('fandom_character');
        Schema::dropIfExists('fandom');
    }
};
