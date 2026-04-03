<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reply from {{ config('app.name') }}</title>
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
            font-size: 24px;
            font-weight: 600;
        }

        .content {
            padding: 40px 30px;
        }

        .content h2 {
            color: #4154f1;
            margin-top: 0;
            font-size: 22px;
            font-weight: 600;
        }

        .content p {
            margin: 15px 0;
            color: #555;
        }

        .original-message {
            background: #f8f9fa;
            border-left: 4px solid #6c757d;
            padding: 15px 20px;
            margin: 20px 0;
            border-radius: 4px;
        }

        .original-message p {
            margin: 0;
            color: #666;
            font-style: italic;
        }

        .reply-box {
            background: #e8f5e9;
            border-left: 4px solid #28a745;
            padding: 20px;
            margin: 25px 0;
            border-radius: 4px;
        }

        .reply-box p {
            margin: 0;
            color: #333;
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
            margin: 25px 0;
        }
    </style>
</head>

<body>
    <div class="email-wrapper">
        <div class="header">
            <div class="logo-container">
                <img src="{{ asset('public/assets/img/logo.png') }}" alt="{{ config('app.name') }} Logo">
            </div>
            <h1>Response to Your Inquiry</h1>
        </div>

        <div class="content">
            <p>Hello {{ $data['name'] }},</p>

            <p>Thank you for contacting us. We have reviewed your message and would like to respond:</p>

            <div class="divider"></div>

            <p><strong>Your Original Message:</strong></p>
            <p><em>Subject: {{ $data['subject'] }}</em></p>
            <div class="original-message">
                <p>{!! nl2br(e($data['original_message'])) !!}</p>
            </div>

            <p><strong>Our Response:</strong></p>
            <div class="reply-box">
                <p>{!! nl2br(e($data['reply'])) !!}</p>
            </div>

            <div class="divider"></div>

            <p>If you have any further questions, please don't hesitate to reach out.</p>

            <p style="margin-top: 30px;">Best regards,<br>
                <strong>The {{ config('app.name') }} Team</strong>
            </p>
        </div>

        <div class="footer">
            <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
            <p>Please do not reply directly to this email.</p>
        </div>
    </div>
</body>

</html>
