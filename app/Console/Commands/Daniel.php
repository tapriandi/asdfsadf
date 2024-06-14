<?php

namespace App\Console\Commands;

use App\Models\Crypto24Hours;
use App\Models\ExistPair;
use App\Models\Peak24Hours;
use App\Models\Peak4Hours;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Telegram\Bot\Laravel\Facades\Telegram;

use Storage;



class DanielCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:daniel-command';

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
        // if($oldPrice == 0){
        //     file_put_contents('/home/astj9531/public_html/mycrypto.astodigi.com/laravel/ketemu0digetincrease.txt', $oldprice.'----'.$newPrice);
            
        // }
        
        // file_put_contents('/home/astj9531/public_html/mycrypto.astodigi.com/laravel/semogabenergetincrease.txt', $oldPrice);
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
        $response = Http::get($url);
        $data = $response->json();
        $coinTc = $this->getCoinTC();

        $result = [];

        if ($data) {
            // file_put_contents('/home/astj9531/public_html/mycrypto.astodigi.com/laravel/menitGetApi.txt', print_r($symbol,true));
            
            foreach ($data as $symbol => $item) {
                if (strpos($symbol, '_usdt') !== false) {
                    $exchange = 'IO';

                    $newSymbol = strtoupper(str_replace('_usdt', '', $symbol));
                    $isMatch = collect($coinTc)->contains('baseAsset', $newSymbol);

                    if ($isMatch) {
                        $exchange = 'TC';
                    }
                    if($item['last']==0)
                    {
                        file_put_contents('/home/astj9531/public_html/mycrypto.astodigi.com/laravel/ceksampeakar.txt', print_r($symbol,true));
                    }
                    else{
                        // file_put_contents('/home/astj9531/public_html/mycrypto.astodigi.com/laravel/ketemudielse.txt', print_r($symbol,true));
                        $result[] = [
                            'exc' => $exchange,
                            'symbol' => $newSymbol,
                            'price' => $item['last'],
                            'volume' => $item['baseVolume'],
                            'percent_change' => $item['percentChange']
                        ];
                    }
                }
            }
            $this->info('Crypto Rate minutes saved successfully.');
        } else {
            $this->error('Failed to fetch Crypto Rate minutes.');
        }
        
        // file_put_contents('/home/astj9531/public_html/mycrypto.astodigi.com/laravel/dataPermenit.txt', print_r($result,true));

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
    //   $fp = fopen('/home/astj9531/public_html/mycrypto.astodigi.com/laravel/cekMessage.txt', 'w');
    //     fwrite($fp, "result 24 jam test");
    //     fclose($fp);
        
        // Initialize the message variable
        $messages = '';

        // title
        if ($flag == 'buy') {
            $title = "🔥🔥🔥 <b>BUY!</b> 🔥🔥🔥";
        } else if ($flag == 'alert') {
            $title = "⏰ <b>ALERT! 🚀</b>";
        } else if ($flag == 'sell1') {
            $title = "💢💢💢 <b>SELL!</b> 💢💢💢";
        } else {
            $title = '';
        }

        $messages .= "{$title}\n\n";

        foreach ($cryptoIncrease as $item) {
            // variable
            $start = $item['created_at']->format('H:i');
            $open = $item['created_at']->format('H:i');
            $hit = $item['hit_time']->format('H:i');

            // sell
            if (isset($item['peak_hit_25_time'])) {
                $hitTime25 = $item['peak_hit_25_time']->format('H:i');
            }

            // icon
            if ($flag == 'buy') {
                $icon = "✅";
            } else if ($flag == 'alert') {
                $icon = "✨";
            } else if ($flag == 'sell1') {
                $icon = "💥";
            } else {
                $icon = '';
            }

            $titleHour = ($item['peak'] === '4') ? ' 4 Hours ' : ' 24 Hours ';
            $marketCap = (($item['exchange'] === 'IO') ? "| {$this->formatNumber($item['volume'])}" : "");

            // Format the message with the required information
            $formattedMessage = "{$icon} <b>{$item['symbol']}</b> {$start} | <b>{$titleHour} | {$item['exchange']} {$marketCap}</b>\n";
            $formattedMessage .= "{$open} - Open - 0.0% - {$this->setPrice($item['price'])}\n";

            if ($flag !== 'sell1') {
                $formattedMessage .= "{$hit} - Change - {$this->getIncrease($item['price'],$item['hit'])}% - {$this->setPrice($item['hit'])}\n";
            }

            if ($flag == 'sell1') {
                $formattedMessage .= "{$hitTime25} - Change - {$this->getIncrease($item['price'],$item['peak_hit_25'])}% - {$item['peak_hit_25']}\n";
                $formattedMessage .= "{$hit} - Sell - {$this->getIncrease($item['price'],$item['hit'])}% - {$this->setPrice($item['hit'])}\n";
            }

            $base = $item['price'] * 1.25;
            $t10 = $base * 1.1;

            if ($flag == 'buy') {
                $formattedMessage .= "Target - 10% - {$this->setPrice($t10)}\n";
                $formattedMessage .= "Target - 20% - {$this->setPrice($base * 1.2)}\n";
                $formattedMessage .= "Target - 30% - {$this->setPrice(($base * 1.3))}\n";
                $formattedMessage .= "Target - 40% - {$this->setPrice(($base * 1.4))}\n";
                $formattedMessage .= "Target - 50% - {$this->setPrice(($base * 1.5))}\n";
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
                    // $hit = $this->getIncrease([$item['price']], $item['hit']);
                    $this->updateField($item['id'], 'hit_20', $item['hit'], '4');
                    $this->updateField($item['id'], 'hit_time_20', $item['hit_time'], '4');
                }
                if ($item['peak'] == '24') {
                    // update data peak 24: alert
                    // $hit = $this->getIncrease($item['price'], $item['hit']);
                    $this->updateField($item['id'], 'hit_20', $item['hit'], '24');
                    $this->updateField($item['id'], 'hit_time_20', $item['hit_time'], '24');
                }
            }
            
            if ($flag == 'buy') {
                if ($item['peak'] == '4') {
                    // update data peak 4: buy
                    // $hit = $this->getIncrease($item['price'], $item['hit']);
                    $this->updateField($item['id'], 'hit_25', $item['hit'], '4');
                    $this->updateField($item['id'], 'hit_time_25', $item['hit_time'], '4');
                    $this->updateField($item['id'], 'status_25', 1, '4');
                }
                if ($item['peak'] == '24') {
                    // update data peak 24: buy
                    // $hit = $this->getIncrease($item['price'], $item['hit']);
                    $this->updateField($item['id'], 'hit_25', $item['hit'], '24');
                    $this->updateField($item['id'], 'hit_time_25', $item['hit_time'], '24');
                    $this->updateField($item['id'], 'status_25', 1, '24');
                }
            }
        }
    }

    private function cryptoIncrease4($dataLast, $dataPeak4, $openTime4, $hitTime)
    {
        // $result = [
        //     'alert' => [],
        //     'buy' => [],
        // ];
        
        $resultMessage = [];
        $alert = 0;
        $buy = 0;
        
        
        
        // file_put_contents('/home/astj9531/public_html/mycrypto.astodigi.com/laravel/Res2.txt', print_r($dataLast));
        foreach ($dataLast as $lastItem) {
            // $fp = fopen('/home/astj9531/public_html/mycrypto.astodigi.com/laravel/Res.txt', 'w');
            // fwrite($fp, serialize($dataLast));
            // fclose($fp);
            
            // match symbol
            $peak4Item = $dataPeak4->where('symbol', $lastItem['symbol'])->first();

            // handle 4 hours
            if (isset($peak4Item)) {
                // increase 20%
                
                // $fp = fopen('/home/astj9531/public_html/mycrypto.astodigi.com/laravel/p4outer.txt', 'w');
                // fwrite($fp, "harga terakhir > 0 dalam 4 jam ". $peak4Item->price . ' ' . $lastItem['price']);
                // fclose($fp);

                if (!$peak4Item['hit_time_20']) {
                    if ($lastItem['price'] >= ($peak4Item->price * 1.2) && $lastItem['price'] < ($peak4Item->price * 1.125)) {
                        
                        // $fp = fopen('/home/astj9531/public_html/mycrypto.astodigi.com/laravel/p4alert.txt', 'w');
                        // fwrite($fp, "masuk 4 jam alert". $peak4Item->price . ' ' . $lastItem['price']);
                        // fclose($fp);

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
                        
                        // update data peak 4: alert
                        // $hit = $this->getIncrease($peak4Item->price, $lastItem['price']);
                        // $this->updateField($peak4Item->id, 'hit_20', $hit, '4', $openTime4);
                        // $this->updateField($peak4Item->id, 'hit_time_20', $hitTime, '4', $openTime4);
                    }
                }
                
                
                // increase 25%
                if ($peak4Item['status_25'] == 0) {
                    if ($lastItem['price'] >= ($peak4Item->price * 1.25)) {
                        
                        // $fp = fopen('/home/astj9531/public_html/mycrypto.astodigi.com/laravel/p4buy.txt', 'w');
                        // fwrite($fp, "masuk 4 jam buy". $peak4Item->price . ' ' . $lastItem['price']);
                        // fclose($fp);

                        $resultMessage['buy'][$buy] = [
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
                        $buy++;
                        
                        // update data peak 4: buy
                        // $hit = $this->getIncrease($peak4Item->price, $lastItem['price']);
                        // $this->updateField($peak4Item->id, 'hit_25', $hit, '4', $openTime4);
                        // $this->updateField($peak4Item->id, 'hit_time_25', $hitTime, '4', $openTime4);
                        // $this->updateField($peak4Item->id, 'status_25', 1, '4', $openTime4);
                    }
                }
            }
             
            // sampe sini masuk: test -> set alert > 11%
            // file_put_contents('/home/astj9531/public_html/mycrypto.astodigi.com/laravel/p4resultditengah.txt', print_r($resultMessage, true));
        }
        
        // file_put_contents('/home/astj9531/public_html/mycrypto.astodigi.com/laravel/p4result.txt', print_r($resultMessage, true));

        return $resultMessage;
       
    }

    private function cryptoIncrease24($dataLast, $dataPeak24, $openTime24, $hitTime)
    {
        // $result = [
        //     'alert' => [],
        //     'buy' => [],
        // ];
        
        $resultMessage = [];
        $buy = 0;
        $alert = 0;

        foreach ($dataLast as $lastItem) {
            $peak24Item = $dataPeak24->firstWhere('symbol', $lastItem['symbol']);

            // handle 24 hours
            if (isset($peak24Item)) {
                // increase 20%
                
                // $fp = fopen('/home/astj9531/public_html/mycrypto.astodigi.com/laravel/p24alertOuter.txt', 'w');
                // fwrite($fp, "harga terakhir > 0 dalam 24 jam ". $peak24Item->price . ' ' . $lastItem['price']);
                // fclose($fp);

                if (!$peak24Item['hit_time_20']) {
                    if ($lastItem['price'] >= ($peak24Item->price * 1.2) && $lastItem['price'] < ($peak24Item->price * 1.25)) {
                        
                        // $fp = fopen('/home/astj9531/public_html/mycrypto.astodigi.com/laravel/p24alert.txt', 'w');
                        // fwrite($fp, "masuk 24 jam alert ". $peak24Item->price . ' ' . $lastItem['price']);
                        // fclose($fp);
                        
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

                        // update data peak 24: alert
                        // $hit = $this->getIncrease($peak24Item->price, $lastItem['price']);
                        // $this->updateField($peak24Item->id, 'hit_20', $hit, '24', $openTime24);
                        // $this->updateField($peak24Item->id, 'hit_time_20', $hitTime, '24', $openTime24);
                    }
                }

                // increase 25%
                if ($peak24Item['status_25'] == 0) {
                    if ($lastItem['price'] >= ($peak24Item->price * 1.25)) {
                        
                        // $fp = fopen('/home/astj9531/public_html/mycrypto.astodigi.com/laravel/p24buy.txt', 'w');
                        // fwrite($fp, "masuk 24 jam buy ". $peak24Item->price . ' ' . $lastItem['price']);
                        // fclose($fp);
                        
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

                        // update data peak 24: buy
                        // $hit = $this->getIncrease($peak24Item->price, $lastItem['price']);
                        // $this->updateField($peak24Item->id, 'hit_25', $hit, '24', $openTime24);
                        // $this->updateField($peak24Item->id, 'hit_time_25', $hitTime, '24', $openTime24);
                        // $this->updateField($peak24Item->id, 'status_25', 1, '24', $openTime24);
                    }
                }
            }
        }
        
        //  set alert > 11%
        // file_put_contents('/home/astj9531/public_html/mycrypto.astodigi.com/laravel/p24result.txt', print_r($resultMessage,true));

        return $resultMessage;
    }

    private function cryptoSell1($dataLast, $dataPeak4, $dataPeak24, $openTime4, $openTime24, $hitTime)
    {
        $result = [];

        foreach ($dataLast as $lastItem) {
            // match symbol
            $peak4Item = $dataPeak4->firstWhere('symbol', $lastItem['symbol']);
            $peak24Item = $dataPeak24->firstWhere('symbol', $lastItem['symbol']);

            // handle sell skema 1
            if ($lastItem['price'] > 0) {
                if ($peak4Item['status_25'] > 0) {
                    if ($lastItem['price'] <= ($peak4Item['hit_25'] * 0.8)) {
                        $result['sell1'][] = [
                            'id' => $peak4Item->id,
                            'symbol' => $peak4Item->symbol,
                            'created_at' => $openTime4,
                            'price' => $peak4Item->price,
                            'hit' => $lastItem['price'],
                            'hit_time' => $hitTime,
                            'peak' => '4',
                            'exchange' => $lastItem['exc'],
                            'volume' => $lastItem['volume'],
                            'peak_hit_25' => $peak4Item['hit_25'],
                            'peak_hit_25_time' => $peak4Item['hit_time_25'],
                        ];
                    }
                }
                if ($peak24Item['status_25'] > 0) {
                    if ($lastItem['price'] >= ($peak24Item['hit_25'] * 0.8)) {
                        $result['sell1'][] = [
                            'id' => $peak24Item->id,
                            'symbol' => $peak24Item->symbol,
                            'created_at' => $openTime24,
                            'price' => $peak24Item->price,
                            'hit' => $lastItem['price'],
                            'hit_time' => $hitTime,
                            'peak' => '24',
                            'exchange' => $lastItem['exc'],
                            'volume' => $lastItem['volume'],
                            'peak_hit_25' => $peak4Item['hit_25'],
                            'peak_hit_25_time' => $peak4Item['hit_time_25'],
                        ];
                    }
                }
            }
        }

        return $result;
    }

    private function cryptoIncrease()
    {
        $dataLast = $this->getLastMinutesData();
        $dataPeak4 = $this->getLastPeak4Data();
        $dataPeak24 = $this->getLastPeak24Data();

        $result = [];

        $openTime4 = Peak4Hours::latest('created_at')->value('created_at');
        $openTime24 = Peak24Hours::latest('created_at')->value('created_at');
        $hitTime = Carbon::now();
        
        // file_put_contents('/home/astj9531/public_html/mycrypto.astodigi.com/laravel/increase4sebelum.txt', print_r($dataPeak4,true));

        // 4 hour: checking increase and make message
        if (isset($dataLast) && isset($dataPeak4)) {
            // file_put_contents('/home/astj9531/public_html/mycrypto.astodigi.com/laravel/increase4masuk.txt', 'data lastMinutes dan dataPeak 4');

            $res = $this->cryptoIncrease4($dataLast, $dataPeak4, $openTime4, $hitTime);
            
            // file_put_contents('/home/astj9531/public_html/mycrypto.astodigi.com/laravel/increase4res.txt', print_r($res,true));
            
            if (isset($res['alert'])) {
                $result['alert'][] = $res['alert'];
            }
            if (isset($res['buy'])) {
                $result['buy'][] =  $res['buy'];
            }
        }

        // 24 hour: checking increase and make message
        if (isset($dataLast) && isset($dataPeak24)) {
            
            // file_put_contents('/home/astj9531/public_html/mycrypto.astodigi.com/laravel/increase24masuk.txt', 'data lastMinutes dan dataPeak 24');
                
            $res = $this->cryptoIncrease24($dataLast, $dataPeak24, $openTime24, $hitTime);
            // file_put_contents('/home/astj9531/public_html/mycrypto.astodigi.com/laravel/increase24res.txt', print_r($res,true));
            
            if (isset($res['alert'])) {
                $result['alert'][] = $res['alert'];
            }
            if (isset($res['buy'])) {
                $result['buy'][] =  $res['buy'];
            }
        }

        // file_put_contents('/home/astj9531/public_html/mycrypto.astodigi.com/laravel/increaseResult.txt', print_r($result,true));
        return $result;
    }

    public function handle()
    {
        
        // latest condition: message akan terkirim di telegram dengan kondisi update dataPeak di comment
        // Tugas: cari alternative cara untuk update dataPeak
        
        try {
            // $response = $this->cryptoIncrease();
            
            Telegram::bot('mybot')->sendMessage([
                'chat_id' => env('TELEGRAM_CHAT_ID'),
                'text' => 'test bot',
                'parse_mode' => 'html',
            ]);
            
            
            // file_put_contents('/home/astj9531/public_html/mycrypto.astodigi.com/laravel/increaseResponse.txt', print_r($response,true));

            // if (isset($response['alert'])) {
            //     $messagesAlert = $this->setMessage($response['alert'][0], 'alert');
            //     // lagi coba buat funtion update dataPeak disini
            //     // file_put_contents('/home/astj9531/public_html/mycrypto.astodigi.com/laravel/setMessage4.txt', print_r($messagesAlert,true));
            //     Telegram::bot('mybot')->sendMessage([
            //         'chat_id' => env('TELEGRAM_CHAT_ID'),
            //         'text' => $messagesAlert,
            //         'parse_mode' => 'html',
            //     ]);
                
            //     $this->updatePeakdata($response['alert'][0], 'alert');
            // }

            // if (isset($response['buy'])) {
            //     $messagesBuy = $this->setMessage($response['buy'][0], 'buy');
            //     // lagi coba buat funtion update dataPeak disini
            //     Telegram::bot('mybot')->sendMessage([
            //         'chat_id' => env('TELEGRAM_CHAT_ID'),
            //         'text' => $messagesBuy,
            //         'parse_mode' => 'html',
            //     ]);
                
            //     $this->updatePeakdata($response['buy'][0], 'buy');
            // }

        } catch (\Exception $e) {
            $this->error('Error sending messages to Telegram:' . $e->getMessage());
        }
    }
}
