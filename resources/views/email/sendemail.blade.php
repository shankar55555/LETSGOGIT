<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Your Password - Bhoomi CRM</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');

        body {
            margin: 0;
            padding: 0;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            line-height: 1.6;
            color: #374151;
            background-color: #f8fafc;
        }

        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 40px 20px;
            text-align: center;
            color: white;
        }

        .logo {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 8px;
            letter-spacing: -0.5px;
        }

        .header-subtitle {
            opacity: 0.9;
            font-size: 16px;
            font-weight: 400;
        }

        .content {
            padding: 50px 40px;
        }

        .greeting {
            font-size: 24px;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 8px;
        }

        .main-message {
            font-size: 18px;
            color: #4b5563;
            margin-bottom: 30px;
            font-weight: 500;
        }

        .description {
            font-size: 16px;
            color: #6b7280;
            line-height: 1.7;
            margin-bottom: 35px;
        }

        .reset-button {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white !important;
            text-decoration: none !important;
            padding: 16px 32px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 16px;
            text-align: center;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }

        .reset-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(102, 126, 234, 0.5);
        }

        .security-note {
            background-color: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 20px;
            margin: 30px 0;
            border-radius: 0 8px 8px 0;
        }

        .security-note h4 {
            margin: 0 0 8px 0;
            color: #92400e;
            font-size: 16px;
            font-weight: 600;
        }

        .security-note p {
            margin: 0;
            color: #92400e;
            font-size: 14px;
            line-height: 1.5;
        }

        .footer {
            background-color: #f9fafb;
            padding: 30px 40px;
            text-align: center;
            border-top: 1px solid #e5e7eb;
        }

        .footer-text {
            color: #6b7280;
            font-size: 14px;
            margin: 0 0 10px 0;
        }

        .company-name {
            color: #374151;
            font-weight: 600;
            font-size: 16px;
            margin: 0;
        }

        .contact-info {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            color: #9ca3af;
            font-size: 12px;
        }

        @media only screen and (max-width: 600px) {
            .email-container {
                margin: 10px;
                border-radius: 8px;
            }

            .content {
                padding: 30px 25px;
            }

            .header {
                padding: 30px 20px;
            }

            .greeting {
                font-size: 22px;
            }

            .main-message {
                font-size: 16px;
            }

            .footer {
                padding: 25px 25px;
            }
        }
    </style>
</head>

<body>
    <div style="background-color: #f8fafc; padding: 20px 0; min-height: 100vh;">
        <div class="email-container">
            <!-- Header Section -->
            <div class="header">
                <div class="logo">BhoomiCRM</div>
                <div class="header-subtitle">Password Reset Request</div>
            </div>

            <!-- Main Content -->
            <div class="content">
                <h1 class="greeting">Hello, {{ $user->name }}!</h1>
                <p class="main-message">We received a request to reset your password.</p>

                <p class="description">
                    Don't worry, it happens to the best of us! We've generated a secure link that will allow you to
                    create a new password for your account. This link is valid for 2 hours and can only be used once.
                </p>

                <div style="text-align: center; margin: 40px 0;">
                    <a href="{{ url('/reset-password-view?token=' . $token . '&email=' . urlencode($user->email)) }}"
                        class="reset-button">
                        Reset My Password
                    </a>
                </div>

                <!-- Security Note -->
                <div class="security-note">
                    <h4>🔒 Security Notice</h4>
                    <p>If you didn't request this password reset, please ignore this email. Your account remains secure
                        and no changes have been made.</p>
                </div>
            </div>

            <!-- Footer -->
            <div class="footer">
                <p class="company-name">BhoomiCRM Team</p>
                <p class="footer-text">Thank you for choosing our platform!</p>

                <div class="contact-info">
                    <p>This is an automated message, please do not reply to this email.</p>
                    <p>© {{ date('Y') }} BhoomiCRM. All rights reserved.</p>
                </div>
            </div>
        </div>
    </div>
</body>

</html>
