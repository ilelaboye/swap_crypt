<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\BinanceService;

class SwapController extends Controller
{
    public function swap()
    {
        $binance = new BinanceService();

        // Example: Swap 100 USDT → BTC
        $quote = $binance->getQuote("USDT", "ADA", 1);
        dd($quote);
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
}
