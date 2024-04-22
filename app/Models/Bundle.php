<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bundle extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'description',
        'price',
        'currency',
    ];
    public function payments()
    {
        return $this->hasMany(Payment::class);
    }
}
