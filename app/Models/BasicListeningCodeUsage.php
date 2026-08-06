<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BasicListeningCodeUsage extends Model
{
    protected $fillable = ['connect_code_id','user_id','used_at','ip','ua'];
    protected $casts = ['used_at'=>'datetime'];

    public function code(): BelongsTo { return $this->belongsTo(BasicListeningConnectCode::class,'connect_code_id'); }
}
