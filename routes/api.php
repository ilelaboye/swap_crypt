<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SwapController;

Route::get('get-price', [SwapController::class, 'getPrice']);
Route::get('/swap',  [SwapController::class, 'swap']);
