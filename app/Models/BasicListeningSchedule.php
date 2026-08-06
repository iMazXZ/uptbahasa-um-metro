<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class BasicListeningSchedule extends Model
{
    protected $fillable = [
        'prody_id', 'hari', 'jam_mulai', 'jam_selesai',
    ];

    public function prody(): BelongsTo
    {
        return $this->belongsTo(Prody::class);
    }

    public function tutors(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'basic_listening_schedule_tutor', 'schedule_id', 'user_id');
    }

    /** Jadwal yang sedang aktif sekarang (Jakarta) */
    public function scopeActiveNow($query)
    {
        $now   = Carbon::now('Asia/Jakarta');
        $map   = [1=>'Senin',2=>'Selasa',3=>'Rabu',4=>'Kamis',5=>'Jumat',6=>'Sabtu',7=>'Minggu'];
        $today = $map[$now->dayOfWeekIso];
        $time  = $now->format('H:i:s');

        return $query->where('hari', $today)
            ->where('jam_mulai', '<=', $time)
            ->where('jam_selesai', '>=', $time);
    }
}
