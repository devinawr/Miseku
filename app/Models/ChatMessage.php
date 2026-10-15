<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Chat 1:1 dengan konteks opsional: pesanan / custom request / produk. */
class ChatMessage extends Model
{
    protected $table = 'chat_message';

    public $timestamps = false;

    protected $fillable = ['sender_id', 'receiver_id', 'customer_order_id', 'custom_request_id', 'product_id', 'message_body', 'sent_at'];

    protected function casts(): array
    {
        return ['sent_at' => 'datetime'];
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(CustomerOrder::class, 'customer_order_id');
    }

    public function customRequest(): BelongsTo
    {
        return $this->belongsTo(CustomRequest::class, 'custom_request_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /** Foto lampiran (file_upload, source_table = 'chat_message'). */
    public function images(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(FileUpload::class, 'source_id')->where('source_table', 'chat_message')->orderBy('sort_order');
    }
}
