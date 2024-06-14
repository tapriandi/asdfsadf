<?php

namespace App\Filament\Widgets;

use Filament\Support\Enums\IconPosition;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PeakBtcWidget extends BaseWidget
{
    // protected function getBtcPrice(): array
    // {
    //     $prices = [];
    //     $btc4hours = Bitcoin4Hours::all();
    //     foreach ($btc4hours as $btc4hour) {
    //         $price = $btc4hour['price'];
    //         $prices[] = $price;
    //     }

    //     return $prices;
    // }

    // protected function getStats(): array
    // {
    //     $btc4 = Bitcoin4Hours::latest()->first();
    //     $btc24 = Bitcoin24Hours::latest()->first();
    //     $btcCurrent = BitcoinMinutes::latest()->first();
    //     return [
    //         Stat::make('BTC Now', '$' . $btcCurrent->price)
    //             ->descriptionIcon('iconoir-bitcoin-circle', IconPosition::Before)
    //             ->description('Current price of Bitcoin')
    //             ->color('primary'),
    //         Stat::make('Current Peak', '$' . $btc4->price)
    //             ->color('success')
    //             ->descriptionIcon('iconoir-bitcoin-circle', IconPosition::Before)
    //             ->description('4 hours peak'),
    //         // Stat::make('Current Peak', '$' . $btc24->price)
    //         //     ->color('success')
    //         //     ->descriptionIcon('iconoir-bitcoin-circle', IconPosition::Before)
    //         //     ->description('24 hours peak'),

    //     ];
    // }
}
