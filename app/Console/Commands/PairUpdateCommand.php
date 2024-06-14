<?php

namespace App\Console\Commands;

use App\Models\ExistPair;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class PairUpdateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:pair-update-command';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */

    private function getCoinmarketcap()
    {
        $url = 'https://api.gate.io/api2/1/tickers';
        $response = Http::get($url);
        $data = $response->json();

        return $data;
    }

    private function getTokocrypto()
    {
        $url = 'https://www.tokocrypto.com/open/v1/common/symbols';
        $response = Http::get($url);
        $data = $response->json()['data']['list'];

        return $data;
    }

    public function handle()
    {
        try {
            $coinmarketcapData = $this->getCoinmarketcap();
            $tokocryptoData = $this->getTokocrypto();


            if ($coinmarketcapData) {
                foreach ($coinmarketcapData as $symbol => $item) {
                    $removeSuffix = strpos($symbol, '_usdt');

                    if ($removeSuffix !== false) {
                        $exchange = 'IO';
                        $newSymbol = strtoupper(str_replace('_usdt', '', $symbol));
                        $symbolExistsInTokocrypto = collect($tokocryptoData)->contains('baseAsset', $newSymbol);

                        if ($symbolExistsInTokocrypto) {
                            $exchange = 'TC';
                        }

                        // $exchangeString = implode('/', $exchange);
                        // $marketCap = $item['quote']['USDT']['market_cap'];
                        // dd($newSymbol, $this->formatNumber($item['baseVolume']));

                        ExistPair::create([
                            'name' => $newSymbol,
                            'symbol' => $newSymbol,
                            'exchange' => $exchange,
                            'market' => $item['baseVolume'],
                        ]);
                    }
                }
            }

            $this->info('ExistPair table updated successfully.');
        } catch (\Exception $e) {
            $this->error('Failed to update ExistPair table: ' . $e->getMessage());
        }
    }
}
