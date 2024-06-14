<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Peak24Hours extends Model
{
    use HasFactory;
    protected $fillable = [
        'symbol',
        'price',
        'hit_20',
        'hit_time_20',
        'status_25',
        'hit_25',
        'hit_time_25',
        'target_20',
        'target_time_20',
        'target_50',
        'target_time_50',
    ];
}
