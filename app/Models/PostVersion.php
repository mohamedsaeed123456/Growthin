<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PostVersion extends Model
{
    use HasFactory;
    protected $fillable = ['post_id','oldVersion_id', 'submission_date_time', 'selected'];

    public function post() {
        return $this->belongsTo(Post::class);
    }
}
