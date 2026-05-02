{{-- resources/views/emails/password-reset.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Reset Request</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 0;
            background-color: #f4f7fa;
        }
        .container {
            max-width: 600px;
            margin: 20px auto;
            padding: 0;
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .header {
            background: linear-gradient(135deg, #1e3a8a, #3b82f6);
            color: white;
            padding: 30px 20px;
            text-align: center;
        }
        .content {
            padding: 30px;
        }
        .button {
            display: inline-block;
            padding: 12px 30px;
            background: linear-gradient(135deg, #1e3a8a, #3b82f6);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            margin: 20px 0;
            font-weight: 600;
        }
        .footer {
            background: #f8fafc;
            padding: 20px;
            text-align: center;
            font-size: 12px;
            color: #6c757d;
            border-top: 1px solid #e5e7eb;
        }
        .warning {
            background: #fef3c7;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
            font-size: 14px;
            border-left: 4px solid #f59e0b;
        }
        @media (max-width: 600px) {
            .content {
                padding: 20px;
            }
            .button {
                display: block;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1 style="margin: 0; font-size: 24px;">Password Reset Request</h1>
            <p style="margin: 10px 0 0; opacity: 0.9;">Government Services Portal</p>
        </div>

        <div class="content">
            <p>Dear {{ $user->name }},</p>

            <p>We received a request to reset the password for your account associated with <strong>{{ $user->email }}</strong>.</p>

            <p>To reset your password, click the button below:</p>

            <div style="text-align: center;">
                <a href="{{ $resetUrl }}" class="button">Reset My Password</a>
            </div>

            <div class="warning">
                <strong>⚠️ Security Notice:</strong>
                <ul style="margin: 10px 0 0 20px; padding: 0;">
                    <li>This link will expire in 60 minutes</li>
                    <li>If you didn't request this, please ignore this email</li>
                    <li>Never share this link with anyone</li>
                </ul>
            </div>

            <p>If the button doesn't work, copy and paste this link into your browser:</p>
            <p style="background: #f3f4f6; padding: 10px; border-radius: 6px; word-break: break-all; font-size: 12px;">
                {{ $resetUrl }}
            </p>

            <hr style="margin: 30px 0 20px; border: none; border-top: 1px solid #e5e7eb;">

            <p style="font-size: 14px;">
                Best regards,<br>
                <strong>Government Services Team</strong>
            </p>
        </div>

        <div class="footer">
            <p>This is an automated message, please do not reply to this email.</p>
            <p>&copy; {{ date('Y') }} Government Services Portal. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
