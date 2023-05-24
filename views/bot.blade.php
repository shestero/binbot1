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
                align-items: center;
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
                font-size: 22px;
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

            pre { 
                text-align: left; 
                margin: 10px;
            }

        </style>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.4/jquery.min.js"></script>
    <script type="text/javascript">

    var intervalID;
    intervalID = setInterval(function() {
        $.ajax({
            url: "/api/bot/{{ $botId }}", 
            type: "GET",
            dataType: 'text',
            success: function(response) {
                if (response!=null) {
                    var t = new Date();
                    var dt = t.getFullYear() + '-' +('0' + (t.getMonth()+1)).slice(-2)+ '-' +  ('0' + t.getDate()).slice(-2) + ' ' + 
                        t.getHours()+ ':'+('0' + (t.getMinutes())).slice(-2)+ ':'+('0' + t.getSeconds()).slice(-2);
        
                    if (response.includes("FINISHED")) {
                        $('#log').text( response );
                        clearInterval(intervalID);
                    } else {
                        $('#log').text( dt + "\t<-- Дата-время актуальности\n" + response);
                    }
                }
            }
        });
    }, 1500); // Do this every 1.5 seconds

</script>

</head>
<body>
    <div class="position-ref full-height">

        <div class="content">
            <div class="title m-b-md">
                Работа бота <u>{{ $botId }}</u>
            </div>

            <hr>

            <pre id="log">... Протокол работы должен быть здесь ...</pre>

        </div>
    </div>
</body>
</html>
