<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to NegoMaster</title>
</head>

<body
    style="margin: 0; padding: 0; background-color: #f5f8fa; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; -webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale; color: #2d3748;">
    <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%"
        style="background-color: #f5f8fa; padding: 40px 0 20px 0;">
        <tr>
            <td align="center">
                <!-- Outer email container -->
                <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="600"
                    style="background-color: #ffffff; border-top: 6px solid #3756a2; border-radius: 8px; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05); overflow: hidden;">
                    <!-- Header -->
                    <tr>
                        <td align="center"
                            style="padding: 30px 40px 20px 40px; background: linear-gradient(135deg, #f8fafc 0%, #edf2f7 100%); border-bottom: 1px solid #e2e8f0;">
                            <!-- Branding -->
                            <img src="{{ url('assets/img/logo.png') }}" alt="NegoMaster Logo"
                                style="max-height: 45px; border: 0; display: block; margin: 0 auto;">
                        </td>
                    </tr>

                    <!-- Main Body -->
                    <tr>
                        <td style="padding: 40px 40px 30px 40px;">
                            <h1
                                style="margin: 0 0 20px 0; font-size: 22px; font-weight: 700; color: #1a365d; line-height: 1.3;">
                                Welcome, {{ $user->name }}!
                            </h1>

                            <p style="margin: 0 0 16px 0; font-size: 16px; line-height: 1.6; color: #4a5568;">
                                Hello {{ $user->name }} {{ $user->surname ?? '' }},
                            </p>

                            <p style="margin: 0 0 20px 0; font-size: 16px; line-height: 1.6; color: #4a5568;">
                                Your account has been successfully created on <strong>NegoMaster</strong>. You can now
                                log in using the credentials below:
                            </p>

                            <!-- Credentials Card -->
                            <div
                                style="background-color: #f7fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 20px; margin: 25px 0;">
                                <table role="presentation" border="0" cellpadding="0" cellspacing="0"
                                    width="100%">
                                    <tr>
                                        <td style="padding-bottom: 8px; font-size: 14px; color: #718096; width: 100px;">
                                            <strong>Email:</strong>
                                        </td>
                                        <td style="padding-bottom: 8px; font-size: 14px; color: #2d3748;">
                                            {{ $user->email }}</td>
                                    </tr>
                                    @if (!empty($password))
                                        <tr>
                                            <td style="font-size: 14px; color: #718096;"><strong>Password:</strong></td>
                                            <td style="font-size: 14px; color: #2d3748;">{{ $password }}</td>
                                        </tr>
                                    @endif
                                </table>
                            </div>

                            <p
                                style="margin: 0 0 20px 0; font-size: 14px; line-height: 1.6; color: #e53e3e; font-weight: 600;">
                                For security reasons, we strongly recommend that you change your password immediately
                                after your first login.
                            </p>

                            <p style="margin: 0 0 20px 0; font-size: 16px; line-height: 1.6; color: #4a5568;">
                                You can set or reset your password directly by clicking the button below:
                            </p>

                            <!-- Action button -->
                            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%"
                                style="margin: 30px 0 20px 0;">
                                <tr>
                                    <td align="center">
                                        <a href="{{ url(route('password.reset', ['token' => $token, 'email' => $user->email])) }}"
                                            style="display: inline-block; background-color: #3756a2; color: #ffffff; font-size: 16px; font-weight: 600; text-decoration: none; padding: 14px 30px; border-radius: 6px; box-shadow: 0 4px 6px rgba(55, 86, 162, 0.15); transition: background-color 0.2s ease;">
                                            Set Your Password
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            <p
                                style="margin: 30px 0 0 0; font-size: 14px; line-height: 1.6; color: #718096; border-top: 1px solid #edf2f7; padding-top: 20px;">
                                If the button above doesn't work, copy and paste the following link into your browser:
                                <br>
                                <span style="word-break: break-all; color: #3756a2;">
                                    {{ url(route('password.reset', ['token' => $token, 'email' => $user->email])) }}
                                </span>
                            </p>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td align="center"
                            style="padding: 20px 40px 30px 40px; background-color: #edf2f7; border-top: 1px solid #e2e8f0; font-size: 12px; color: #718096; line-height: 1.5;">
                            <p style="margin: 0 0 8px 0; font-weight: 600; color: #4a5568;">
                                NegoMaster Training & Consulting
                            </p>
                            <p style="margin: 0 0 12px 0;">
                                Geneva, Switzerland &bull; support@negomaster.ch
                            </p>
                            <p style="margin: 0;">
                                &copy; {{ date('Y') }} NegoMaster. All rights reserved.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>

</html>
