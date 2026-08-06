<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BasicListeningManualScore extends Model
{
    protected $fillable = ['user_id','user_year','meeting','score'];
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}