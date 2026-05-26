<?php

namespace SaamMi\AnyChat\Models;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;


class Attachment extends Model
{
     protected $fillable = ['attachable_id', 'attachable_type', 'file_path', 'file_name', 'mime_type', 'url', 'original_name'];

    
    
     public function attachable(): MorphTo
    {
        return $this->morphTo();
    }

}