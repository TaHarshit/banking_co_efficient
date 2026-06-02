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
                            <h2
                                style="margin: 0; font-size: 20px; font-weight: 700; color: #1a365d; letter-spacing: -0.5px;">
                                NegoMaster
                            </h2>
                            <p
                                style="margin: 5px 0 0 0; font-size: 13px; color: #4a5568; font-weight: 500; text-transform: uppercase; letter-spacing: 1px;">
                                by Co-Efficient³
                            </p>
                        </td>
                    </tr>

                    <!-- Main Body -->
                    <tr>
                        <td style="padding: 40px 40px 30px 40px;">
                            <h1
                                style="margin: 0 0 20px 0; font-size: 22px; font-weight: 700; color: #1a365d; line-height: 1.3;">
                                Welcome to the Program, {{ $user->name }}!
                            </h1>

                            <p style="margin: 0 0 16px 0; font-size: 16px; line-height: 1.6; color: #4a5568;">
                                Hello {{ $user->name }},
                            </p>

                            <p style="margin: 0 0 20px 0; font-size: 16px; line-height: 1.6; color: #4a5568;">
                                We are thrilled to welcome you to <strong>NegoMaster</strong>. Your account has been
                                registered successfully, and you are ready to begin your journey in mastering commercial
                                negotiation for banking professionals.
                            </p>

                            <!-- Key benefits / Call-to-action details -->
                            <div
                                style="background-color: #f7fafc; border-left: 4px solid #3756a2; padding: 20px; margin: 25px 0; border-radius: 0 6px 6px 0;">
                                <h3 style="margin: 0 0 8px 0; font-size: 15px; color: #2d3748; font-weight: 700;">
                                    Getting Started is Easy:
                                </h3>
                                <ul
                                    style="margin: 0; padding-left: 20px; font-size: 14px; color: #4a5568; line-height: 1.6;">
                                    <li style="margin-bottom: 6px;">Log in to the mobile application using your email
                                        address: <strong>{{ $user->email }}</strong></li>
                                    <li style="margin-bottom: 6px;">Complete your personalized profile to tailor the
                                        experience</li>
                                    <li>Explore the case studies and prepare your negotiation strategies</li>
                                </ul>
                            </div>

                            <p style="margin: 0 0 30px 0; font-size: 16px; line-height: 1.6; color: #4a5568;">
                                Our AI-driven coaching features are tailored to match your specific professional style,
                                helping you build confidence and achieve exceptional real-world negotiation outcomes.
                            </p>

                            <!-- Action button -->

                            <p
                                style="margin: 30px 0 0 0; font-size: 14px; line-height: 1.6; color: #718096; border-top: 1px solid #edf2f7; padding-top: 20px;">
                                If you did not register for this account, please ignore this email or contact support.
                            </p>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td align="center"
                            style="padding: 20px 40px 30px 40px; background-color: #edf2f7; border-top: 1px solid #e2e8f0; font-size: 12px; color: #718096; line-height: 1.5;">
                            <p style="margin: 0 0 8px 0; font-weight: 600; color: #4a5568;">
                                Co-Efficient³ Training & Consulting
                            </p>
                            <p style="margin: 0 0 12px 0;">
                                Geneva, Switzerland &bull; support@co-efficient.ch
                            </p>
                            <p style="margin: 0;">
                                &copy; {{ date('Y') }} Banking Co-Efficient. All rights reserved.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>

</html>
