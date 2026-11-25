<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class ArtistApplication extends Model
{
    protected $table = 'artist_application';
    protected $fillable = ['user_id','shop_name','shop_description','bank_name','bank_account_number','social_url','shop_address','shop_city_id','shop_province','shop_postal_code','status','reviewed_by','reviewed_at','review_note'];
    protected function casts(): array { return ['reviewed_at'=>'datetime']; }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
