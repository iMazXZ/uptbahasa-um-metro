<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BasicListeningSupervisor extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'position', 'email', 'phone', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function responses(): HasMany
    {
        return $this->hasMany(BasicListeningSurveyResponse::class, 'supervisor_id');
    }
}
