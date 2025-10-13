<?php

namespace App\Console\Commands;

use App\Models\Usuario;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\URL;

class TestVerificacaoEmail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:verificacao-email {email : Email do usuário}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Gera um link de verificação de email para teste';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');
        
        // Busca o usuário
        $usuario = Usuario::where('email', $email)->first();
        
        if (!$usuario) {
            $this->error("❌ Usuário com email {$email} não encontrado!");
            $this->line("💡 Use: php artisan test:usuario-sem-verificacao {$email}");
            return 1;
        }
        
        // Gera o link de verificação
        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60), // 1 hora de validade
            [
                'id' => $usuario->id,
                'hash' => sha1($usuario->getEmailForVerification()),
            ]
        );
        
        $this->info("🔗 Link de Verificação Gerado:");
        $this->line($verificationUrl);
        $this->line("");
        
        if ($usuario->hasVerifiedEmail()) {
            $this->warn("⚠️  Este usuário já tem email verificado!");
            $this->line("🕐 Verificado em: " . $usuario->email_verified_at->format('d/m/Y H:i:s'));
        } else {
            $this->line("✅ Status: Email não verificado");
        }
        
        $this->line("");
        $this->line("🧪 Para testar:");
        $this->line("1. Copie o link acima");
        $this->line("2. Cole no navegador");
        $this->line("3. Você verá a página de confirmação");
        
        return 0;
    }
}
