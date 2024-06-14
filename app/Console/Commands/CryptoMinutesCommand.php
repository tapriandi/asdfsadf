<?php

namespace App\Console\Commands;

use App\Models\Crypto4Hours;
use App\Models\CryptoMinutes;
use App\Models\ExistPair;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class CryptoMinutesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:crypto-minutes-command';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */


    public function handle()
    {

        $url = 'https://api.gate.io/api2/1/tickers';
        $response = Http::get($url);
        $data = $response->json();
        
        $fp = fopen('/home/astj9531/public_html/mycrypto.astodigi.com/success.txt', 'w');
        fwrite($fp, $data);
        fclose($fp);

        if ($data) {
            foreach ($data as $symbol => $item) {
                if (strpos($symbol, '_usdt') !== false) {
                    $newSymbol = strtoupper(str_replace('_usdt', '', $symbol));
                    CryptoMinutes::create([
                        'symbol' => $newSymbol,
                        'price' => $item['last'],
                        'percent_change' => $item['percentChange']
                    ]);
                }
            }

            $this->info('Crypto Rate minutes saved successfully.');
        } else {
            $this->error('Failed to fetch Crypto Rate minutes.');
        }
    }
}
