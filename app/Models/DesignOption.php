<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class DesignOption extends Model
{
    protected $table = 'design_option';

    public $timestamps = false;

    protected $fillable = ['design_poll_id', 'design_image', 'description'];

    public function poll(): BelongsTo
    {
        return $this->belongsTo(DesignPoll::class, 'design_poll_id');
    }

    public function votes(): HasMany
    {
        return $this->hasMany(DesignVote::class, 'design_option_id');
    }

    /** Produk PO yang lahir dari desain ini (product.design_option_id). */
    public function product(): HasOne
    {
        return $this->hasOne(Product::class, 'design_option_id')->where('product_status', '!=', 'deleted');
    }
}
