<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to {{ config('app.name') }}</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f6f9ff;
            margin: 0;
            padding: 0;
        }

        .email-wrapper {
            max-width: 600px;
            margin: 40px auto;
            background: #ffffff;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        }

        .header {
            background: linear-gradient(135deg, #4154f1 0%, #2c3cdd 100%);
            color: #ffffff;
            padding: 40px 30px;
            text-align: center;
        }

        .logo-container {
            margin-bottom: 20px;
        }

        .logo-container img {
            max-width: 150px;
            height: auto;
        }

        .header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: 600;
        }

        .content {
            padding: 40px 30px;
        }

        .content h2 {
            color: #4154f1;
            margin-top: 0;
            font-size: 24px;
            font-weight: 600;
        }

        .content p {
            margin: 15px 0;
            color: #555;
        }

        .info-box {
            background: #f8f9fa;
            border-left: 4px solid #4154f1;
            padding: 20px;
            margin: 25px 0;
            border-radius: 4px;
        }

        .info-box p {
            margin: 8px 0;
            color: #333;
        }

        .info-box strong {
            color: #4154f1;
            font-weight: 600;
        }

        .button-container {
            text-align: center;
            margin: 30px 0;
        }

        .button {
            display: inline-block;
            padding: 15px 40px;
            background: #4154f1;
            color: #ffffff !important;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            font-size: 16px;
            box-shadow: 0 4px 10px rgba(65, 84, 241, 0.3);
            transition: all 0.3s ease;
        }

        .button:hover {
            background: #2c3cdd;
            box-shadow: 0 6px 15px rgba(65, 84, 241, 0.4);
        }

        .link-box {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 4px;
            margin: 20px 0;
            word-break: break-all;
        }

        .link-box p {
            margin: 5px 0;
            font-size: 14px;
            color: #666;
        }

        .link-box a {
            color: #4154f1;
            text-decoration: none;
        }

        .warning-box {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 20px;
            margin: 25px 0;
            border-radius: 4px;
        }

        .warning-box p {
            margin: 0;
            color: #856404;
        }

        .warning-box strong {
            color: #856404;
        }

        .footer {
            background: #f8f9fa;
            padding: 30px;
            text-align: center;
            border-top: 1px solid #e9ecef;
        }

        .footer p {
            margin: 5px 0;
            font-size: 13px;
            color: #6c757d;
        }

        .divider {
            height: 1px;
            background: #e9ecef;
            margin: 30px 0;
        }
    </style>
</head>

<body>
    <div class="email-wrapper">
        <div class="header">
            <div class="logo-container">
                <img src="{{ asset('public/assets/img/logo.png') }}" alt="{{ config('app.name') }} Logo">
            </div>
            <h1>Welcome to {{ config('app.name') }}</h1>
        </div>

        <div class="content">
            <h2>Your Business Account Has Been Created! 🎉</h2>

            <p>Hello,</p>

            <p>We're excited to inform you that a business account has been created for you on {{ config('app.name') }}.
                You're now part of our growing business community!</p>

            <div class="info-box">
                <p><strong>Business Name:</strong> {{ $data['business_name'] }}</p>
                <p><strong>Email:</strong> {{ $data['business_email'] }}</p>
            </div>

            <p>To get started and access your business dashboard, you need to set up your password by clicking the
                button below:</p>

            <div class="button-container">
                <a href="{{ $data['setup_link'] }}" class="button">Set Up Your Password</a>
            </div>

            <div class="link-box">
                <p><strong>Or copy and paste this link into your browser:</strong></p>
                <p><a href="{{ $data['setup_link'] }}">{{ $data['setup_link'] }}</a></p>
            </div>

            <div class="warning-box">
                <p><strong>⚠️ Important:</strong> This link will expire in 24 hours for security reasons. Please set up
                    your password as soon as possible.</p>
            </div>

            <div class="divider"></div>

            <p><strong>What's Next?</strong></p>
            <p>Once you've set up your password, you'll be able to:</p>
            <ul style="color: #555; line-height: 1.8;">
                <li>Access your business dashboard</li>
                <li>Manage your business profile</li>
                <li>Update your business information</li>
                <li>And much more!</li>
            </ul>

            <p>If you have any questions or need assistance, please don't hesitate to contact our support team.</p>

            <p style="margin-top: 30px;">Best regards,<br>
                <strong>The {{ config('app.name') }} Team</strong>
            </p>
        </div>

        <div class="footer">
            <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
            <p>This is an automated email. Please do not reply to this message.</p>
        </div>
    </div>
</body>

</html>
