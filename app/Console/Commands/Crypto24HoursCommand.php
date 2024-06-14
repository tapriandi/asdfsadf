<?php

namespace App\Console\Commands;

use App\Models\Crypto24Hours;
use App\Models\ExistPair;
use App\Models\Peak24Hours;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class Crypto24HoursCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:crypto24-hours-command';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    private function getCoinGateIo()
    {
        // $url = 'https://api.gateio.ws/api/v4/spot/tickers';
        // try {
        //     $response = Http::get($url);
        //     $data = $response->json();

        //     $result = [];
        //     foreach ($data as $item) {
        //         if (strpos($item['currency_pair'], '_USDT') !== false) {
        //             $newSymbol = strtoupper(str_replace('_USDT', '', $item['currency_pair']));
    
        //                 $result[] = [
        //                     'symbol' => $newSymbol,
        //                     'price' => $item['last'],
        //                     'volume' => $item['base_volume'],
        //                 ];
        //             }
        //         }
        //     }
        //     return $result;
        // } catch (\Exception $e) {
        //     return '' . $e;
        // }
    }

    public function handle()
    {
        // $historys = History::where('price_close', 0)->get();
        
        // foreach ($historys as $history) {
        //     if (!isset($history['price_hit_25'])) {
        //         $history->update([
        //             'time_price_close' => Carbon::now(),
        //             'price_close' => 1.11,
        //             'price_hit_25' => 1.11,
        //         ]);
        //     }
        // }
        
    //     $coins = $this->getCoinGateIo();
    //     if ($coins) {
    //         foreach ($coins as $item) {
    //             // Crypto24Hours::create([
    //             //     'symbol' => $item['symbol'],
    //             //     'price' => $item['price'],
    //             //     'volume' => $item['volume'],
    //             // ]);

    //             Peak24Hours::create([
    //                 'symbol' => $item['symbol'],
    //                 'price' =>  $item['price'],
    //                 'hit_20' => 0,
    //                 'hit_time_20' => null,
    //                 'status_25' => 0,
    //                 'hit_25' => 0,
    //                 'hit_time_25' => null,
    //                 'target_20' => 0,
    //                 'target_time_20' => null,
    //                 'target_50' => 0,
    //                 'target_time_50' => null,
    //             ]);
    //         }
    //         $this->info('Crypto Rate 24 H saved successfully.');
    //     } else {
    //         $this->error('Failed to fetch Crypto Rate 24 H.');
    //     }
    }
}
