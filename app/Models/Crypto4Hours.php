<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Crypto4Hours extends Model
{
    use HasFactory;
    protected $fillable = [
        'symbol',
        'price',
        'volume',
    ];
}
