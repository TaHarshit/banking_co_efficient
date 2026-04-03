<!DOCTYPE html>
<html>
<head>
    <title>Welcome to Banking Co-Efficient</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #4154f1;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 5px 5px 0 0;
        }
        .content {
            padding: 20px;
            border: 1px solid #ddd;
            border-top: none;
            border-radius: 0 0 5px 5px;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: #4154f1;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 20px;
        }
        .credentials {
            background-color: #f4f4f4;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .footer {
            margin-top: 20px;
            font-size: 0.8em;
            color: #777;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Welcome, {{ $user->name }}!</h1>
    </div>
    <div class="content">
        <p>Hello {{ $user->name }} {{ $user->surname }},</p>
        <p>Your account has been created on Banking Co-Efficient. You can now log in using the following credentials:</p>
        
        <div class="credentials">
            <p><strong>Email:</strong> {{ $user->email }}</p>
            <p><strong>Temporary Password:</strong> {{ $password }}</p>
        </div>

        <p>For security reasons, we recommend that you change your password immediately after your first login.</p>
        
        <p>You can also reset your password directly by clicking the button below:</p>
        
        <a href="{{ url(route('password.reset', ['token' => $token, 'email' => $user->email])) }}" class="btn">Set Your Password</a>

        <p>If the button above doesn't work, copy and paste the following link into your browser:</p>
        <p>{{ url(route('password.reset', ['token' => $token, 'email' => $user->email])) }}</p>

        <div class="footer">
            <p>&copy; {{ date('Y') }} Banking Co-Efficient. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
