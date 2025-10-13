<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }} - Pharmedice</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.1);
            max-width: 500px;
            width: 100%;
            padding: 40px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
        }
        
        .container.success::before {
            background: linear-gradient(90deg, #10b981, #059669);
        }
        
        .container.error::before {
            background: linear-gradient(90deg, #ef4444, #dc2626);
        }

        .icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 36px;
            color: white;
        }
        
        .icon.success {
            background: linear-gradient(135deg, #10b981, #059669);
        }
        
        .icon.error {
            background: linear-gradient(135deg, #ef4444, #dc2626);
        }

        .title {
            font-size: 28px;
            font-weight: 700;
            color: #111827;
            margin-bottom: 12px;
            line-height: 1.2;
        }

        .message {
            font-size: 18px;
            font-weight: 500;
            margin-bottom: 16px;
        }
        
        .message.success {
            color: #059669;
        }
        
        .message.error {
            color: #dc2626;
        }

        .description {
            font-size: 16px;
            color: #6b7280;
            margin-bottom: 32px;
            line-height: 1.5;
        }

        .info-box {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 20px;
            margin: 24px 0;
            text-align: left;
        }

        .info-item {
            display: flex;
            align-items: center;
            margin-bottom: 12px;
            font-size: 14px;
            color: #4b5563;
        }

        .info-item:last-child {
            margin-bottom: 0;
        }

        .info-item .icon-small {
            width: 16px;
            height: 16px;
            margin-right: 12px;
            color: #10b981;
        }

        .button {
            display: inline-block;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            text-decoration: none;
            padding: 14px 28px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 16px;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            margin: 8px;
        }

        .button:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
        }

        .button.secondary {
            background: white;
            color: #667eea;
            border: 2px solid #667eea;
        }

        .button.secondary:hover {
            background: #667eea;
            color: white;
        }

        .footer {
            margin-top: 32px;
            padding-top: 24px;
            border-top: 1px solid #e5e7eb;
            font-size: 14px;
            color: #6b7280;
        }

        .footer a {
            color: #667eea;
            text-decoration: none;
        }

        .footer a:hover {
            text-decoration: underline;
        }

        @media (max-width: 480px) {
            .container {
                padding: 30px 20px;
            }
            
            .title {
                font-size: 24px;
            }
            
            .message {
                font-size: 16px;
            }
        }
    </style>
</head>
<body>
    <div class="container {{ $success ? 'success' : 'error' }}">
        <div class="icon {{ $success ? 'success' : 'error' }}">
            @if($success)
                ✓
            @else
                ✕
            @endif
        </div>
        
        <h1 class="title">{{ $title }}</h1>
        <p class="message {{ $success ? 'success' : 'error' }}">{{ $message }}</p>
        <p class="description">{{ $description }}</p>
        
        @if($success && isset($user_email))
            <div class="info-box">
                <div class="info-item">
                    <span class="icon-small">📧</span>
                    <span><strong>Email:</strong> {{ $user_email }}</span>
                </div>
                <div class="info-item">
                    <span class="icon-small">✅</span>
                    <span><strong>Status:</strong> Verificado</span>
                </div>
                @if(isset($verified_at))
                <div class="info-item">
                    <span class="icon-small">🕐</span>
                    <span><strong>Verificado em:</strong> {{ \Carbon\Carbon::parse($verified_at)->format('d/m/Y H:i') }}</span>
                </div>
                @endif
            </div>
        @endif
        
        <div class="actions">
            @if($success)
                <a href="{{ env('FRONTEND_URL', 'http://localhost:3000') }}/login" class="button">
                    Fazer Login
                </a>
            @endif
            
            @if(isset($show_resend) && $show_resend)
                <a href="{{ env('FRONTEND_URL', 'http://localhost:3000') }}/verificar-email" class="button secondary">
                    Reenviar Email
                </a>
            @endif
        </div>
        
        <div class="footer">
            <p>
                <strong>Pharmedice</strong> - Sistema de Gestão de Laudos<br>
                Precisa de ajuda? <a href="mailto:suporte@pharmedice.com">Entre em contato</a>
            </p>
        </div>
    </div>
</body>
</html>