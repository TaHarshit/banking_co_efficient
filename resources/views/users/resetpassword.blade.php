<!DOCTYPE html>
<html lang="en">
    <head>
        <title>Banking Co-Efficient | Password Reset</title>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
        <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
    </head>
    <body>

        <div class="container">
            <div class="row">
                <div class="col-sm-6 col-sm-offset-3">
                    <div class="jumbotron">
                        <center class="mb-4"><img src="{{url('public/assets/img/logo.png')}}" width="150" height="auto"></center>
                        <center> <h2>Reset Password</h2></center>
                        <form action="{{route('updatepassword')}}" method="POST">
                            @if (isset($message))
                                <div class="alert alert-{{ $icon }}">{{ $message }}</div>
                            @endif
                            @csrf
                            <input type="hidden" name="token" value="{{$token}}" />
                            <div class="form-group">
                                <label for="pwd">Email:</label>
                                <input type="text" class="form-control" name="email" placeholder="Enter email">
                            </div>
                            <div class="form-group">
                                <label for="pwd">Password:</label>
                                <input type="password" class="form-control" name="password" placeholder="Enter password">
                            </div>
                            <div class="form-group">
                                <label for="pwd">Confirm Password:</label>
                                <input type="password" class="form-control" name="password_confirmation" placeholder="Confim password">
                            </div>
                            <button type="submit" class="btn btn-info">Submit</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </body>
</html>
