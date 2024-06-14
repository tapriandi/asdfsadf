<?php

namespace App\Console\Commands;


use App\Models\Peak24Hours;
use App\Models\Peak4Hours;
use App\Models\History;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Telegram\Bot\Laravel\Facades\Telegram;

use Storage;


class SendMessageBot extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:send-message-bot';

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
        $percentageIncrease = (($newPrice - $oldPrice) / $oldPrice) * 100;
        $res =  number_format($percentageIncrease, 2);
        return $res;
    }

    private function setPrice($price)
    {
        return '$' . rtrim(rtrim($price, '0'), '.');
    }

    private function getCoinTC()
    {
        $url = 'https://www.tokocrypto.com/open/v1/common/symbols';
        $response = Http::get($url);
        $data = $response->json()['data']['list'];

        return $data;
    }

    private function getLastMinutesData()
    {
        $url = 'https://api.gate.io/api2/1/tickers';
        $response = Http::withOptions(['verify', false])->get($url);
        // $response = Http::get($url);
        $data = $response->json();
        $coinTc = $this->getCoinTC();

        $result = [];

        if ($data) {
            foreach ($data as $item) {
                if (strpos($item['currency_pair'], '_USDT') !== false) {
                    $newSymbol = strtoupper(str_replace('_USDT', '', $item['currency_pair']));

                    $isMatch = collect($coinTc)->contains('baseAsset', $newSymbol);

                    if ($isMatch) {
                        $exchange = 'TC';
                    }
                    if ($item['last'] == 0) {
                        // file_put_contents('/home/astj9531/public_html/mycrypto.astodigi.com/laravel/ceksampeakar.txt', print_r($symbol, true));
                    } else {
                        $result[] = [
                            'exc' => $exchange,
                            'symbol' => $newSymbol,
                            'price' => $item['last'],
                            'volume' => $item['base_volume'],
                        ];
                    }
                }
            }
            $this->info('Crypto Rate minutes saved successfully.');
        } else {
            $this->error('Failed to fetch Crypto Rate minutes.');
        }

        return $result;
    }

    private function getLastPeak4Data()
    {
        $latestCreatedAt = Peak4Hours::latest('created_at')->value('created_at');
        $hour = $latestCreatedAt->format('H');

        $currentDate = now()->format('Y-m-d');

        $data = Peak4Hours::whereDate('created_at', $currentDate)
            ->whereRaw("HOUR(created_at) = ?", [$hour])
            ->get();

        return $data;
    }

    private function getLastPeak24Data()
    {
        $latestCreatedAt = Peak24Hours::latest('created_at')->value('created_at');
        $hour = $latestCreatedAt->format('H');

        $currentDate = now()->format('Y-m-d');

        $data = Peak24Hours::whereDate('created_at', $currentDate)
            ->whereRaw("HOUR(created_at) = ?", [$hour])
            ->get();

        return $data;
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

    private function updateField($id, $field, $value, $type)
    {
        if ($type == '24') {
            $peaks = Peak24Hours::where('id', $id)->firstOrFail();
            $peaks->update([$field => $value]);
        }
        if ($type == '4') {
            $peaks = Peak4Hours::where('id', $id)->firstOrFail();
            $peaks->update([$field => $value]);
        }
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
            $title = "🛑 <b>SELL!</b> ❌️";
        } else {
            $title = '';
        }

        $messages .= "{$title}\n\n";

        foreach ($cryptoIncrease as $item) {
            // variable
            $date = $item['created_at']->format('Y/m/d');
            $open = $item['created_at']->format('H:i');
            $hit = $item['hit_time']->format('H:i');
                
            // icon
            if ($flag == 'buy') {
                $icon = "✅";
            } else if ($flag == 'alert') {
                $icon = "✨";
            } else if ($flag == 'sell') {
                $icon = "🔻";
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
                $formattedMessage .= "{$open} - Buy - {$this->setPrice($item['price'])}\n";
                $formattedMessage .= "{$hit} - Sell - {$this->setPrice($item['hit'])}\n";
            }

            $base = $item['price'] * 1.25;

            if ($flag == 'buy') {
                $formattedMessage .= "Target - 10% - {$this->setPrice(($base * 1.1).'')}\n";
                $formattedMessage .= "Target - 20% - {$this->setPrice(($base * 1.2).'')}\n";
                $formattedMessage .= "Target - 30% - {$this->setPrice(($base * 1.3).'')}\n";
                $formattedMessage .= "Target - 40% - {$this->setPrice(($base * 1.4).'')}\n";
                $formattedMessage .= "Target - 50% - {$this->setPrice(($base * 1.5).'')}\n";
            }

            $formattedMessage .= "\n";

            // Append the formatted message to the final message
            $messages .= $formattedMessage;
        }
        
        return $messages;
    }

    private function updatePeakdata($cryptoIncrease, $flag)
    {
        foreach ($cryptoIncrease as $item) {
            if ($flag == 'alert') {
                if ($item['peak'] == '4') {
                    // update data peak 4: alert
                    $this->updateField($item['id'], 'hit_20', $item['hit'], '4');
                    $this->updateField($item['id'], 'hit_time_20', $item['hit_time'], '4');
                }
                if ($item['peak'] == '24') {
                    // update data peak 24: alert
                    $this->updateField($item['id'], 'hit_20', $item['hit'], '24');
                    $this->updateField($item['id'], 'hit_time_20', $item['hit_time'], '24');
                }
            }

            if ($flag == 'buy') {
                if ($item['peak'] == '4') {
                    // update data peak 4: buy
                    $this->updateField($item['id'], 'hit_25', $item['hit'], '4');
                    $this->updateField($item['id'], 'hit_time_25', $item['hit_time'], '4');
                    $this->updateField($item['id'], 'status_25', 1, '4');
                }
                if ($item['peak'] == '24') {
                    // update data peak 24: buy
                    $this->updateField($item['id'], 'hit_25', $item['hit'], '24');
                    $this->updateField($item['id'], 'hit_time_25', $item['hit_time'], '24');
                    $this->updateField($item['id'], 'status_25', 1, '24');
                }
            }
        }
    }

    private function cryptoIncrease4($dataLast, $dataPeak4, $openTime4, $hitTime)
    {
        $resultMessage = [];
        $alert = 0;
        $buy = 0;

        foreach ($dataLast as $lastItem) {
            // match symbol
            $peak4Item = $dataPeak4->firstWhere('symbol', $lastItem['symbol']);
            
            // handle 4 hours
            if (isset($peak4Item)) {
                // increase 20%
                if (!$peak4Item['hit_time_20']) {
                    if ($lastItem['price'] >= ($peak4Item->price * 1.2) && $lastItem['price'] < ($peak4Item->price * 1.25)) {
                        
                        // new method
                        $history = History::where('price_close', 0)->where('symbol', $lastItem['symbol'])->where('timeframe', '4')->first();
                     
                        if (!isset($history)) {
                            $resultMessage['alert'][$alert] = [
                                'id' => $peak4Item->id,
                                'symbol' => $peak4Item->symbol,
                                'created_at' => $openTime4,
                                'price' => $peak4Item->price,
                                'hit' => $lastItem['price'],
                                'hit_time' => $hitTime,
                                'peak' => '4',
                                'exchange' => $lastItem['exc'],
                                'volume' => $lastItem['volume']
                            ];
                            $alert++;
                            
                            History::create([
                                'symbol' => $lastItem['symbol'],
                                'exchange' => $lastItem['exc'],
                                'timeframe' => '4',
                                'volume' => $lastItem['volume'],
                                'price' => $peak4Item->price,
                                'price_high' => $lastItem['price'],
                                'price_close' => 0,
                                'price_hit_20' => $lastItem['price'],
                                'time_hit_20' => $hitTime,
                            ]);
                        }
                    }
                }


                // increase 25%
                if ($peak4Item['status_25'] == 0) {
                    if ($lastItem['price'] >= ($peak4Item->price * 1.25)) {
                        
                        // new method
                        $history = History::where('price_close', 0)->where('symbol', $lastItem['symbol'])->where('timeframe', '4')->first();
                       
                        if(isset($history) && !isset($history['price_hit_25'])) {
                            $resultMessage['buy'][$buy] = [
                                'id' => $peak4Item->id,
                                'symbol' => $peak4Item->symbol,
                                'created_at' => $openTime4,
                                'price' => $peak4Item->price,
                                'hit' => $lastItem['price'],
                                'hit_time' => $hitTime,
                                'peak' => '4',
                                'exchange' => $lastItem['exc'],
                                'volume' => $lastItem['volume'],
                            ];
                            $buy++;
                        }
                        
                        if (isset($history)) {
                            $history->update([
                                'price_high' => $lastItem['price'],
                                'price_hit_25' => $lastItem['price'],
                                'time_hit_25' => $hitTime,
                            ]);
                        }
                        if (!isset($history)) {
                            History::create([
                                'symbol' => $lastItem['symbol'],
                                'exchange' => $lastItem['exc'],
                                'timeframe' => '4',
                                'volume' => $lastItem['volume'],
                                'price' => $peak4Item->price,
                                'price_high' => $lastItem['price'],
                                'price_close' => 0,
                                'price_hit_20' => $lastItem['price'],
                                'time_hit_20' => $hitTime,
                                'price_hit_25' => $lastItem['price'],
                                'time_hit_25' => $hitTime,
                            ]);
                        }
                    }
                }
            }
        }

        return $resultMessage;
    }

    private function cryptoIncrease24($dataLast, $dataPeak24, $openTime24, $hitTime)
    {
        $resultMessage = [];
        $buy = 0;
        $alert = 0;

        foreach ($dataLast as $lastItem) {
            $peak24Item = $dataPeak24->firstWhere('symbol', $lastItem['symbol']);

            // handle 24 hours
            if (isset($peak24Item)) {
                // increase 20%
                if (!$peak24Item['hit_time_20']) {
                    if ($lastItem['price'] >= ($peak24Item->price * 1.2) && $lastItem['price'] < ($peak24Item->price * 1.25)) {

                        // new method
                        $history = History::where('price_close', 0)->where('symbol', $lastItem['symbol'])->where('timeframe', '24')->first();
                    
                        if (!isset($history)) {
                            $resultMessage['alert'][$alert] = [
                                'id' => $peak24Item->id,
                                'symbol' => $peak24Item->symbol,
                                'created_at' => $openTime24,
                                'price' => $peak24Item->price,
                                'hit' => $lastItem['price'],
                                'hit_time' => $hitTime,
                                'peak' => '24',
                                'exchange' => $lastItem['exc'],
                                'volume' => $lastItem['volume']
                            ];
                            $alert++;
                            
                            History::create([
                                'symbol' => $lastItem['symbol'],
                                'exchange' => $lastItem['exc'],
                                'timeframe' => '24',
                                'volume' => $lastItem['volume'],
                                'price' => $peak24Item->price,
                                'price_high' => $lastItem['price'],
                                'price_close' => 0,
                                'price_hit_20' => $lastItem['price'],
                                'time_hit_20' => $hitTime,
                            ]);
                        }
                    }
                }

                // increase 25%
                if ($peak24Item['status_25'] == 0) {
                    if ($lastItem['price'] >= ($peak24Item->price * 1.25)) {

                        // new method
                        $history = History::where('price_close', 0)->where('symbol', $lastItem['symbol'])->where('timeframe', '24')->first();
                        
                        if(isset($history) && !isset($history['price_hit_25'])) {
                            $resultMessage['buy'][$buy] = [
                                'id' => $peak24Item->id,
                                'symbol' => $peak24Item->symbol,
                                'created_at' => $openTime24,
                                'price' => $peak24Item->price,
                                'hit' => $lastItem['price'],
                                'hit_time' => $hitTime,
                                'peak' => '24',
                                'exchange' => $lastItem['exc'],
                                'volume' => $lastItem['volume']
                            ];
                            $buy++;
                        }
                        
                        if (isset($history)) {
                            $history->update([
                                'price_high' => $lastItem['price'],
                                'price_hit_25' => $lastItem['price'],
                                'time_hit_25' => $hitTime,
                            ]);
                        }
                        if (!isset($history)) {
                             History::create([
                                'symbol' => $lastItem['symbol'],
                                'exchange' => $lastItem['exc'],
                                'timeframe' => '24',
                                'volume' => $lastItem['volume'],
                                'price' => $peak24Item->price,
                                'price_high' => $lastItem['price'],
                                'price_close' => 0,
                                'price_hit_20' => $lastItem['price'],
                                'time_hit_20' => $hitTime,
                                'price_hit_25' => $lastItem['price'],
                                'time_hit_25' => $hitTime,
                            ]);
                        }
                    }
                }
            }
        }

        return $resultMessage;
    }

    private function cryptoDecrease($dataLast, $hitTime)
    {
        $resultMessage = [];
        

        foreach ($dataLast as $lastItem) {
            $history4 = History::where('price_close', 0)->where('symbol', $lastItem['symbol'])->where('timeframe', '4')->first();
            // $history4 = History::where('symbol', $lastItem['symbol'])->where('timeframe', '4')->first();

            
            // type 1 : 4 hour // tested ok
            if (isset($history4) && $history4['price_hit_25'] > 0 && !isset($history4['time_hit_target_10'])) {
                $hit25 = $history4->price * 1.25;
                $minus20 = $hit25 * 0.8;

                if ($lastItem['price'] < $minus20) {
                    $resultMessage['sell'][] = [
                        'id' => $history4->id,
                        'symbol' => $lastItem['symbol'],
                        'created_at' => Carbon::parse($history4['time_hit_25']),
                        'price' => $history4['price_hit_25'],
                        'hit' => $lastItem['price'],
                        'hit_time' => $hitTime,
                        'peak' => '4',
                        'exchange' => $lastItem['exc'],
                        'volume' => $lastItem['volume']
                    ];

                    $history4->update([
                        'price_close' => $lastItem['price'],
                        // 'price_close' => 0,
                        'time_price_close' => $hitTime
                    ]);
                }
            }

            // type 2 : 4 hour
            if (isset($history4) && $history4['price_hit_25'] > 0 && isset($history4['time_hit_target_10'])) {
                $minus10 = $history4['price_high'] * 0.9;

                if ($lastItem['price'] < $minus10) {
                    $resultMessage['sell'][] = [
                        'id' => $history4->id,
                        'symbol' => $lastItem['symbol'],
                        'created_at' => Carbon::parse($history4['time_hit_25']),
                        'price' => $history4['price_hit_25'],
                        'hit' => $lastItem['price'],
                        'hit_time' => $hitTime,
                        'peak' => '4',
                        'exchange' => $lastItem['exc'],
                        'volume' => $lastItem['volume']
                    ];

                    $history4->update([
                        'price_close' => $lastItem['price'],
                        'time_price_close' => $hitTime,
                    ]);
                }
            }

            // ====================
            // 24 hour
            $history44 = History::where('price_close', 0)->where('symbol', $lastItem['symbol'])->where('timeframe', '24')->first();
            
            // type 1 : 24 hour
            if (isset($history24) && $history24['price_hit_25'] > 0 && !isset($history24['time_hit_target_10'])) {
                $hit25 = $history24->price * 1.25;
                $minus20 = $hit25 * 0.8;

                if ($lastItem['price'] < $minus20) {
                    $resultMessage['sell'][] = [
                        'id' => $history24->id,
                        'symbol' => $lastItem['symbol'],
                        'created_at' => Carbon::parse($history24['time_hit_25']),
                        'price' => $history24['price_hit_25'],
                        'hit' => $lastItem['price'],
                        'hit_time' => $hitTime,
                        'peak' => '24',
                        'exchange' => $lastItem['exc'],
                        'volume' => $lastItem['volume']
                    ];

                    $history24->update([
                        'price_close' => $lastItem['price'],
                        'time_price_close' => $hitTime,
                    ]);
                }
            }

            // type 2 : 24 hour
            if (isset($history24) && $history24['price_hit_25'] > 0 && isset($history24['time_hit_target_10'])) {
                $minus10 = $history24['price_high'] * 0.9;

                if ($lastItem['price'] < $minus10) {
                    $resultMessage['sell'][] = [
                        'id' => $history24->id,
                        'symbol' => $lastItem['symbol'],
                        'created_at' => Carbon::parse($history24['time_hit_25']),
                        'price' => $history24['price_hit_25'],
                        'hit' => $lastItem['price'],
                        'hit_time' => $hitTime,
                        'peak' => '24',
                        'exchange' => $lastItem['exc'],
                        'volume' => $lastItem['volume']
                    ];

                    $history24->update([
                        'price_close' => $lastItem['price'],
                        'time_price_close' => $hitTime,
                    ]);
                }
            }
            
        }
        
        return $resultMessage;
    }

    // ========== combine ==========
    private function cryptoIncrease()
    {
        $dataLast = $this->getLastMinutesData();
        $dataPeak4 = $this->getLastPeak4Data();
        $dataPeak24 = $this->getLastPeak24Data();

        $result = [];

        $openTime4 = Peak4Hours::latest('created_at')->value('created_at');
        $openTime24 = Peak24Hours::latest('created_at')->value('created_at');
        $hitTime = Carbon::now();
        
       
        // update price_high and target 10%
        foreach ($dataLast as $lastItem) {
            $peak4 = History::where('price_close', 0)->where('symbol', $lastItem['symbol'])->where('timeframe', '4')->first();
            if (isset($peak4) ) {
                // price hight
                if($lastItem['price'] > $peak4['price_high']) {
                    $peak4->update(['price_high' => $lastItem['price']]);
                }
                
                // target
                $target10h4 = ($peak4['price'] * 1.25) * 1.1 ;
                if (!isset($peak4['time_hit_target_10']) && $lastItem['price'] >= $target10h4) {
                    $peak4->update(['time_hit_target_10' => $hitTime]);
                }
            }
            
            $peak24 = History::where('price_close', 0)->where('symbol', $lastItem['symbol'])->where('timeframe', '24')->first();
            if (isset($peak24)) {
                // price hight
                if($lastItem['price'] > $peak24['price_high']) {
                    $peak24->update(['price_high' => $lastItem['price']]);
                }
                
                // target
                $target10h24 = ($peak24['price'] * 1.25) * 1.1 ;
                if (!isset($peak24['time_hit_target_10']) && $lastItem['price'] >= $target10h24) {
                    $peak24->update(['time_hit_target_10' => $hitTime]);
                }
            }
        }

        // 4 hour: checking increase and make message
        if (isset($dataLast) && isset($dataPeak4)) {
            $res = $this->cryptoIncrease4($dataLast, $dataPeak4, $openTime4, $hitTime);
            if (isset($res['alert'])) {
                $this->updatePeakdata($res['alert'], 'alert');
                $result['alert'][] = $res['alert'];
            }
            if (isset($res['buy'])) {
                $this->updatePeakdata($res['buy'], 'buy');
                $result['buy'][] =  $res['buy'];
            }
        }

        // 24 hour: checking increase and make message
        if (isset($dataLast) && isset($dataPeak24)) {
            $res = $this->cryptoIncrease24($dataLast, $dataPeak24, $openTime24, $hitTime);
            if (isset($res['alert'])) {
                $this->updatePeakdata($res['alert'], 'alert');
                $result['alert'][] = $res['alert'];
            }
            if (isset($res['buy'])) {
                $this->updatePeakdata($res['buy'], 'buy');
                $result['buy'][] =  $res['buy'];
            }
        }

        
        // Sell: checking decrease and make message
        if (isset($dataLast)) {
            $res = $this->cryptoDecrease($dataLast, $hitTime);

            if (isset($res['sell'])) {
                $result['sell'][] = $res['sell'];
            }
        }
        
        return $result;
    }

    public function handle()
    {
      
        try {
            $response = $this->cryptoIncrease();
            
            file_put_contents('/home/astj9531/public_html/mycrypto.astodigi.com/laravel/aaDatalast1.txt', print_r($response, true));

            if (isset($response['sell'])) {
                $messagesSell = $this->setMessage($response['sell'][0], 'sell');
                
                Telegram::bot('mybot')->sendMessage([
                    'chat_id' => env('TELEGRAM_TEST_BOT'),
                    'text' => $messagesSell,
                    'parse_mode' => 'html',
                ]);
            }
            
            if (isset($response['alert'])) {
                $messagesAlert = $this->setMessage($response['alert'][0], 'alert');
                
                Telegram::bot('mybot')->sendMessage([
                    'chat_id' => env('TELEGRAM_TEST_BOT'),
                    'text' => $messagesAlert,
                    'parse_mode' => 'html',
                ]);
            }

            if (isset($response['buy'])) {
                $messagesBuy = $this->setMessage($response['buy'][0], 'buy');
                
                Telegram::bot('mybot')->sendMessage([
                    'chat_id' => env('TELEGRAM_TEST_BOT'),
                    'text' => $messagesBuy,
                    'parse_mode' => 'html',
                ]);

            }

        } catch (\Exception $e) {
            $this->error('Error sending messages to Telegram:' . $e->getMessage());
        }
    }
}
