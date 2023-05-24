<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

use App\Binance;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () { return view( 'create', [ "pairs" => Binance::indices() ] ); });

Route::get('/pairs', function() {
    $url = 'https://testnet.binancefuture.com/fapi/v1/premiumIndex';
    $response = Http::get($url);
    $json = $response->body();
    $pairs = collect( json_decode($json) )->map(function ($el) { return $el->symbol; });
    return response( json_encode($pairs) )->header('Content-Type', 'application/json');
});

Route::get('/symbol', function() {
    $info = Binance::symbol('BTCUSDT');
    return response( $info )->header('Content-Type', 'application/json');
});

Route::get('/indexPrice', function() {
    dd(Binance::indexPrice('BTCUSDT')); 
});

Route::get('/klines', function() {
    dd(Binance::klines('BTCUSDT')); 
});

Route::name('start')->post('/start', 'BotController@start' );

Route::name('bot')->get('/bot/{botId}', function($botId) {
    return view('bot', compact("botId") ); 
});

Route::get('/bot/{botId}/plain', function($botId) {
    $content = Storage::disk('public')->get("$botId.txt");
    return response($content)->header('Content-Type', 'text/plain');
});

