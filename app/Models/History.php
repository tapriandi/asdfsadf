<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class History extends Model
{
    use HasFactory;

    protected $fillable = [
        'symbol',
        'exchange',
        'timeframe',
        'volume',
        'price',
        'price_high',
        'time_price_high',
        'price_close',
        'time_price_close',
        'price_hit_20',
        'price_hit_25',
        'time_hit_20',
        'time_hit_25',
        'time_sell_1',
        'time_sell_2',
        'time_hit_target_10',
        'time_hit_target_20',
        'time_hit_target_30',
        'time_hit_target_40',
        'time_hit_target_50',
    ];
}
