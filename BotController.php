<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Binance;
use App\Jobs\Loop;
use Illuminate\Support\Str; // for Uuid


class BotController extends Controller
{
    // form submit action:
    public function start(Request $request) {
        $validated = $request->validate( [
            'pairs' => 'required',
            'symbol' => 'required',
            'primary' => 'required|numeric|min:0|not_in:0',
            'quantityRounded' => 'required|numeric|min:0|not_in:0',
            'direction' => 'required|in:BUY,SELL'
        ] );
    
        $botId = Str::uuid()->toString(); //Uuid::generate();
    
        $pair = $validated["pairs"];
        $symbol = $validated["symbol"];
        $primary = $validated["primary"];
        $direction = $validated["direction"];
        // $quantity = $primary/$indexPrice;
        $quantityRounded = $validated["quantityRounded"]; 
    
        $indexPrice = Binance::indexPrice($symbol);
    
        $message = "START:\tpair=$pair, direction=$direction, primary=$primary, indexPrice=$indexPrice, quantityRounded=$quantityRounded";
        Binance::botlog($botId, $message);
    
        $klines = Binance::klines($pair);
        $bl = $klines["BL"];
        $ml = $klines["ML"];
        $tl = $klines["TL"];
    
        $message = "BL=$bl, TL=$tl";
        Binance::botlog($botId, $message);
    
        if ($direction=="BUY") {
            $orderId = Binance::newOrder($symbol, true, $quantityRounded);
    
        } else if ($direction=="SELL") {
            $orderId = Binance::newOrder($symbol, false, $quantityRounded);
        } else {
            die("Wrong direction=$direction");
        }
    
        $message = "created order $orderId";
        Binance::botlog($botId, $message);
    
        Loop::dispatch($botId, $pair, $symbol, (float)$primary, $direction, (float)$ml, $orderId, 1);
    
        return redirect()->route( "bot", compact("botId") );    
    }
}
