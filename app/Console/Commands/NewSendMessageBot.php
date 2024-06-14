<?php

namespace App\Console\Commands;


use App\Models\History;
use App\Models\Coin;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Telegram\Bot\Laravel\Facades\Telegram;

use Storage;


class NewSendMessageBot extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:new-send-message-bot';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */

    private function getIncrease($oldPrice, $newPrice)
    {
        if ($oldPrice == 0) {
            return 0;
        }
        $percentageIncrease = (($newPrice - $oldPrice) / $oldPrice) * 100;
        return number_format($percentageIncrease, 2);
    }

    private function setPrice($price)
    {
        return '$' . rtrim(rtrim($price, '0'), '.');
    }
    
    private function formatNumber($number)
    {
        $abbreviations = ["", "K", "M", "B", "T"];
        $index = 0;

        while ($number >= 1000 && $index < count($abbreviations) - 1) {
            $number /= 1000;
            $index++;
        }

        return round($number, 2) . " " . $abbreviations[$index];
    }

    private function getMinutesCoin()
    {
        $url = 'https://api.gateio.ws/api/v4/spot/tickers';
        $response = Http::get($url);
        $data = $response->json();
        
        $result = [];
        
        if (!empty($data)) {
            foreach ($data as $item) {
                if (strpos($item['currency_pair'], '_USDT') !== false) {
                    $newSymbol = strtoupper(str_replace('_USDT', '', $item['currency_pair']));
                    
                    if ($item['last'] !== 0) {
                        $result[] = [
                            'symbol' => $newSymbol,
                            'price' => $item['last'],
                            'volume' => $item['quote_volume'],
                        ];
                    } else {
                        // file_put_contents('/home3/demoweb1/mypreorder.online/laravel/aaDatalast.txt', print_r($symbol, true));
                    }
                }
            }
            $this->info('Get last minutes coin successfully.');
        } else {
            $this->error('Get last minutes coin Failed.');
        }
        
        // file_put_contents('/home3/demoweb1/mypreorder.online/laravel/aaDatalast.txt', print_r($result, true));

        return $result;
    }
    
    private function setMessage($cryptoIncrease, $flag)
    {
        // Initialize the message variable
        $messages = '';

        // title
        if ($flag == 'buy') {
            $title = "🔥🔥🔥 <b>BUY!</b> 🔥🔥🔥";
        } else if ($flag == 'alert') {
            $title = "⏰ <b>ALERT! 🚀</b>";
        } else if ($flag == 'sell') {
            $title = "🚨🚨🚨 <b>SELL!</b> 🚨🚨🚨️";
        } else {
            $title = '';
        }

        $messages .= "{$title}\n\n";

        foreach ($cryptoIncrease as $item) {
            // Convert date strings to Carbon instances
            $createdAt = Carbon::parse($item['created_at']);
            $hitTime = Carbon::parse($item['hit_time']);
            $price = $item['price'];
            
            // variable
            $date = $createdAt->format('Y/m/d');
            $open = $item['peak'] === '24' ? '07:00' : $createdAt->format('H:i');
            $openBuy = $createdAt->format('H:i');
            $hit = $hitTime->format('H:i');
            
            //
            if ($flag == 'sell') {
                $timePriceHigh = Carbon::parse($item['time_price_high']);
                $priceHigh = $timePriceHigh->format('H:i');
            }

            // icon
            if ($flag == 'buy') {
                $icon = "✅";
            } else if ($flag == 'alert') {
                $icon = "✨";
            } else if ($flag == 'sell') {
                $icon = $item['hit'] > $item['price'] ? "💰" : "🔻";
            } else {
                $icon = '';
            }

            $titleHour = ($item['peak'] === '4') ? ' 4 Hours ' : ' 24 Hours ';
            $marketCap = (($item['exchange'] === 'IO') ? "| {$this->formatNumber($item['volume'])}" : "");

            // Format the message with the required information
            $formattedMessage = "{$icon} <b>{$item['symbol']}</b> {$date} | <b>{$titleHour} | {$item['exchange']} {$marketCap}</b>\n";

            if ($flag !== 'sell') {
                $formattedMessage .= "{$open} - Open - 0.0% - {$this->setPrice($item['price'])}\n";
                $formattedMessage .= "{$hit} - Change - {$this->getIncrease($item['price'],$item['hit'])}% - {$this->setPrice($item['hit'])}\n";
            }

            if ($flag == 'sell') {
                $formattedMessage .= "{$openBuy} - Buy - {$this->setPrice($item['price'])}\n";
                $formattedMessage .= "{$priceHigh} - Highest - {$this->setPrice($item['price_high'])}\n";
                $formattedMessage .= "{$hit} - Sell - {$this->setPrice($item['hit'])}\n";
            }
           
            
            $base = $price * 1.25;
            
            
            if ($flag == 'buy') {
                $t10 = $price < 0.0001 ? sprintf('%.10f', ($base * 1.1)) : $base * 1.1;
                $t20 = $price < 0.0001 ? sprintf('%.10f', ($base * 1.2)) : $base * 1.2;
                $t30 = $price < 0.0001 ? sprintf('%.10f', ($base * 1.3)) : $base * 1.3;
                $t40 = $price < 0.0001 ? sprintf('%.10f', ($base * 1.4)) : $base * 1.4;
                $t50 = $price < 0.0001 ? sprintf('%.10f', ($base * 1.5)) : $base * 1.5;
                
                $formattedMessage .= "Target - 10% - {$this->setPrice( $t10 )} \n";
                $formattedMessage .= "Target - 20% - {$this->setPrice( $t20 )}\n";
                $formattedMessage .= "Target - 30% - {$this->setPrice( $t30 )}\n";
                $formattedMessage .= "Target - 40% - {$this->setPrice( $t40 )}\n";
                $formattedMessage .= "Target - 50% - {$this->setPrice( $t50 )}\n";
            }
            
            $formattedMessage .= "\n";
            
            // Append the formatted message to the final message
            $messages .= $formattedMessage;
        }
        
        return $messages;
    }
    
    private function CheckCoins()
    {
        $hitTime = Carbon::now();
        $currentCoin = $this->getMinutesCoin();
        
        // file_put_contents('/home3/demoweb1/mypreorder.online/laravel/aaDatalast.txt', print_r($currentCoin, true));
        
        $result = [];
        
        
        // update table history
        // $coinRunning = History::whereNull('price_close')->get();
        
        // if($coinRunning) {
        //     foreach($coinRunning as $coin) {
        //         $existCoin = Coin::where('symbol', $coin['symbol'])->first();
        //     }
        // }
        
        foreach($currentCoin as $coin) {
            $existCoin = Coin::where('symbol', $coin['symbol'])->first();
            
            if($existCoin) {
                $symbol = $coin['symbol'];
                $price4 = $existCoin->price4;
                $price24 = $existCoin->price24;
                $currentPrice = $coin['price'];
                
                
                if  ($price4 > 0 || $price24 > 0) {
                    $history = History::whereNull('price_close')->where('symbol', $symbol)->get();
                    
                    foreach($history as $coinRunning) {
                        if (isset($coinRunning)) {
                            $price = $coinRunning['price'];
                            $priceHigh = $coinRunning['price_high'];
                            $priceHit25 = $coinRunning['price_hit_25'];
                            $timeHit25 = $coinRunning['time_hit_25'];
                            $timeHitTarget10 = $coinRunning['time_hit_target_10'];
                            
                            // update price high
                            if($currentPrice > $priceHigh) {
                                $coinRunning->update(['price_high' => $currentPrice]);
                                $coinRunning->update(['time_price_high' => $hitTime]);
                            }
                            
                            // update target
                            $target10 = ($price * 1.25) * 1.1;
                            if (!isset($timeHitTarget10) && $currentPrice >= $target10) {
                                $coinRunning->update(['time_hit_target_10' => $hitTime]);
                            }
                            $target20 = ($price * 1.25) * 1.2;
                            if (!isset($coinRunning['time_hit_target_20']) && $currentPrice >= $target20) {
                                $coinRunning->update(['time_hit_target_20' => $hitTime]);
                            }
                            $target30 = ($price * 1.25) * 1.3;
                            if (!isset($coinRunning['time_hit_target_30']) && $currentPrice >= $target30) {
                                $coinRunning->update(['time_hit_target_30' => $hitTime]);
                            }
                            $target40 = ($price * 1.25) * 1.4;
                            if (!isset($coinRunning['time_hit_target_40']) && $currentPrice >= $target40) {
                                $coinRunning->update(['time_hit_target_40' => $hitTime]);
                            }
                            $target50 = ($price * 1.25) * 1.5;
                            if (!isset($coinRunning['time_hit_target_50']) && $currentPrice >= $target50) {
                                $coinRunning->update(['time_hit_target_50' => $hitTime]);
                            }
                        }
                    }
                    
                    // $history2 = History::whereNull('time_price_close')->where('symbol', $symbol)->first();
                    // if ($history2) {
                    //     // Mengecek apakah tanggal pada catatan bukan hari ini
                    //     $today = Carbon::today();
                    //     if ($history2->created_at->lt($today)) {
                    //         // Mengupdate kolom price_close menjadi 0
                    //         // $history->update(['price_close' => 11111]);
                    //     }
                    // }
                    
                    // if (isset($history)) {
                    //     $price = $history['price'];
                    //     $priceHigh = $history['price_high'];
                    //     $priceHit25 = $history['price_hit_25'];
                    //     $timeHit25 = $history['time_hit_25'];
                    //     $timeHitTarget10 = $history['time_hit_target_10'];
                        
                    //     // update price high
                    //     if($currentPrice > $priceHigh) {
                    //         $history->update(['price_high' => $currentPrice]);
                    //         $history->update(['time_price_high' => $hitTime]);
                    //     }
                        
                    //     // update target
                    //     $target10 = ($price * 1.25) * 1.1;
                    //     if (!isset($timeHitTarget10) && $currentPrice >= $target10) {
                    //         $history->update(['time_hit_target_10' => $hitTime]);
                    //     }
                    //     $target20 = ($price * 1.25) * 1.2;
                    //     if (!isset($history['time_hit_target_20']) && $currentPrice >= $target20) {
                    //         $history->update(['time_hit_target_20' => $hitTime]);
                    //     }
                    //     $target30 = ($price * 1.25) * 1.3;
                    //     if (!isset($history['time_hit_target_30']) && $currentPrice >= $target30) {
                    //         $history->update(['time_hit_target_30' => $hitTime]);
                    //     }
                    //     $target40 = ($price * 1.25) * 1.4;
                    //     if (!isset($history['time_hit_target_40']) && $currentPrice >= $target40) {
                    //         $history->update(['time_hit_target_40' => $hitTime]);
                    //     }
                    //     $target50 = ($price * 1.25) * 1.5;
                    //     if (!isset($history['time_hit_target_50']) && $currentPrice >= $target50) {
                    //         $history->update(['time_hit_target_50' => $hitTime]);
                    //     }
                    // }
                }
            }
        }
        
        // 4 hour
        foreach($currentCoin as $coin) {
            $existCoin = Coin::where('symbol', $coin['symbol'])->first();
            
            if($existCoin) {
                $existDate = $existCoin->updated_at;
                $updatedAt = $existCoin->updated_at;
                $symbol = $coin['symbol'];
                $volume = $coin['volume'];
                $exchange = $existCoin->exchange;
                $price4 = $existCoin->price4;
                $price24 = $existCoin->price24;
                $currentPrice = $coin['price'];
                
                // 4 Hour 
                if  ($price4 > 0) {
                    $history4 = History::whereNull('price_close')->where('symbol', $symbol)->where('timeframe', '4')->first();
                    
                    // alert 4 hour
                    if (!isset($history4)) {
                        if ($currentPrice >= ($price4 * 1.2) && $currentPrice < ($price4 * 1.25)) {
                            $result['alert4'][] = [
                                'symbol' => $symbol,
                                'created_at' => $updatedAt,
                                'price' => $price4,
                                'hit' => $currentPrice,
                                'hit_time' => $hitTime,
                                'peak' => '4',
                                'exchange' => $exchange,
                                'volume' => $volume
                            ];
                            
                            History::create([
                                'symbol' => $symbol,
                                'exchange' => $exchange,
                                'timeframe' => '4',
                                'volume' => $volume,
                                'price' => $price4,
                                'price_high' => $currentPrice,
                                'price_hit_20' => $currentPrice,
                                'time_hit_20' => $hitTime,
                            ]);
                        }
                    }
                    
                    if (isset($history4)) {
                        $h4_price = $history4['price'];
                        $h4_priceHigh = $history4['price_high'];
                        $h4_priceHit25 = $history4['price_hit_25'];
                        $h4_timeHit25 = $history4['time_hit_25'];
                        $h4_timeHitTarget10 = $history4['time_hit_target_10'];
                        $timePriceHigh = $history4['time_price_high'];
                        $priceHigh = $h4_priceHigh;
                        
                        // cek price high
                        if($currentPrice > $h4_priceHigh) {
                            $priceHigh = $currentPrice;
                            $timePriceHigh = $hitTime;
                        }
                        
                        // buy 4 hour
                        if (!isset($h4_priceHit25)) {
                            if ($currentPrice >= ($price4 * 1.25) ) {
                                $result['buy4'][] = [
                                    'symbol' => $symbol,
                                    'created_at' => $updatedAt,
                                    'price' => $price4,
                                    'hit' => $currentPrice,
                                    'hit_time' => $hitTime,
                                    'peak' => '4',
                                    'exchange' => $exchange,
                                    'volume' => $volume,
                                ];
                                
                                $history4->update([
                                    'price_high' => $currentPrice,
                                    'time_price_high' => $hitTime,
                                    'price_hit_25' => $currentPrice,
                                    'time_hit_25' => $hitTime,
                                ]);
                            }
                        }
                        
                        // Sell 4 hour
                        if ($h4_priceHit25 > 0) {
                            // sell type 1
                            if(!isset($h4_timeHitTarget10)) {
                                $hit25 = $h4_price * 1.25;
                                $minus20 = $hit25 * 0.8;
                                
                                if ($currentPrice < $minus20) {
                                    $result['sell'][] = [
                                        'symbol' => $symbol,
                                        'created_at' => $h4_timeHit25,
                                        'price' => $h4_priceHit25,
                                        'hit' => $currentPrice,
                                        'hit_time' => $hitTime,
                                        'peak' => '4',
                                        'exchange' => $exchange,
                                        'volume' => $volume,
                                        'price_high' => $priceHigh,
                                        'time_price_high' => $timePriceHigh,
                                    ];
                
                                    $history4->update([
                                        'price_close' => $currentPrice,
                                        'time_price_close' => $hitTime,
                                    ]);
                                }
                            }
                            
                            // sell type 2
                            if(isset($h4_timeHitTarget10)) {
                                $minus10 = $priceHigh * 0.9;
                
                                if ($currentPrice <= $minus10) {
                                    $result['sell'][] = [
                                        'symbol' => $symbol,
                                        'created_at' => $h4_timeHit25,
                                        'price' => $h4_priceHit25,
                                        'hit' => $currentPrice,
                                        'hit_time' => $hitTime,
                                        'peak' => '4',
                                        'exchange' => $exchange,
                                        'volume' => $volume,
                                        'price_high' => $priceHigh,
                                        'time_price_high' => $timePriceHigh,
                                    ];
                                    
                                    $history4->update([
                                        'price_close' => $currentPrice,
                                        'time_price_close' => $hitTime,
                                    ]);
                                }
                            }
                        }
                    }
                }
            }
            
            // file_put_contents('/home3/demoweb1/mypreorder.online/laravel/aaDatalast1.txt', print_r($result, true));
        }
        
        // 24 hour
        foreach($currentCoin as $coin) {
            $existCoin = Coin::where('symbol', $coin['symbol'])->first();
            
            if($existCoin) {
                $existDate = $existCoin->updated_at;
                $updatedAt = $existCoin->updated_at;
                $symbol = $coin['symbol'];
                $volume = $coin['volume'];
                $exchange = $existCoin->exchange;
                $price4 = $existCoin->price4;
                $price24 = $existCoin->price24;
                $currentPrice = $coin['price'];
                
                // 24 Hour 
                if  ($existCoin->price24 > 0) {
                    $history24 = History::where('symbol', $symbol)->whereNull('price_close')->where('timeframe', '24')->first();
                    
                    // alert 24 hour
                    if (!isset($history24)) {
                        if ($currentPrice >= ($price24 * 1.2) && $currentPrice < ($price24 * 1.25)) {
                            $result['alert24'][] = [
                                'symbol' => $symbol,
                                'created_at' => $updatedAt,
                                'price' => $price24,
                                'hit' => $currentPrice,
                                'hit_time' => $hitTime,
                                'peak' => '24',
                                'exchange' => $exchange,
                                'volume' => $volume
                            ];
                            
                            History::create([
                                'symbol' => $symbol,
                                'exchange' => $exchange,
                                'timeframe' => '24',
                                'volume' => $volume,
                                'price' => $price24,
                                'price_high' => $currentPrice,
                                'price_hit_20' => $currentPrice,
                                'time_hit_20' => $hitTime,
                            ]);
                        }
                    } 
                    
                    if (isset($history24)) {
                        $h24_price = $history24['price'];
                        $h24_priceHigh = $history24['price_high'];
                        $h24_priceHit25 = $history24['price_hit_25'];
                        $h24_timeHit25 = $history24['time_hit_25'];
                        $h24_timeHitTarget10 = $history24['time_hit_target_10'];
                        $timePriceHigh = $history24['time_price_high'];
                        $priceHigh = $h24_priceHigh;
                        
                        // cek price high
                        if($currentPrice > $h24_priceHigh) {
                            $priceHigh = $currentPrice;
                            $timePriceHigh = $hitTime;
                        }
                        
                        // buy 24 hour
                        if (!isset($h24_priceHit25)) {
                            if ($currentPrice >= ($price24 * 1.25) ) {
                                $result['buy24'][] = [
                                    'symbol' => $symbol,
                                    'created_at' => $updatedAt,
                                    'price' => $price24,
                                    'hit' => $currentPrice,
                                    'hit_time' => $hitTime,
                                    'peak' => '24',
                                    'exchange' => $exchange,
                                    'volume' => $volume,
                                ];
                                
                                $history24->update([
                                    'price_high' => $currentPrice,
                                    'time_price_high' => $hitTime,
                                    'price_hit_25' => $currentPrice,
                                    'time_hit_25' => $hitTime,
                                ]);
                            }
                        }
                        
                        // Sell 24 hour
                        if ($h24_priceHit25 > 0) {
                            // sell type 1
                            if(!isset($h24_timeHitTarget10)) {
                                $hit25 = $h24_price * 1.25;
                                $minus20 = $hit25 * 0.8;
                                
                                if ($currentPrice < $minus20) {
                                    $result['sell'][] = [
                                        'symbol' => $symbol,
                                        'created_at' => $h24_timeHit25,
                                        'price' => $h24_priceHit25,
                                        'hit' => $currentPrice,
                                        'hit_time' => $hitTime,
                                        'peak' => '24',
                                        'exchange' => $exchange,
                                        'volume' => $volume,
                                        'price_high' => $priceHigh,
                                        'time_price_high' => $timePriceHigh,
                                    ];
                
                                    $history24->update([
                                        'price_close' => $currentPrice,
                                        'time_price_close' => $hitTime,
                                    ]);
                                }
                            }
                            
                            // sell type 2
                            if(isset($h24_timeHitTarget10)) {
                                $minus10 = $priceHigh * 0.9;
                
                                if ($currentPrice <= $minus10) {
                                    $result['sell'][] = [
                                        'symbol' => $symbol,
                                        'created_at' => $h24_timeHit25,
                                        'price' => $h24_priceHit25,
                                        'hit' => $currentPrice,
                                        'hit_time' => $hitTime,
                                        'peak' => '24',
                                        'exchange' => $exchange,
                                        'volume' => $volume,
                                        'price_high' => $priceHigh,
                                        'time_price_high' => $timePriceHigh,
                                    ];
                                    
                                    $history24->update([
                                        'price_close' => $currentPrice,
                                        'time_price_close' => $hitTime,
                                    ]);
                                }
                            }
                        }
                    }
                }
            }
            
            // file_put_contents('/home3/demoweb1/mypreorder.online/laravel/aaDatalast1.txt', print_r($result, true));
        }
        
        
        return $result;
    }


    public function handle()
    {
        $response = $this->CheckCoins();
        
        // file_put_contents('/home3/demoweb1/mypreorder.online/laravel/aaAllMessage.txt', print_r($response, true));
        
        if (isset($response['sell'])) {
            $messagesSell = $this->setMessage($response['sell'], 'sell');
            Telegram::bot('mybot')->sendMessage([
                'chat_id' => env('TELEGRAM_CHANNEL_ID'),
                'text' => $messagesSell,
                'parse_mode' => 'html',
            ]);
        }
        
        // 4 hour
        if (isset($response['alert4'])) {
            $alert4 = $this->setMessage($response['alert4'], 'alert');
            Telegram::bot('mybot')->sendMessage([
                'chat_id' => env('TELEGRAM_CHANNEL_ID'),
                'text' => $alert4,
                'parse_mode' => 'html',
            ]);
        }
        if (isset($response['buy4'])) {
            $buy4 = $this->setMessage($response['buy4'], 'buy');
            Telegram::bot('mybot')->sendMessage([
                'chat_id' => env('TELEGRAM_CHANNEL_ID'),
                'text' => $buy4,
                'parse_mode' => 'html',
            ]);
        }
        
        // 24 hour
        if (isset($response['alert24'])) {
            $alert24 = $this->setMessage($response['alert24'], 'alert');
            Telegram::bot('mybot')->sendMessage([
                'chat_id' => env('TELEGRAM_CHANNEL_ID'),
                'text' => $alert24,
                'parse_mode' => 'html',
            ]);
        }
        if (isset($response['buy24'])) {
            $buy24 = $this->setMessage($response['buy24'], 'buy');
            Telegram::bot('mybot')->sendMessage([
                'chat_id' => env('TELEGRAM_CHANNEL_ID'),
                'text' => $buy24,
                'parse_mode' => 'html',
            ]);
        }
    }
}