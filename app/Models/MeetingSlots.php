<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MeetingSlots extends Model
{
    use HasFactory;
    protected $fillable = [
        'days',
        'slots',
        'creator_id',
    ];
    protected $casts = [
        'days' => 'json',
        'slots' => 'json',
    ];
    public function user() {
        return $this->belongsTo(User::class);
    }
}
