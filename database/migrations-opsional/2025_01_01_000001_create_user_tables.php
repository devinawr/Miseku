<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Subset skema Miseku (v4.1) untuk modul awal: user, artist_profile, address.
 * Nama tabel sengaja SINGULAR, mengikuti ERD TA (bukan konvensi plural Laravel).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user', function (Blueprint $t) {
            $t->id();
            $t->string('name', 120);
            $t->string('email', 150)->unique('uq_user_email');
            $t->string('password', 255);
            $t->string('phone', 20)->nullable();
            $t->enum('role', ['admin', 'artist', 'buyer'])->default('buyer');
            $t->string('profile_photo', 255)->nullable();
            $t->boolean('is_active')->default(true);
            $t->decimal('rating_as_buyer', 3, 2)->default(0);
            $t->unsignedInteger('rating_count_as_buyer')->default(0);
            $t->dateTime('created_at')->useCurrent();
            $t->dateTime('updated_at')->nullable();
            $t->index('role', 'idx_user_role');
        });

        Schema::create('artist_profile', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained('user')->cascadeOnUpdate();
            $t->string('shop_name', 120);
            $t->text('shop_description')->nullable();
            $t->string('bank_name', 60)->nullable();
            $t->string('bank_account_number', 40)->nullable();
            $t->decimal('available_balance', 14, 2)->default(0);
            $t->decimal('held_balance', 14, 2)->default(0);
            $t->decimal('rating', 3, 2)->default(0);
            $t->unsignedInteger('rating_count')->default(0);
            $t->dateTime('created_at')->useCurrent();
            $t->dateTime('updated_at')->nullable();
        });

        Schema::create('address', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained('user')->cascadeOnUpdate();
            $t->string('address_label', 60);
            $t->string('recipient_name', 120);
            $t->text('full_address');
            $t->string('rajaongkir_city_id', 20);
            $t->string('province', 80);
            $t->string('postal_code', 10)->nullable();
            $t->boolean('is_primary')->default(false);
            $t->dateTime('created_at')->useCurrent();
            $t->dateTime('updated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('address');
        Schema::dropIfExists('artist_profile');
        Schema::dropIfExists('user');
    }
};
