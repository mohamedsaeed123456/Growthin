<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Meeting extends Model
{
    use HasFactory,SoftDeletes;
    protected $fillable = [
        'meeting_type',
        'meeting_title',
        'meeting_date',
        'meeting_plan',
        'meeting_image',
        'meeting_ling',
        'meeting_summary',
        'reason_meeting_cancle',
        'user_id',
        'creator_id',
    ];
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function creator()
    {
        return $this->belongsTo(User::class);
    }

}
