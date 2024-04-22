<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class Account extends Model
{
    use HasFactory,Notifiable;
    protected $table = 'accounts';
    protected $fillable = [
        'company_name',
        'company_sector',
        'user_name',
        'phone',
        'gender',
        'date',
        'user_id',
        'country_code',
        'operation_id',
        'manager_id',
    ];
    protected $casts = [
        'created_at' => 'datetime:d-m-Y',
        'updated_at' => 'datetime:d-m-Y',
        'date' => 'datetime:d-m-Y',
        'country_code' => 'integer',
    ];
    public function country()
    {
        return $this->belongsTo(Country::class, 'country_code','code');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
