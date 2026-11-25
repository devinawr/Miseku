<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Rekening yang disampaikan pembeli untuk satu refund manual. */
class RefundBankAccount extends Model
{
    protected $table = 'refund_bank_account';

    protected $fillable = ['payment_id', 'bank_name', 'account_name', 'account_number'];

    protected $hidden = ['account_name', 'account_number'];

    protected function casts(): array
    {
        return ['account_name' => 'encrypted', 'account_number' => 'encrypted'];
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function getMaskedAccountAttribute(): string
    {
        return '••••' . substr((string) $this->account_number, -4);
    }
}
