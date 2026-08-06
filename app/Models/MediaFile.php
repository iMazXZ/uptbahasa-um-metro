<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MediaFile extends Model
{
    protected $fillable = [
        'disk',
        'path',
        'filename',
        'directory',
        'mime_type',
        'size',
        'width',
        'height',
        'last_modified_at',
    ];

    protected $casts = [
        'last_modified_at' => 'datetime',
        'size' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
    ];
}
