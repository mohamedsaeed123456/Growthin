<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    use HasFactory;
    protected $fillable = [
        'channel',
        'campaign',
        'content_goal',
        'content_type',
        'post_content',
        'content_image',
        'publication_date',
        'client_status',
        'operation_status',
        'manager_status',
        'user_id',
        'campaign_id'
    ];
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }
    protected $casts = [
        'content_type' => 'array',
        'channel' => 'array',
    ];
}
