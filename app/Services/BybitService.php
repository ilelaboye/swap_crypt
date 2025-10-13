<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BybitService
{
    protected $apiKey;
    protected $secret;
    protected $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('services.binance.key', env('BYBIT_API_KEY'));
        $this->secret = config('services.binance.secret', env('BYBIT_API_SECRET'));
        $this->baseUrl = env('BYBIT_BASE_URL', 'https://api.bybit.com'); //api-testnet.bybit.com
    }

    private function signRequest($payload, $secret)
    {
        return hash_hmac('sha256', $payload, $secret);
    }

    public function getQuote($fromAsset, $toAsset, $fromAmount)
    {
        $timestamp = time() * 1000; //round(microtime(true) * 1000);
        $recvWindow = 5000;
        $body = [
            'fromCoin' => $fromAsset,
            'toCoin' => $toAsset,
            "requestCoin" =>  $fromAsset,
            'requestAmount' => $fromAmount,
            'accountType' => 'eb_convert_funding'
        ];
        $jsonBody = json_encode($body);

        $payload = $timestamp . $this->apiKey . $recvWindow . $jsonBody;
        $sign = hash_hmac('sha256', $payload, $this->secret);

        $response = Http::withHeaders([
            'X-BAPI-API-KEY' => $this->apiKey,
            'X-BAPI-SIGN' => $sign,
            'X-BAPI-TIMESTAMP' => $timestamp,
            'X-BAPI-RECV-WINDOW' => $recvWindow,
            'Content-Type' => 'application/json',
        ])->post("{$this->baseUrl}/v5/asset/exchange/quote-apply", $body);

        $quoteData = $response->json();

        Log::info('Quote: ' . $response);

        return $quoteData;
    }

    public function acceptQuoteWithQuoteId($quoteTxId)
    {
        $timestamp = time() * 1000; //round(microtime(true) * 1000);
        $recvWindow = 5000;
        $body = ['quoteTxId' => $quoteTxId];

        $jsonBody = json_encode($body);
        $payload = $timestamp . $this->apiKey . $recvWindow . $jsonBody;
        $sign = hash_hmac('sha256', $payload, $this->secret);
        $confirmResponse = Http::withHeaders([
            'X-BAPI-API-KEY' => $this->apiKey,
            'X-BAPI-SIGN' => $sign,
            'X-BAPI-TIMESTAMP' => $timestamp,
            'X-BAPI-RECV-WINDOW' => $recvWindow,
            'Content-Type' => 'application/json',
        ])->post("{$this->baseUrl}/v5/asset/exchange/convert-execute", $body);

        return $confirmResponse->json();
    }

    public function confirmQuoteConvertStatus($quoteTxId)
    {
        Log::info('$quoteTxId: ' . $quoteTxId);

        $timestamp = time() * 1000; //round(microtime(true) * 1000);
        $recvWindow = 5000;
        $query = "quoteTxId={$quoteTxId}&accountType=eb_convert_funding";

        $payload = $timestamp . $this->apiKey . $recvWindow . $query;
        $sign = hash_hmac('sha256', $payload, $this->secret); //$this->signRequest($confirmPayload, $this->secret);
        Log::info('payload: ' . $payload);
        Log::info('sign: ' . $sign);
        $confirmResponse = Http::withHeaders([
            'X-BAPI-API-KEY' => $this->apiKey,
            'X-BAPI-SIGN' => $sign,
            'X-BAPI-TIMESTAMP' => $timestamp,
            'X-BAPI-RECV-WINDOW' => $recvWindow,
            'Content-Type' => 'application/json',
        ])->get("{$this->baseUrl}/v5/asset/exchange/convert-result-query?{$query}",);

        return $confirmResponse->json();
    }

    public function acceptQuote()
    {
        $quoteData = $this->getQuote('USDT', "ADA", 1);
        $quoteTxId = $quoteData['result']['quoteTxId'] ?? null;

        if (!$quoteTxId) return response()->json(['error' => 'Failed to get quote', 'data' => $quoteData]);

        $timestamp = round(microtime(true) * 1000);
        $recvWindow = 5000;

        $confirmBody = json_encode(['quoteTxId' => $quoteTxId]);
        $confirmPayload = $timestamp . $this->apiKey . $recvWindow . $confirmBody;
        $confirmSign = $this->signRequest($confirmPayload, $this->secret);

        $confirmResponse = Http::withHeaders([
            'X-BYBIT-API-KEY' => $this->apiKey,
            'X-BYBIT-SIGN' => $confirmSign,
            'X-BYBIT-TIMESTAMP' => $timestamp,
            'X-BYBIT-RECV-WINDOW' => $recvWindow,
            'Content-Type' => 'application/json',
        ])->post("{$this->baseUrl}/v5/asset/convert/confirm-quote", ['quoteTxId' => $quoteTxId]);

        return $confirmResponse->json();
    }

    public function getPrice($symbol = null)
    {
        $url = $this->baseUrl . "/v5/market/tickers?category=spot&symbol={$symbol}";

        Log::info("Bybit Price URL: " . $url);

        $response = Http::get($url);
        return $response->json();
    }
}
