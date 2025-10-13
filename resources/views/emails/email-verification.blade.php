<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmação de Email - Pharmedice</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333333;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            padding: 0;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #ffffff;
            padding: 40px 30px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: 600;
        }
        .header .icon {
            font-size: 48px;
            margin-bottom: 10px;
        }
        .content {
            padding: 40px 30px;
        }
        .content h2 {
            color: #667eea;
            font-size: 24px;
            margin-top: 0;
            margin-bottom: 20px;
        }
        .content p {
            margin-bottom: 15px;
            font-size: 16px;
        }
        .greeting {
            font-weight: 600;
            color: #667eea;
            font-size: 18px;
        }
        .button-container {
            text-align: center;
            margin: 35px 0;
        }
        .button {
            display: inline-block;
            padding: 15px 40px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #ffffff !important;
            text-decoration: none;
            border-radius: 50px;
            font-weight: 600;
            font-size: 16px;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
            transition: transform 0.2s;
        }
        .button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.5);
        }
        .info-box {
            background-color: #f8f9ff;
            border-left: 4px solid #667eea;
            padding: 15px 20px;
            margin: 25px 0;
            border-radius: 4px;
        }
        .info-box p {
            margin: 5px 0;
            font-size: 14px;
        }
        .warning-box {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px 20px;
            margin: 25px 0;
            border-radius: 4px;
        }
        .warning-box p {
            margin: 5px 0;
            font-size: 14px;
            color: #856404;
        }
        .welcome-box {
            background-color: #d4edda;
            border-left: 4px solid #28a745;
            padding: 15px 20px;
            margin: 25px 0;
            border-radius: 4px;
        }
        .welcome-box p {
            margin: 5px 0;
            font-size: 14px;
            color: #155724;
        }
        .alternative-link {
            margin-top: 30px;
            padding: 20px;
            background-color: #f8f9fa;
            border-radius: 8px;
            word-break: break-all;
        }
        .alternative-link p {
            font-size: 12px;
            color: #666;
            margin-bottom: 10px;
        }
        .alternative-link a {
            color: #667eea;
            font-size: 12px;
        }
        .footer {
            background-color: #f8f9fa;
            padding: 30px;
            text-align: center;
            border-top: 1px solid #e9ecef;
        }
        .footer p {
            margin: 5px 0;
            font-size: 13px;
            color: #6c757d;
        }
        .footer a {
            color: #667eea;
            text-decoration: none;
        }
        .divider {
            height: 1px;
            background-color: #e9ecef;
            margin: 30px 0;
        }
        .benefits {
            margin: 25px 0;
        }
        .benefits ul {
            list-style: none;
            padding: 0;
        }
        .benefits li {
            padding: 8px 0;
            font-size: 15px;
        }
        .benefits li:before {
            content: "✓ ";
            color: #28a745;
            font-weight: bold;
            margin-right: 8px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="icon">✉️</div>
            <h1>Confirme seu Email</h1>
        </div>
        
        <div class="content">
            <p class="greeting">Olá, {{ $userName }}!</p>
            
            <div class="welcome-box">
                <p><strong>🎉 Bem-vindo(a) à Pharmedice!</strong></p>
                <p>Obrigado por se cadastrar em nossa plataforma. Estamos muito felizes em tê-lo(a) conosco!</p>
            </div>
            
            <p>Para começar a usar todos os recursos da plataforma, precisamos confirmar seu endereço de e-mail. Clique no botão abaixo para verificar sua conta:</p>
            
            <div class="button-container">
                <a href="{{ $verificationUrl }}" class="button">Confirmar Email</a>
            </div>
            
            <div class="info-box">
                <p><strong>⏱️ Importante:</strong></p>
                <p>Este link de verificação expira em <strong>{{ $expirationTime }}</strong>.</p>
            </div>
            
            <div class="benefits">
                <p><strong>Após verificar seu email, você poderá:</strong></p>
                <ul>
                    <li>Acessar sua área do cliente</li>
                    <li>Visualizar seus laudos médicos</li>
                    <li>Gerenciar suas informações pessoais</li>
                    <li>Receber notificações importantes</li>
                </ul>
            </div>
            
            <div class="warning-box">
                <p><strong>⚠️ Atenção:</strong></p>
                <p>Se você não criou uma conta na Pharmedice, ignore este e-mail. Nenhuma ação adicional é necessária.</p>
            </div>
            
            <div class="divider"></div>
            
            <p><strong>Por questões de segurança:</strong></p>
            <ul>
                <li>Nunca compartilhe este link com outras pessoas</li>
                <li>A Pharmedice nunca solicitará sua senha por e-mail</li>
                <li>Em caso de dúvida, entre em contato com nosso suporte</li>
            </ul>
            
            <div class="alternative-link">
                <p><strong>Problema com o botão acima?</strong></p>
                <p>Copie e cole este link no seu navegador:</p>
                <a href="{{ $verificationUrl }}">{{ $verificationUrl }}</a>
            </div>
        </div>
        
        <div class="footer">
            <p><strong>Pharmedice - Área do Cliente</strong></p>
            <p>Este é um e-mail automático, por favor não responda.</p>
            <p>Se precisar de ajuda, entre em contato com nosso suporte.</p>
            <p style="margin-top: 15px; font-size: 11px; color: #999;">
                © {{ date('Y') }} Pharmedice. Todos os direitos reservados.
            </p>
        </div>
    </div>
</body>
</html>
