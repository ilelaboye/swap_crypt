<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SwapController;

Route::get('get-price', [SwapController::class, 'getPrice']);
Route::get('/swap',  [SwapController::class, 'swap']);

Route::get('get-bybit-price', [SwapController::class, 'getBybitPrice']);
Route::get('/bybit-ada-swap',  [SwapController::class, 'bybitADASwap']);
Route::get('/get-bybit-quote',  [SwapController::class, 'getBybitQuote']);
Route::get('/confirm-bybit-convert-status',  [SwapController::class, 'convertBybitConvertStatus']);
// Route::get('/bybit-swap',  [SwapController::class, 'test']);
