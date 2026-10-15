<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * customer_order + order_item + review + product_review + product_view.
 * CATATAN: kolom event_booth_id & custom_request_id tetap ada agar setia pada ERD,
 * tapi FK-nya belum dipasang karena tabel event_booth / custom_request belum masuk
 * subset modul awal. Pasang constraint-nya saat modul event & custom dikerjakan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_order', function (Blueprint $t) {
            $t->id();
            $t->string('order_code', 40)->unique('uq_order_code');
            $t->foreignId('buyer_id')->constrained('user')->restrictOnDelete()->cascadeOnUpdate();
            $t->foreignId('artist_profile_id')->constrained('artist_profile')->restrictOnDelete()->cascadeOnUpdate();
            $t->foreignId('address_id')->nullable()->constrained('address')->nullOnDelete()->cascadeOnUpdate();
            $t->unsignedBigInteger('event_booth_id')->nullable();
            $t->unsignedBigInteger('custom_request_id')->nullable()->unique('uq_order_request'); // 1:0..1
            $t->enum('order_type', ['ready_stock', 'pre_order', 'custom', 'ots_event']);
            $t->enum('shipping_method', ['courier', 'event_pickup'])->nullable();
            $t->enum('payment_scheme', ['full', 'dp'])->default('full');
            $t->decimal('product_subtotal', 14, 2)->default(0);
            $t->decimal('shipping_cost', 12, 2)->default(0);
            $t->decimal('admin_fee', 12, 2)->default(0);
            $t->decimal('total_amount', 14, 2)->default(0);
            $t->enum('order_status', [
                'awaiting_payment', 'paid', 'awaiting_production', 'in_production',
                'production_completed', 'awaiting_settlement', 'shipped',
                'ready_for_pickup', 'completed', 'cancelled', 'failed',
            ])->default('awaiting_payment');
            $t->string('courier', 40)->nullable();
            $t->string('tracking_number', 60)->nullable();
            $t->dateTime('settlement_deadline')->nullable();
            $t->unsignedTinyInteger('settlement_reminder_stage')->default(0);
            $t->dateTime('shipping_deadline')->nullable();
            $t->unsignedTinyInteger('shipping_reminder_stage')->default(0);
            $t->dateTime('completed_at')->nullable();
            $t->dateTime('created_at')->useCurrent();
            $t->dateTime('updated_at')->nullable();
            $t->index(['order_status', 'created_at'], 'idx_order_status');
            $t->index(['order_status', 'settlement_deadline'], 'idx_order_settlement_due');
            $t->index(['order_status', 'shipping_deadline'], 'idx_order_shipping_due');
        });

        Schema::create('order_item', function (Blueprint $t) {
            $t->id();
            $t->foreignId('customer_order_id')->constrained('customer_order')->cascadeOnDelete()->cascadeOnUpdate();
            $t->foreignId('product_id')->constrained('product')->cascadeOnUpdate();
            $t->foreignId('po_batch_id')->nullable()->constrained('po_batch')->cascadeOnUpdate();
            $t->unsignedInteger('quantity')->default(1);
            $t->decimal('unit_price', 12, 2);
            $t->decimal('subtotal', 14, 2);
        });

        Schema::create('review', function (Blueprint $t) {
            $t->id();
            $t->foreignId('customer_order_id')->constrained('customer_order')->cascadeOnDelete()->cascadeOnUpdate();
            $t->foreignId('reviewer_id')->constrained('user')->cascadeOnDelete()->cascadeOnUpdate();
            $t->foreignId('reviewee_id')->constrained('user')->cascadeOnDelete()->cascadeOnUpdate();
            $t->enum('direction', ['buyer_to_artist', 'artist_to_buyer']);
            $t->unsignedTinyInteger('rating');
            $t->text('comment')->nullable();
            $t->dateTime('created_at')->useCurrent();
            $t->unique(['customer_order_id', 'reviewer_id'], 'uq_review_order_reviewer');
        });

        Schema::create('product_review', function (Blueprint $t) {
            $t->id();
            $t->foreignId('customer_order_id')->constrained('customer_order')->cascadeOnDelete()->cascadeOnUpdate();
            $t->foreignId('product_id')->constrained('product')->cascadeOnDelete()->cascadeOnUpdate();
            $t->foreignId('reviewer_id')->constrained('user')->cascadeOnDelete()->cascadeOnUpdate();
            $t->unsignedTinyInteger('rating');
            $t->text('comment')->nullable();
            $t->dateTime('created_at')->useCurrent();
            $t->unique(['customer_order_id', 'product_id'], 'uq_preview_order_product');
        });

        Schema::create('product_view', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained('user')->cascadeOnDelete()->cascadeOnUpdate();
            $t->foreignId('product_id')->constrained('product')->cascadeOnDelete()->cascadeOnUpdate();
            $t->dateTime('viewed_at')->useCurrent();
            $t->index(['user_id', 'viewed_at'], 'idx_view_user');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_view');
        Schema::dropIfExists('product_review');
        Schema::dropIfExists('review');
        Schema::dropIfExists('order_item');
        Schema::dropIfExists('customer_order');
    }
};
