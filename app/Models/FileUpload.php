<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FileUpload extends Model
{
    protected $table = 'file_upload';

    public $timestamps = false;

    protected $fillable = ['url', 'source_table', 'source_id', 'uploaded_by', 'sort_order'];

    protected function casts(): array
    {
        return ['uploaded_at' => 'datetime'];
    }
}
