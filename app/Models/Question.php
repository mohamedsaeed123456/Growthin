<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    use HasFactory;
    protected $table = 'questions';
    protected $fillable = [
        'location',
        'period',
        'description',
        'url',
        'social_media_link',
        'positives_question',
        'goals_question',
        'competitors_question',
        'advertising_question',
        'profile_image',
        'user_id',
    ];
    protected $casts = [
        'created_at' => 'datetime:d-m-Y',
        'updated_at' => 'datetime:d-m-Y',
    ];
    public function users() {
        return $this->hasMany(User::class);
    }

}
