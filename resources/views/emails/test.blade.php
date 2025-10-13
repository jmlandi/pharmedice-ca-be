<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teste de Email - Pharmedice</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .container {
            background-color: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo {
            font-size: 24px;
            font-weight: bold;
            color: #2c5aa0;
            margin-bottom: 10px;
        }
        .success-icon {
            font-size: 48px;
            color: #22c55e;
            margin-bottom: 20px;
        }
        .title {
            color: #2c5aa0;
            font-size: 20px;
            margin-bottom: 20px;
        }
        .content {
            margin-bottom: 20px;
        }
        .info-box {
            background-color: #f0f9ff;
            border-left: 4px solid #2c5aa0;
            padding: 15px;
            margin: 20px 0;
        }
        .footer {
            text-align: center;
            font-size: 14px;
            color: #666;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">Pharmedice</div>
            <div class="success-icon">✅</div>
        </div>
        
        <h1 class="title">Email de Teste Enviado com Sucesso!</h1>
        
        <div class="content">
            <p>Parabéns! Se você está lendo este email, significa que sua configuração de email está funcionando perfeitamente.</p>
            
            <div class="info-box">
                <strong>Informações do Teste:</strong><br>
                📧 <strong>Remetente:</strong> nao-responda@marcoslandi.com<br>
                🚀 <strong>Provedor:</strong> Resend<br>
                📅 <strong>Data:</strong> {{ date('d/m/Y H:i:s') }}<br>
                🔧 <strong>Sistema:</strong> Pharmedice Customer Area
            </div>
            
            <p>Agora você pode:</p>
            <ul>
                <li>✅ Enviar emails de verificação para novos usuários</li>
                <li>✅ Enviar notificações importantes</li>
                <li>✅ Processar recuperação de senhas</li>
                <li>✅ Comunicar-se com seus usuários</li>
            </ul>
        </div>
        
        <div class="footer">
            <p>Este é um email de teste automatizado do sistema Pharmedice.<br>
            Enviado via Resend de nao-responda@marcoslandi.com</p>
        </div>
    </div>
</body>
</html>