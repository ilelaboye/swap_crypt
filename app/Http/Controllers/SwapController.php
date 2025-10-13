<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\BinanceService;
use App\Services\BybitService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SwapController extends Controller
{
    public function swap()
    {
        $binance = new BinanceService();

        // Example: Swap 100 USDT → BTC
        $quote = $binance->getQuote("USDT", "ADA", 1);
        return $quote;
        // if (isset($quote['quoteId'])) {
        //     $result = $binance->acceptQuote($quote['quoteId']);
        //     return response()->json([
        //         'quote' => $quote,
        //         'result' => $result
        //     ]);
        // }

        // return response()->json($quote); // error message
    }

    public function getPrice()
    {
        $binance = new \App\Services\BinanceService();

        // Get price of BTC/USDT
        $ada = $binance->getPrice("ADAUSDT");

        // // Get price of ADA/USDT
        // $ada = $binance->getPrice("ADAUSDT");

        // // Get ALL prices
        // $all = $binance->getPrice();

        return response()->json([
            'ada' => $ada,
            // 'ada' => $ada,
            // 'all' => $all
        ]);
    }

    public function getBybitQuote()
    {
        $bybit = new BybitService();
        $quote = $bybit->getQuote("USDT", "ADA", '1');
        return $quote;
    }

    public function bybitADASwap()
    {
        return $this->calculate('ADA');
    }

    public function convertBybitConvertStatus(Request $request)
    {
        $quoteTxId = $request->query('quoteTxId');
        $bybit = new BybitService();
        return $bybit->confirmQuoteConvertStatus($quoteTxId);
    }

    public function calculate($currency)
    {
        $trans = DB::table('transactions')->where('status', true)->where('currency', $currency)->latest()->first();
        $bybit = new BybitService();
        $amount = "1";
        if ($trans) {
            Log::info('found');
            // check if price has increase by 1% or 0.5%, if yes swap back to usdt, if no, then don't swap
            $checkQuoteData = $bybit->getQuote("USDT", $currency, $amount);
            Log::info($checkQuoteData);
            $new_price = floatval($amount) / floatval($checkQuoteData['result']['exchangeRate']);
            if ($new_price > $trans->purchase_price) {
                $change = (($new_price - $trans->purchase_price) / $trans->purchase_price) * 100;
                Log::info('new price ' . $new_price);
                Log::info('old price ' . $trans->purchase_price);
                Log::info('incresed ' . $change);
                //if it has increased by 1%, swap back to usdt
                if ($change >= 0.7) {
                    $quoteData = $bybit->getQuote($currency, 'USDT', "{$trans->purchase_quantity}");
                    $quoteTxId = $quoteData['result']['quoteTxId'] ?? null;
                    if (!$quoteTxId) return response()->json(['error' => 'Failed to get quote', 'data' => $quoteData]);
                    $accept = $bybit->acceptQuoteWithQuoteId($quoteTxId);
                    $update = DB::table('transactions')->where('status', true)->where('currency', $currency)->update([
                        'swap_price' => $new_price,
                        'swap_quantity' => $quoteData['result']['exchangeRate'],
                        'profit' => floatval($new_price) - floatval($trans->purchase_price),
                        'sold_at' => now(),
                        'soldQuoteTxId' => $quoteTxId,
                        'status' => false
                    ]);

                    return $accept;
                }
            }
        } else {
            // since there is no active trade, buy at the current price

            $quoteData = $bybit->getQuote("USDT", $currency, $amount);
            $quoteTxId = $quoteData['result']['quoteTxId'] ?? null;

            if (!$quoteTxId) return response()->json(['error' => 'Failed to get quote', 'data' => $quoteData]);

            $accept = $bybit->acceptQuoteWithQuoteId($quoteTxId);
            Log::info('Accept quote: ');
            Log::info($accept);
            $purchase_price = floatval($amount) / floatval($quoteData['result']['exchangeRate']); // 1 ADA = how many usdt
            $save = DB::table('transactions')->insert([
                'purchase_price' => $purchase_price, // 1 ADA = how many usdt
                'currency' => $currency,
                'bought_at' => now(),
                'base_amount' => $amount,
                'purchase_quantity' => $quoteData['result']['exchangeRate'],
                'quoteTxId' => $accept['result']['quoteTxId']
            ]);
            return $accept;
        }
    }
}
