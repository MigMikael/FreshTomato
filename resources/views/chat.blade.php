<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js"></script>
    <title>Chatting</title>
</head>
<body>
    <nav class="navbar navbar-expand-sm bg-dark navbar-dark fixed-bottom">
        <form class="form-inline" action="{{ url('bot/ask') }}" method="POST" style="width: 100%">
            {{ csrf_field() }}
            <div class="col-md-10 col-sm-10" style="padding: 0">
                <input class="form-control" type="text" style="width: 100%;margin: 0" name="question" placeholder="Question">
            </div>
            <div class="col-md-2 col-sm-2" style="padding: 0">
                <button class="btn btn-success" style="width: 100%;margin: 0" type="submit">Ask!</button>
            </div>
        </form>
    </nav>
    <div class="container-fluid" style="margin-top: 3%">
        @if(isset($question))
        <div class="card">
            <div class="card-body">
                <h4 class="card-title text-right">{{ $question }}</h4>
                <hr>
                <p class="card-text text-left">{{ $answer }}</p>
                {{--<a href="#" class="card-link">Card link</a>
                <a href="#" class="card-link">Another link</a>--}}
            </div>
        </div>
        @endif
    </div>
</body>
</html>