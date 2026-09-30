<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** product, po_batch, file_upload (polimorfik: source_table + source_id, sengaja bukan FK). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product', function (Blueprint $t) {
            $t->id();
            $t->foreignId('artist_profile_id')->constrained('artist_profile')->cascadeOnDelete()->cascadeOnUpdate();
            $t->foreignId('fandom_character_id')->constrained('fandom_character')->cascadeOnUpdate();
            $t->foreignId('merchandise_type_id')->constrained('merchandise_type')->cascadeOnUpdate();
            $t->foreignId('design_option_id')->nullable()->constrained('design_option')->nullOnDelete()->cascadeOnUpdate();
            $t->string('product_name', 150);
            $t->text('description')->nullable();
            $t->decimal('price', 12, 2);
            $t->string('size', 60)->nullable();
            $t->unsignedInteger('stock')->default(0);
            $t->enum('availability_type', ['ready_stock', 'pre_order'])->default('ready_stock');
            $t->boolean('allow_event_pickup')->default(false);
            $t->enum('product_status', ['active', 'inactive', 'deleted'])->default('active');
            $t->decimal('rating', 3, 2)->default(0);
            $t->unsignedInteger('rating_count')->default(0);
            $t->dateTime('created_at')->useCurrent();
            $t->dateTime('updated_at')->nullable();
            $t->index(['availability_type', 'product_status', 'price'], 'idx_product_filter');
        });

        Schema::create('po_batch', function (Blueprint $t) {
            $t->id();
            $t->foreignId('product_id')->constrained('product')->cascadeOnDelete()->cascadeOnUpdate();
            $t->decimal('price_po', 12, 2);
            $t->enum('payment_scheme', ['dp', 'full'])->default('full');
            $t->decimal('dp_amount', 12, 2)->nullable();
            $t->unsignedInteger('min_order')->nullable();
            $t->unsignedInteger('max_slot')->nullable();
            $t->dateTime('opens_at');
            $t->dateTime('closes_at');
            $t->date('estimated_month')->nullable();
            $t->enum('batch_status', ['open', 'closed', 'in_production', 'completed', 'cancelled'])->default('open');
            $t->dateTime('completed_at')->nullable();
            $t->dateTime('created_at')->useCurrent();
            $t->dateTime('updated_at')->nullable();
            $t->index(['product_id', 'batch_status'], 'idx_batch_product');
        });

        Schema::create('file_upload', function (Blueprint $t) {
            $t->id();
            $t->string('url', 255);
            $t->string('source_table', 50);
            $t->unsignedBigInteger('source_id');
            $t->foreignId('uploaded_by')->constrained('user')->cascadeOnDelete()->cascadeOnUpdate();
            $t->unsignedTinyInteger('sort_order')->default(1);
            $t->dateTime('uploaded_at')->useCurrent();
            $t->index(['source_table', 'source_id'], 'idx_file_source');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('file_upload');
        Schema::dropIfExists('po_batch');
        Schema::dropIfExists('product');
    }
};
