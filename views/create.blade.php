<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Bot start form</title>

        <!-- Fonts -->
        <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@200;600&display=swap" rel="stylesheet">

        <!-- Styles -->
        <style>
            html, body {
                background-color: #fff;
                color: #636b6f;
                font-family: 'Nunito', sans-serif;
                font-weight: 200;
                height: 100vh;
                margin: 0;
            }

            .full-height {
                height: 100vh;
            }

            .flex-center {
                align-items: top;
                display: flex;
                justify-content: center;
            }

            .position-ref {
                position: relative;
            }

            .top-right {
                position: absolute;
                right: 10px;
                top: 18px;
            }

            .content {
                text-align: center;
            }

            .title {
                font-size: 42px;
            }

            .links > a {
                color: #636b6f;
                padding: 0 25px;
                font-size: 13px;
                font-weight: 600;
                letter-spacing: .1rem;
                text-decoration: none;
                text-transform: uppercase;
            }

            .m-b-md {
                margin-bottom: 30px;
            }

            p > label {
                display: inline-block;
                width: 250px;
                text-align: right;
            }
        </style>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.4/jquery.min.js"></script>
    <script type="text/javascript">

    function fillIndexQuanity($full) {
        $('#indexPrice').val("...");
        $('#quantity').val("...");
        $('#quantityRounded').val("...");
        if ($full) {
            $('#symbol').val('...');
            $('#pricePrecision').val('...');
            $('#minPrice').val('...');
            $('#maxPrice').val('...');
            $('#tickSize').val('...');
        }

        var pair = $('#pairs').val();
        if ( pair==null || pair<='' )
            return;

        $.ajax({
            url: "/api/indexPrice/"+pair,
            type: 'get',
            dataType: 'text',
            async: 'true',
            success: function(response) {
                if (response != null) {
                    var indexPrice = response;
                    $('#indexPrice').val(indexPrice);

                    var primary = $('#primary').val();
                    if ( primary==null || primary<='' )
                        return;
                        
                    $('#quantity').val( primary/indexPrice );
                }
            }    
        });

        if ($full) {
            $.ajax({
                url: "/api/symbol/"+pair,
                type: 'get',
                dataType: 'json',
                async: 'true',
                success: function(response) {
                    if (response != null) {
                        $('#symbol').val(response.symbol);
                        $('#pricePrecision').val(response.pricePrecision);
                    } else {
                        $('#symbol').val('???');
                        $('#pricePrecision').val('???');
                    }
                }    
            });
        }

        $.ajax({
            url: "/api/priceFilter/"+pair,
            type: 'get',
            dataType: 'json',
            async: 'true',
            success: function(response) {
                if (response != null) {
                    $('#minPrice').val(response.minPrice);
                    $('#maxPrice').val(response.maxPrice);
                    $('#tickSize').val(response.tickSize);

                    var quantity = $('#quantity').val(); // todo
                    if (quantity!=null) { 
                        var prec = 10000000;
                        var ticks = Math.trunc( quantity / response.tickSize ); 
                        $('#quantityRounded').val( Math.round( ticks*response.tickSize * prec) / prec );
                    }
                    
                } else {
                    $('#minPrice').val('???');
                    $('#maxPrice').val('???');
                    $('#tickSize').val('???');
                    $('#tickSize').val('???');
                }
            }    
        });
    };

    $(document).ready(function(){
        $("#pairs").change(function(){
            fillIndexQuanity(true);
        });
        $("#primary").change(function(){
            fillIndexQuanity(false);
        });
        /*
        $("#primary").keyup(function(){
            fillIndexQuanity(false);
        });
        */
    });

    </script>

    </head>
    <body>
        <div class="flex-center position-ref full-height">

            <div class="content">
                <div class="title m-b-md">
                    Форма старта бота
                </div>

                <!-- use laravelcollective/html ?? -->
                <!-- https://ru.hexlet.io/courses/php-laravel/lessons/forms/theory_unit -->
                <form action="{{ route('start') }}" method="post">
                @csrf
                <div style="float: left; margin: 25px;">
                    <label for="pairs">Торговая пара:</label><br>
                    <select name="pairs" id="pairs" size="16" value="{{ old('pairs') }}">
@foreach ($pairs->sort() as $pair)
<option value="{{ $pair }}">{{ $pair }}</option>
@endforeach
                    </select> 
                </div>
                <div style="float: right; margin: 25px;">
                    <p aling="left">
                        <label for="primary">Первичная инвестиция в USDT: </label>
                        <input name="primary" id="primary" type="text" value="{{ old('primary') }}">
                        <br>
                        <label for="indexPrice">indexPrice: </label>
                        <input name="indexPrice" id="indexPrice" type="text" readonly>
                        <br>
                        <label for="quantity">quantity: </label>
                        <input name="quantity" id="quantity" type="text" readonly>
                        <br>
                        <label for="quantityRounded">quantityRounded: </label>
                        <input name="quantityRounded" id="quantityRounded" type="text" readonly>
                    </p>
                    <p aling="left">
                        <label for="pricePrecision">precision: </label>
                        <input name="pricePrecision" id="pricePrecision" type="text" readonly>
                        <br>
                        <label for="symbol">symbol: </label>
                        <input name="symbol" id="symbol" type="text" readonly>
                        <br>
                        <label for="minPrice">min.price: </label>
                        <input name="minPrice" id="minPrice" type="text" readonly>
                        <br>
                        <label for="maxPrice">max.price: </label>
                        <input name="maxPrice" id="maxPrice" type="text" readonly>
                        <br>
                        <label for="tickSize">tick size: </label>
                        <input name="tickSize" id="tickSize" type="text" readonly>
                    </p>
                    <p>
                        <fieldset>
                            <label for="direction">Направление сделки:</label>
                            <input name="direction" type="radio" id="buy" value="BUY">
                            <label for="buy">BUY</label>
                            <input name="direction" type="radio" id="sell" value="SELL">
                            <label for="sell">SELL</label>
                        </fieldset>
                    </p>
                    <p>
                        <div style="margin: 25px;">
                            <input type="submit" value="Старт!">
                            <input type="reset" style="float: right;">
                        </div>
                    </p>
                </div>
                <div style="float: center; margin: 30px;">
                <div style="position: absolute; bottom: 0; float: center; margin: 25px; width: 500px;">
                    <!-- Вывод ошибок валидации формы -->
@if ($errors->any())    
    <div class="alert alert-danger" style="color: red">
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
                </div>
                </div>
                </form>

            </div>
        </div>
    </body>
</html>
