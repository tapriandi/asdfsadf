<?php

namespace App\Console\Commands;

use App\Models\Crypto4Hours;
use App\Models\ExistPair;
use App\Models\Peak4Hours;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class Crypto4HoursCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:crypto4-hours-command';

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
        $url = 'https://api.gateio.ws/api/v4/spot/tickers';
        // try {
        //     $response = Http::get($url);
        //     $data = $response->json();
            
        //     if (!is_array($data)) {
        //         throw new \Exception('API response is not valid');
        //     }

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
        //     return 'get API failed ' . $e;
        // }
    }

    public function handle()
    {

        $coins = $this->getCoinGateIo();
        
        if (!empty($coins)) {
            foreach ($coins as $item) {
                Peak4Hours::create([
                    'symbol' => $item['symbol'],
                    'price' =>  $item['price'],
                    'hit_20' => 0,
                    'hit_time_20' => null,
                    'status_25' => 0,
                    'hit_25' => 0,
                    'hit_time_25' => null,
                    'target_20' => 0,
                    'target_time_20' => null,
                    'target_50' => 0,
                    'target_time_50' => null,
                ]);
            }
            $this->info('Crypto Rate 4 H saved successfully.');
        } else {
            $this->error('Failed to fetch Crypto Rate 4 H.');
        }
    }
}
