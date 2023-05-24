<?php

namespace App;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Nahid\JsonQ\Jsonq; // composer require nahid/jsonq


class Binance {

    protected static string $apiKey = "...";
    protected static string $apiSecret = "...";
    public static string $apiUrl = 'https://testnet.binancefuture.com'; // https://fapi.binance.com

    
public static function indices() {
    $url = Binance::$apiUrl . "/fapi/v1/premiumIndex";
    $response = Http::get($url);
    $json = $response->body();
    return collect( json_decode($json) )->map(function ($el) { return $el->symbol; }); 
}

public static function indexPrice($symbol) {
    $url = Binance::$apiUrl . "/fapi/v1/premiumIndex";
    $response = Http::get($url);
    $json = $response->body();
    return collect( json_decode($json) )->keyBy('symbol')->get($symbol)->indexPrice; 
}

public static function symbol($pair) {
    $url = Binance::$apiUrl . "/fapi/v1/exchangeInfo";
    $response = Http::get($url);
    $json = $response->body();
    $q = new Jsonq($json);
    return $q->from('symbols')->where('pair','=',$pair)->first()->get();
}

public static function priceFilter($pair) {
    $json = Binance::symbol($pair);
    $q = new Jsonq($json);
    return $q->from('filters')->where('filterType','=','PRICE_FILTER')->first()->get();
}

public static function klines($pair) {
    $D = 2;

    $url = Binance::$apiUrl . "/fapi/v1/continuousKlines"; // not /fapi/v1/klines
    $request = [ 
        'pair' => $pair,
        'interval' => '5m',
        'contractType' => 'PERPETUAL'
    ];
    $response = Http::get($url, $request);
    $json = $response->body();
    $data = collect( json_decode($json) )->reverse()->skip(1)->take(20)->map(function ($el) { return $el[4]; })->reverse(); // 5th element is CLOSE

    $ML = $data->avg();

    $dev = stats_standard_deviation($data->toArray()); // requires: (PECL stats >= 1.0.0); add "extension=stats.so" to php.ini

    $TL = $ML + $D*$dev;
    $BL = $ML - $D*$dev;

    return [ 'ML' => $ML, 'TL' => $TL, 'BL' => $BL ];
}


public static function newOrder($symbol, bool $buy, $quantity) {
    $url = Binance::$apiUrl . "/fapi/v1/order";
    $klines = Binance::klines($symbol);
    if ($buy) {
        $side = 'BUY';
        $price = $klines["BL"];
    } else {
        $side = 'SELL';
        $price = $klines["TL"];
    }
    $priceRounded = round($price,1); // ! precision ?    

    $timestamp = time()*1000; 
    $request = [
        "timestamp" => $timestamp,
        "symbol" => $symbol,
        "side" => $side,
        "type" => "LIMIT",
        "timeInForce" => "GTC", // ?
        "quantity" => $quantity,
        "price" => $priceRounded
    ];
    $headers = [
        'Content-Type' => 'application/json; charset=utf-8', // 'application/x-www-form-urlencoded',
        'X-MBX-APIKEY' => Binance::$apiKey
    ];
    $query = http_build_query($request);
    $signature = hash_hmac("sha256", $query, Binance::$apiSecret);
    $fullUrl = "$url?$query&signature=$signature";

    $response = Http::withHeaders($headers)->send("POST", $fullUrl)->json(); // ::post() cannot send empty body! it sends "[]" in body

    // todo: handle error messages ("msg") such as "Limit price can't be lower than 14871.47" etc.
    if ($response!=null && isset($response["code"]) && $response["code"]!=0) {
        //dd($response);
        return $response["code"];
    }    

    return $response["orderId"];
}

public static function positionRisk($symbol) {
    // https://binance-docs.github.io/apidocs/futures/en/#position-information-v2-user_data
    $url = Binance::$apiUrl . "/fapi/v2/positionRisk";

    $headers = [
        'X-MBX-APIKEY' => Binance::$apiKey,
        'Content-Type' => 'application/x-www-form-urlencoded'
    ];
    $timestamp = time()*1000; //get current timestamp in milliseconds
    $request = [
        "symbol" => $symbol,
        "timestamp" => $timestamp
    ];
    $query = http_build_query($request);
    $signature = hash_hmac("sha256", $query, Binance::$apiSecret);
    $fullUrl = "$url?$query&signature=$signature";

    $response = Http::withHeaders($headers)->get( $fullUrl );
    $json = $response->body();
    return json_decode( $json )[0]; // ERROR: Cannot use object of type stdClass as array
}

public static function cancelOrder($symbol, $orderId) {
    // https://binance-docs.github.io/apidocs/futures/en/#cancel-order-trade
    // DELETE /fapi/v1/order (HMAC SHA256) 
    $url = Binance::$apiUrl . "/fapi/v1/order";
    $headers = [
        'X-MBX-APIKEY' => Binance::$apiKey,
        'Content-Type' => 'application/x-www-form-urlencoded'
    ];
    $timestamp = time()*1000; //get current timestamp in milliseconds
    $request = [
        "symbol" => $symbol,
        "orderId" => $orderId,
        "timestamp" => $timestamp
    ];
    $query = http_build_query($request);
    $signature = hash_hmac("sha256", $query, Binance::$apiSecret);
    $fullUrl = "$url?$query&signature=$signature";

    $response = Http::withHeaders($headers)->send("DELETE", $fullUrl)->json();
    return $response;
}

// ---- loggin - not about Binance:

public static function botlog($botId, $str) {
    $time = Carbon::now()->toDateTimeString();
    Storage::disk('public')->prepend("$botId.txt", "$time\t$str");
}

} // end of class Binance
?>
