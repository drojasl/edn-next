<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('emails.welcome.subject', ['name' => $entrepreneur->name]) }}</title>
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
            background: linear-gradient(135deg, #1e3a5f 0%, #0d2137 50%, #162032 100%);
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
            background: radial-gradient(circle at 50% 50%, rgba(56, 189, 248, 0.08) 0%, transparent 60%);
        }
        .logo {
            font-size: 28px;
            font-weight: 800;
            color: #ffffff;
            letter-spacing: -0.5px;
            position: relative;
        }
        .logo span {
            color: #38bdf8;
        }
        .badge {
            display: inline-block;
            margin-top: 16px;
            background: rgba(56, 189, 248, 0.15);
            border: 1px solid rgba(56, 189, 248, 0.3);
            color: #38bdf8;
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
        .greeting span {
            color: #38bdf8;
        }
        .text {
            font-size: 15px;
            color: #9ca3af;
            line-height: 1.7;
            margin-bottom: 16px;
        }
        .highlight-box {
            background: rgba(56, 189, 248, 0.06);
            border: 1px solid rgba(56, 189, 248, 0.2);
            border-radius: 12px;
            padding: 24px;
            margin: 28px 0;
        }
        .highlight-box p {
            font-size: 14px;
            color: #cbd5e1;
            line-height: 1.6;
        }
        .highlight-box strong {
            color: #38bdf8;
            display: block;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            margin-bottom: 8px;
        }
        .cta-button {
            display: block;
            width: fit-content;
            margin: 32px auto 0;
            background: linear-gradient(135deg, #0ea5e9, #38bdf8);
            color: #0f172a;
            font-size: 15px;
            font-weight: 700;
            padding: 16px 40px;
            border-radius: 10px;
            text-decoration: none;
            letter-spacing: 0.3px;
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
            color: #38bdf8;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">EDN <span>{{ __('emails.Cursos') }}</span></div>
            <div class="badge">{{ __('emails.welcome.badge') }}</div>
        </div>

        <div class="body">
            <h1 class="greeting">
                {{ __('emails.welcome.greeting', ['name' => $entrepreneur->name]) }}
            </h1>

            <p class="text">
                {{ __('emails.welcome.intro') }}
            </p>

            <div class="highlight-box">
                <strong>{{ __('emails.welcome.info_title') }}</strong>
                <p>📧 {{ __('emails.welcome.email_label') }}: <strong style="color:#e8e8f0; font-weight:600;">{{ $entrepreneur->email }}</strong></p>
                @if($entrepreneur->codigo_amway)
                <p style="margin-top:8px;">🔑 {{ __('emails.welcome.code_label') }}: <strong style="color:#e8e8f0; font-weight:600;">{{ $entrepreneur->codigo_amway }}</strong></p>
                @endif
            </div>

            <p class="text">
                {{ __('emails.welcome.features') }}
            </p>

            <a href="{{ config('app.frontend_url', config('app.url')) }}/admin" class="cta-button">
                {{ __('emails.welcome.cta') }}
            </a>
        </div>

        <div class="divider"></div>

        <div class="footer">
            <p>
                {{ __('emails.welcome.footer') }}
                <a href="mailto:{{ config('mail.from.address') }}">{{ config('mail.from.address') }}</a>
            </p>
            <p style="margin-top:12px;">© {{ date('Y') }} EDN {{ __('emails.Cursos') }}. {{ __('emails.welcome.rights') }}</p>
        </div>
    </div>
</body>
</html>
