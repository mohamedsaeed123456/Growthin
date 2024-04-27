<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Post extends Model
{
    use HasFactory ,SoftDeletes;
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
        'account_manager_status',
        'user_id',
        'campaign_id',
        'isApproved',
    ];
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }
    public function comments()
    {
        return $this->hasMany(Comment::class);
    }
    protected $casts = [
        'content_type' => 'array',
        'channel' => 'array',
    ];
    public function postClientStatus()
    {
        return $this->hasMany(PostClientStatus::class, 'post_id');
    }
    public function postVersions() {
        return $this->hasMany(PostVersion::class);
    }
}
