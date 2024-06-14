<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CryptoMinutes extends Model
{
    use HasFactory;
    protected $fillable = [
        'symbol',
        'price',
        'percent_change',
    ];
}
