<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable,SoftDeletes;
    protected $table = 'users';
    protected $fillable = [
        'email',
        'email_verified',
        'password',
        'password_confirmation',
        'role',
        'otp_resend_count',
        'is_suspended',
        'is_deleted',
    ];
    protected $hidden = [
        'password',
        'remember_token',
    ];
    protected $casts = [
        'created_at' => 'datetime:d-m-Y',
        'updated_at' => 'datetime:d-m-Y',
        'email_verified_at' => 'datetime',
        'deleted_at' => 'datetime:d-m-Y',
    ];
    public function account()
    {
        return $this->hasOne(Account::class, 'user_id', 'id');
    }
    public function payments()
    {
        return $this->hasMany(Payment::class);
    }
    public function questions() {
        return $this->hasOne(Question::class);
    }
    public function posts()
    {
        return $this->hasMany(Post::class);
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }
    public function postClientStatus()
    {
        return $this->hasMany(PostClientStatus::class, 'client_id');
    }

}
