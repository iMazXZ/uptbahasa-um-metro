<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BasicListeningCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'position',
        'is_active',
    ];

    protected $casts = [
        'position'  => 'integer',
        'is_active' => 'boolean',
    ];

    public function surveys(): HasMany
    {
        return $this->hasMany(BasicListeningSurvey::class, 'category', 'slug');
    }
}
