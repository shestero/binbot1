<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\BotController;
use App\Binance;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::get('/indexPrice/{pair}', function ($pair) {
    return Binance::indexPrice($pair);
});

Route::get('/symbol/{pair}', function($pair) {
    $info = Binance::symbol($pair);
    return response( $info )->header('Content-Type', 'application/json');
});

Route::get('/priceFilter/{pair}', function($pair) {
    $info = Binance::priceFilter($pair);
    return response( $info )->header('Content-Type', 'application/json');
});

Route::middleware('throttle:600,1')->get('/bot/{botId}', function($botId) {
    $content = Storage::disk('public')->get("$botId.txt");
    $response = response($content)->header('Content-Type', 'text/plain');
    // disable caching:
    $response->header('Pragma', 'no-cache');
    $response->header('Expires', 'Fri, 01 Jan 1990 00:00:00 GMT');
    $response->header('Cache-Control', 'no-cache, must-revalidate, no-store, max-age=0, private');
    return $response;
});

// ---

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});
