<?php

namespace App\Console\Commands;

use App\Models\Coin;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Telegram\Bot\Laravel\Facades\Telegram;

class AndiCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:andi-command';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
     
    private function getCoinTC()
    {
        $url = 'https://www.tokocrypto.com/open/v1/common/symbols';
        $response = Http::get($url);
        $data = $response->json()['data']['list'];

        return $data;
    }
     
    private function getCoinData()
    {
        $url = 'https://api.gateio.ws/api/v4/spot/tickers';
        $response = Http::get($url);
        $data = $response->json();
        $coinTc = $this->getCoinTC();

        $result = [];

        if ($data) {
            foreach ($data as $item) {
                if (strpos($item['currency_pair'], '_USDT') !== false) {
                    $exchange = 'IO';
                    
                    $newSymbol = strtoupper(str_replace('_USDT', '', $item['currency_pair']));
                    $isMatch = collect($coinTc)->contains('baseAsset', $newSymbol);
                    
                    if ($isMatch) {
                        $exchange = 'TC';
                    }
                    
                    if ($item['last'] !== 0) {
                        $result[] = [
                            'exc' => $exchange,
                            'symbol' => $newSymbol,
                            'price' => $item['last'],
                        ];
                    } else {
                        // file_put_contents('/home/astj9531/public_html/mycrypto.astodigi.com/laravel/ceksampeakar.txt', print_r($symbol, true));
                    }
                }
            }
            $this->info('GET Coin successfully.');
        } else {
            $this->error('GET Coin Failed');
        }
        
        // file_put_contents('/home/astj9531/public_html/mycrypto.astodigi.com/laravel/aaDatalast.txt', print_r($result, true));
        return $result;
    }


    public function handle()
    {
        $currentCoins = $this->getCoinData();
        // $currentHour = (int)date('G');
        
        
        file_put_contents('/home3/demoweb1/mypreorder.online/laravel/aaDatalast.txt', print_r($currentCoins, true));
        
        // foreach ($currentCoins as $coin) {
            
        //     $existingCoin = Coin::where('symbol', $coin['symbol'])->first();

        //     if ($currentHour == 7) {
        //         if ($existingCoin) {
        //             $existingCoin->update([
        //                 'price4' => $coin['price'],
        //                 'price24' => $coin['price'],
        //                 'exchange' => $coin['exc']
        //             ]);
        //         } else {
        //             Coin::create([
        //                 'symbol' => $coin['symbol'],
        //                 'price4' => $coin['price'],
        //                 'price24' => $coin['price'],
        //                 'exchange' => $coin['exc'],
        //             ]);
        //         }
        //     } else {
        //         if ($existingCoin) {
        //             $existingCoin->update([
        //                 'price4' => $coin['price'],
        //                 'exchange' => $coin['exc']
        //             ]);
        //         } else {
        //             Coin::create([
        //                 'symbol' => $coin['symbol'],
        //                 'price4' => $coin['price'],
        //                 'exchange' => $coin['exc'],
        //             ]);
        //         }
        //     }
        // }

        // $this->info('Coin data updated successfully based on the time conditions.');
    }
}
