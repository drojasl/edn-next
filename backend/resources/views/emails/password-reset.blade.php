<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('emails.password_reset.subject') }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background-color: #0f0f13;
            color: #e8e8f0;
            padding: 40px 20px;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: #1a1a24;
            border-radius: 16px;
            overflow: hidden;
            border: 1px solid rgba(255,255,255,0.08);
        }
        .header {
            background: linear-gradient(135deg, #1e2a3f 0%, #111827 50%, #1a1a24 100%);
            padding: 48px 40px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        .header::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle at 50% 50%, rgba(234, 179, 8, 0.07) 0%, transparent 60%);
        }
        .logo {
            font-size: 28px;
            font-weight: 800;
            color: #ffffff;
            letter-spacing: -0.5px;
            position: relative;
        }
        .logo span {
            color: #facc15;
        }
        .icon {
            font-size: 48px;
            margin-bottom: 16px;
            display: block;
        }
        .badge {
            display: inline-block;
            margin-top: 16px;
            background: rgba(234, 179, 8, 0.12);
            border: 1px solid rgba(234, 179, 8, 0.3);
            color: #facc15;
            font-size: 12px;
            font-weight: 600;
            letter-spacing: 2px;
            text-transform: uppercase;
            padding: 6px 16px;
            border-radius: 100px;
            position: relative;
        }
        .body {
            padding: 48px 40px;
        }
        .greeting {
            font-size: 26px;
            font-weight: 700;
            color: #ffffff;
            margin-bottom: 16px;
            line-height: 1.3;
        }
        .text {
            font-size: 15px;
            color: #9ca3af;
            line-height: 1.7;
            margin-bottom: 16px;
        }
        .warning-box {
            background: rgba(234, 179, 8, 0.05);
            border: 1px solid rgba(234, 179, 8, 0.2);
            border-radius: 12px;
            padding: 20px 24px;
            margin: 28px 0;
            display: flex;
            align-items: flex-start;
            gap: 12px;
        }
        .warning-box .icon-sm { font-size: 20px; flex-shrink: 0; margin-top: 2px; }
        .warning-box p {
            font-size: 13px;
            color: #d1a800;
            line-height: 1.6;
        }
        .cta-button {
            display: block;
            width: fit-content;
            margin: 32px auto 0;
            background: linear-gradient(135deg, #d97706, #facc15);
            color: #111827;
            font-size: 15px;
            font-weight: 700;
            padding: 16px 40px;
            border-radius: 100px;
            text-decoration: none;
            letter-spacing: 0.3px;
        }
        .url-fallback {
            margin-top: 24px;
            padding: 16px 20px;
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 8px;
        }
        .url-fallback p {
            font-size: 12px;
            color: #6b7280;
            margin-bottom: 8px;
        }
        .url-fallback code {
            font-size: 11px;
            color: #9ca3af;
            word-break: break-all;
            display: block;
            line-height: 1.5;
        }
        .divider {
            height: 1px;
            background: rgba(255,255,255,0.06);
            margin: 40px 0;
        }
        .footer {
            padding: 0 40px 40px;
            text-align: center;
        }
        .footer p {
            font-size: 12px;
            color: #4b5563;
            line-height: 1.7;
        }
        .footer a {
            color: #facc15;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">EDN <span>{{ __('emails.Cursos') }}</span></div>
            <div class="badge">{{ __('emails.password_reset.badge') }}</div>
        </div>

        <div class="body">
            <h1 class="greeting">{{ __('emails.password_reset.title') }}</h1>

            <p class="text">
                {{ __('emails.password_reset.greeting', ['name' => $recipientName]) }}
            </p>

            <p class="text">
                {{ __('emails.password_reset.instruction') }}
            </p>

            <a href="{{ $resetUrl }}" class="cta-button">
                {{ __('emails.password_reset.cta') }}
            </a>

            <div class="warning-box" style="margin-top:32px;">
                <span class="icon-sm">⚠️</span>
                <p>
                    {{ __('emails.password_reset.warning') }}
                </p>
            </div>

            <div class="url-fallback">
                <p>{{ __('emails.password_reset.fallback') }}</p>
                <code>{{ $resetUrl }}</code>
            </div>
        </div>

        <div class="divider"></div>

        <div class="footer">
            <p>
                {{ __('emails.password_reset.footer') }}
                <a href="mailto:{{ config('mail.from.address') }}">{{ config('mail.from.address') }}</a>
            </p>
            <p style="margin-top:12px;">© {{ date('Y') }} EDN {{ __('emails.Cursos') }}. {{ __('emails.welcome.rights') }}</p>
        </div>
    </div>
</body>
</html>
