<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class EventProposal extends Model
{
    protected $table = 'event_proposal';
    protected $fillable = ['artist_profile_id','event_name','location','start_date','end_date','status','reviewed_by','reviewed_at','review_note','event_id'];
    protected function casts(): array { return ['start_date'=>'date','end_date'=>'date','reviewed_at'=>'datetime']; }
    public function artistProfile(): BelongsTo { return $this->belongsTo(ArtistProfile::class); }
}
