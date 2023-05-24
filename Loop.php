<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use App\Binance;

class Loop implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $botId;
    protected string $pair;
    protected string $symbol;
    protected float $primary;
    protected string $direction;
    protected float $oldML;
    protected string $orderId;
    protected int $loopN;

    public int $max_looptime_sec = 60; 

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($botId, $pair, $symbol, $primary, $direction, $oldML, $orderId, $loopN = 1)
    {
        $this->botId = $botId;
        $this->pair = $pair;
        $this->symbol = $symbol;
        $this->primary = $primary;
        $this->direction = $direction;
        $this->oldML = $oldML;
        $this->orderId = $orderId;
        $this->loopN = $loopN;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $start = microtime(true);
        $looptime = function () use ($start) {
            $time_elapsed_secs = microtime(true) - $start;
            if ($time_elapsed_secs<0) $time_elapsed_secs = 0;
            if ($time_elapsed_secs>25) $time_elapsed_secs = 25;
            
            return $this->max_looptime_sec - $time_elapsed_secs;
        };

        if ($this->loopN % 2 == 1) $loopType = true; else $loopType = false;       

        // определяем направления входа и выхода (true=BYE, false=SELL)
        if ($this->direction=="BUY") {
            $dirIn = $loopType;
    
        } else if ($this->direction=="SELL") {
            $dirIn = !$loopType;
        } else {
            die("Wrong direction=$this->direction");
        }        
        $dirOut = !$dirIn;
                
        $risk = Binance::positionRisk($this->pair);
        $positionAmt = $risk->positionAmt;
        $message = "Loop#$this->loopN:\tloopType=$loopType, dirIn=$dirIn, dirOut=$dirOut, positionAmt=$positionAmt for pair=$this->pair";
        Binance::botlog($this->botId, $message);
        // TODO: эти 2 запроса можно запараллелить?
        $klines = Binance::klines($this->pair);
        $ml = $klines["ML"];
        $message = "Loop#$this->loopN:\tnew ML=$ml for pair=$this->pair";
        Binance::botlog($this->botId, $message);

        if ($positionAmt<=0)
        {
            // позиции нет

            if ($this->oldML==(float)$ml) // ? сопоставлять с учётом ошибки округления или допуском относительной разницы
            {
                Loop::dispatch(
                    $this->botId, $this->pair, $this->symbol, $this->primary, $this->direction, $this->oldML, $this->orderId, $this->loopN
                )->delay(now()->addSeconds($looptime())); 
            } 
            else {
                // отменяем старый оредер
                $response = Binance::cancelOrder($this->symbol, $this->orderId);
                $status = $response["status"];
                $message = "Order with id=$this->orderId : $status";
                Binance::botlog($this->botId, $message);
        
                if ($status=="CANCELED") {
                    // пресчёт цены
                    $indexPrice = Binance::indexPrice($this->symbol);
                    $quantity = $this->primary/$indexPrice;
                    $priceFilter = Binance::priceFilter($this->pair);
                    $minPrice = $priceFilter["minPrice"];
                    $maxPrice = $priceFilter["maxPrice"];
                    $tickSize = $priceFilter["tickSize"];
                    $prec = 10000000;
                    $ticks = floor( $quantity / $tickSize );
                    $quantityRounded = round( $ticks*$tickSize * $prec) / $prec;
                    $message = "Loop#$this->loopN:\tNote new (pair/symbol=$this->pair/$this->symbol, primary=$this->primary): " .
                        "indexPrice=$indexPrice, quantity=$quantity, quantityRounded=$quantityRounded";
                    Binance::botlog($this->botId, $message);
    
                    // ставим новый ордер
                    $orderId = Binance::newOrder($this->symbol, $dirIn, $quantityRounded);
                    $message = "Loop#$this->loopN:\tNew orderId=$orderId (direction=$dirIn)";
                    Binance::botlog($this->botId, $message);
                
                    Loop::dispatch(
                        $this->botId, $this->pair, $this->symbol, $this->primary, $this->direction, (float)$ml, $orderId, $this->loopN
                    )->delay(now()->addSeconds($looptime()));  // continue using new orderId
                }
                // если не удалось удалить ордер цикл заканичвается (процесс останавливается)
            } 
    
        } else {
            // позиция есть

            if ($loopType) 
            {
                // устанавливаем новый ордер 
                $orderId = Binance::newOrder($this->symbol, $dirOut, $positionAmt);
                $message = "Loop#$this->loopN:\tNew orderId=$orderId (direction=$dirOut)";
                Binance::botlog($this->botId, $message);

                Loop::dispatch(
                    $this->botId, $this->pair, $this->symbol, $this->primary, $this->direction, (float)$ml, $orderId, $this->loopN+1
                );
                
            } else {
                $message = "Loop:\tFINISHED!";
                Binance::botlog($this->botId, $message);    
            }
        }
        // TODO: ситуация частичного закрытия ордера не обрабатывается
    }
}

// Loop has been attempted too many times or run too long. The job may have previously timed out.

