<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BinanceService
{
    protected $apiKey;
    protected $secret;
    protected $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('services.binance.key', env('BINANCE_API_KEY'));
        $this->secret = config('services.binance.secret', env('BINANCE_API_SECRET'));
        $this->baseUrl = env('BINANCE_BASE_URL', 'https://api.binance.com');
    }

    private function sign(array $params)
    {
        $query = http_build_query($params);
        $signature = hash_hmac('sha256', $query, $this->secret);
        return $query . '&signature=' . $signature;
    }

    private function headers()
    {
        return [
            'X-MBX-APIKEY' => $this->apiKey,
        ];
    }

    public function getQuote($fromAsset, $toAsset, $fromAmount)
    {

        $params = [
            'fromAsset' => $fromAsset,
            'toAsset'   => $toAsset,
            'fromAmount' => $fromAmount,
            'recvWindow' => 5000,
            'timestamp' => round(microtime(true) * 1000),
        ];
        Log::info($this->apiKey);

        $serverTime = Http::get('https://api.binance.com/api/v3/time')->json();
        Log::info($serverTime);
        $params['timestamp'] = $serverTime['serverTime'];
        $query = http_build_query($params);
        $signature = hash_hmac('sha256', $query, $this->secret);
        Log::info($signature);
        $url = "{$this->baseUrl}/sapi/v1/convert/getQuote?{$query}&signature={$signature}";

        $response = Http::withHeaders(['X-MBX-APIKEY' => $this->apiKey])
            ->post($url);

        return $response->json();

        // $query = $this->sign($params);

        // $response = Http::withHeaders($this->headers())
        //     ->post("{$this->baseUrl}/sapi/v1/convert/getQuote?{$query}");

        // return $response->json();
    }

    public function acceptQuote($quoteId)
    {
        $params = [
            'quoteId'   => $quoteId,
            'recvWindow' => 5000,
            'timestamp' => round(microtime(true) * 1000),
        ];

        $query = $this->sign($params);

        $response = Http::withHeaders($this->headers())
            ->post("{$this->baseUrl}/sapi/v1/convert/acceptQuote?{$query}");

        return $response->json();
    }

    public function getPrice($symbol = null)
    {
        $url = $this->baseUrl . '/api/v3/ticker/price';

        if ($symbol) {
            $url .= '?symbol=' . strtoupper($symbol);
        }

        $response = Http::get($url);
        return $response->json();
    }
}
