<?php

namespace App\Console\Commands;

use App\Models\Crypto24Hours;
use App\Models\Crypto4Hours;
use App\Models\CryptoMinutes;
use App\Models\Peak24Hours;
use App\Models\Peak4Hours;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Telegram\Bot\Laravel\Facades\Telegram;

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

    private function getIncrease($old, $new)
    {
        $percentageIncrease = (($new - $old) / $old) * 100;
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
            foreach ($data as $symbol => $item) {
                if (strpos($symbol, '_usdt') !== false) {
                    $exchange = 'IO';

                    $newSymbol = strtoupper(str_replace('_usdt', '', $symbol));
                    $isMatch = collect($coinTc)->contains('baseAsset', $newSymbol);

                    if ($isMatch) {
                        $exchange = 'TC';
                    }

                    $result[] = [
                        'exc' => $exchange,
                        'symbol' => $newSymbol,
                        'price' => $item['last'],
                        'volume' => $item['baseVolume'],
                        'percent_change' => $item['percentChange']
                    ];
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
        $latestRecords = Peak4Hours::where('created_at', $latestCreatedAt)->get();
        return $latestRecords;
    }
    
    private function getLastPeak24Data()
    {
        $latestCreatedAt = Peak24Hours::latest('created_at')->value('created_at');
        $latestRecords = Peak24Hours::where('created_at', $latestCreatedAt)->get();
        return $latestRecords;
    }

    private function changeDateGMT($date, $flag)
    {
        $newDate = \DateTime::createFromFormat('Y-m-d H:i:s', $date, new \DateTimeZone('UTC'));
        // $newDate->modify('-7 hours');


        if ($flag == 'date') {
            return $newDate->format('d/m/Y');
        }
        if ($flag == 'time') {
            return $newDate->format('H:i');
        }
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

    private function setMessage($cryptoIncrease, $flag)
    {
        // Initialize the message variable
        $messages = '';

        $title = ($flag == 'buy') ? "🔥🔥🔥 <b>BUY!</b> 🔥🔥🔥" : "⏰ <b>ALERT! 🚀</b>";
        $messages .= "{$title}\n\n";

        foreach ($cryptoIncrease as $item) {
            // foreach ($items as $item) {
            $start = $this->changeDateGMT($item['created_at'], 'date');
            $openGmt = $this->changeDateGMT($item['created_at'], 'time');
            $hitGmt = $this->changeDateGMT($item['hit_time'], 'time');
            $open = $item['created_at']->format('H:i');
            // $hit = $item['hit_time']->format('H:i');

            // variable
            $titleHour = ($item['peak'] === '4') ? ' 4 Hours ' : ' 24 Hours ';
            $icon = (($flag === 'buy') ? "✅" : "✨");
            $marketCap = (($item['exchange'] === 'IO') ? "| {$this->formatNumber($item['market'])}" : "");


            // Format the message with the required information
            $formattedMessage = "{$icon} <b>{$item['symbol']}</b> {$start} | <b>{$titleHour} | {$item['exchange']} {$marketCap}</b>\n";
            $formattedMessage .= "{$open} - Open - 0.0% - {$this->setPrice($item['price'])}\n";
            $formattedMessage .= "{$item['hit_time']} - Change - {$this->getIncrease($item['price'],$item['hit'])}% - {$this->setPrice($item['hit'])}\n";

            $base = $item['price'] * 1.25;

            if ($flag == 'buy') {
                $formattedMessage .= "Target - 10% - {$this->setPrice(($base * 1.1))}\n";
                $formattedMessage .= "Target - 20% - {$this->setPrice(($base * 1.2))}\n";
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

    private function updateField($id, $field, $value, $type, $created_at)
    {
        if ($type == '24') {
            $peaks = Peak24Hours::where('id', $id)->where('created_at', $created_at)->firstOrFail();
            $peaks->update([$field => $value]);
        } else {
            $peaks = Peak4Hours::where('id', $id)->where('created_at', $created_at)->firstOrFail();
            $peaks->update([$field => $value]);
        }
    }

    private function cryptoIncrease()
    {
        $dataLast = $this->getLastMinutesData();
        $dataPeak4 = $this->getLastPeak4Data();
        $dataPeak24 = $this->getLastPeak24Data();

        $result = [];

        $openTime4 = Crypto4Hours::latest('created_at')->value('created_at');
        $openTime24 = Crypto24Hours::latest('created_at')->value('created_at');
        $currentDateTime = Carbon::now();
        $hitTime = $currentDateTime->format('H:i');
        
        
        Telegram::bot('mybot')->sendMessage([
            'chat_id' => env('TELEGRAM_CHAT_ID'),
            'text' => 'test',
            'parse_mode' => 'html',
        ]);


        foreach ($dataLast as $lastItem) {
            // match symbol
            $peak4Item = $dataPeak4->where('symbol', $lastItem['symbol'])->first();
            $peak24Item = $dataPeak24->where('symbol', $lastItem['symbol'])->first();


            // handle 4 hours
            if ($peak4Item && $lastItem['price'] > 0) {
                // increase 20%
                if (!$peak4Item['hit_time_20']) {
                    if ($lastItem['price'] >= ($peak4Item->price * 1.08) && $lastItem['price'] < ($peak4Item->price * 1.25)) {
                        $result['alert'][] = [
                            'id' => $peak4Item->id,
                            'symbol' => $peak4Item->symbol,
                            'created_at' => $openTime4,
                            'price' => $peak4Item->price,
                            'hit' => $lastItem['price'],
                            'hit_time' => $hitTime,
                            'peak' => '4',
                            'exchange' => $lastItem['exc'],
                            'market' => $lastItem['volume']
                        ];

                        // $hit = $this->getIncrease($peak4Item->price, $lastItem['price']);
                        // $this->updateField($peak4Item->id, 'hit_20', $hit, '4', $openTime4);
                        // $this->updateField($peak4Item->id, 'hit_time_20', $hitTime, '4', $openTime4);
                    }
                }

                // increase 25%
                if ($peak4Item['status_25'] == 0) {
                    if ($lastItem['price'] >= ($peak4Item->price * 1.25)) {
                        $result['buy'][] = [
                            'id' => $peak4Item->id,
                            'symbol' => $peak4Item->symbol,
                            'created_at' => $openTime4,
                            'price' => $peak4Item->price,
                            'hit' => $lastItem['price'],
                            'hit_time' => $hitTime,
                            'peak' => '4',
                            'exchange' => $lastItem['exc'],
                            'market' => $lastItem['volume']
                        ];

                        // $hit = $this->getIncrease($peak4Item->price, $lastItem['price']);
                        // $this->updateField($peak4Item->id, 'hit_25', $hit, '4', $openTime4);
                        // $this->updateField($peak4Item->id, 'hit_time_25', $hitTime, '4', $openTime4);
                        // $this->updateField($peak4Item->id, 'status_25', 1, '4', $openTime4);
                    }
                }
            }

            // handle 24 hours
            if ($peak24Item) {
                // increase 20%
                if (!$peak24Item['hit_time_20']) {
                    if ($lastItem['price'] >= ($peak24Item->price * 1.2) && $lastItem['price'] < ($peak24Item->price * 1.25)) {
                        $result['alert'][] = [
                            'id' => $peak24Item->id,
                            'symbol' => $peak24Item->symbol,
                            'created_at' => $openTime24,
                            'price' => $peak24Item->price,
                            'hit' => $lastItem['price'],
                            'hit_time' => $hitTime,
                            'peak' => '24',
                            'exchange' => $lastItem['exc'],
                            'market' => $lastItem['volume']
                        ];

                        // $hit = $this->getIncrease($peak24Item->price, $lastItem['price']);
                        // $this->updateField($peak24Item->id, 'hit_20', $hit, '24', $openTime24);
                        // $this->updateField($peak24Item->id, 'hit_time_20', $hitTime, '24', $openTime24);
                    }
                }

                // increase 25%
                if ($peak24Item['status_25'] == 0) {
                    if ($lastItem['price'] >= ($peak24Item->price * 1.25)) {
                        $result['buy'][] = [
                            'id' => $peak24Item->id,
                            'symbol' => $peak24Item->symbol,
                            'created_at' => $openTime24,
                            'price' => $peak24Item->price,
                            'hit' => $lastItem['price'],
                            'hit_time' => $hitTime,
                            'peak' => '24',
                            'exchange' => $lastItem['exc'],
                            'market' => $lastItem['volume']
                        ];

                        // $hit = $this->getIncrease($peak24Item->price, $lastItem['price']);
                        // $this->updateField($peak24Item->id, 'hit_25', $hit, '24', $openTime24);
                        // $this->updateField($peak24Item->id, 'hit_time_25', $hitTime, '24', $openTime24);
                        // $this->updateField($peak24Item->id, 'status_25', 1, '24', $openTime24);
                    }
                }
            }
        }

        return $result;
    }

    public function handle()
    {
        try {
            $response = $this->cryptoIncrease();

            if (isset($response['buy'])) {
                $messagesBuy = $this->setMessage($response['buy'], 'buy');
                Telegram::bot('mybot')->sendMessage([
                    'chat_id' => env('TELEGRAM_CHAT_ID'),
                    'text' => $messagesBuy,
                    'parse_mode' => 'html',
                ]);
            }

            if (isset($response['alert'])) {
                $messagesAlert = $this->setMessage($response['alert'], 'alert');
                Telegram::bot('mybot')->sendMessage([
                    'chat_id' => env('TELEGRAM_CHAT_ID'),
                    'text' => $messagesAlert,
                    'parse_mode' => 'html',
                ]);
            }
        } catch (\Exception $e) {
            $this->error('Error sending messages to Telegram:' . $e->getMessage());
        }
    }
}
