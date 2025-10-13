<?php

namespace App\Console\Commands;

use App\Models\Usuario;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class TestUsuarioSemVerificacao extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:usuario-sem-verificacao {email : Email do usuário de teste}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cria um usuário de teste sem email verificado para testar o bloqueio de login';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');
        
        // Verifica se o usuário já existe
        if (Usuario::where('email', $email)->exists()) {
            $this->error("❌ Usuário com email {$email} já existe!");
            return 1;
        }
        
        // Cria usuário de teste sem email verificado
        $usuario = Usuario::create([
            'primeiro_nome' => 'Teste',
            'segundo_nome' => 'Usuario',
            'apelido' => 'testuser',
            'email' => $email,
            'senha' => Hash::make('123456'),
            'telefone' => '(11) 99999-9999',
            'numero_documento' => '12345678901',
            'data_nascimento' => '1990-01-01',
            'tipo_usuario' => 'usuario',
            'aceite_comunicacoes_email' => true,
            'aceite_comunicacoes_sms' => false,
            'aceite_comunicacoes_whatsapp' => false,
            'aceite_termos_uso' => true,
            'aceite_politica_privacidade' => true,
            'ativo' => true,
            // Importante: NÃO definir email_verified_at (deixar null)
        ]);
        
        $this->info("✅ Usuário de teste criado com sucesso!");
        $this->line("📧 Email: {$email}");
        $this->line("🔒 Senha: 123456");
        $this->line("❌ Email não verificado (email_verified_at é null)");
        $this->line("");
        $this->line("Para testar:");
        $this->line("1. Tente fazer login - deve ser bloqueado");
        $this->line("2. Use POST /api/auth/reenviar-verificacao-email-publico com o email");
        $this->line("3. Verifique o email e então faça login");
        
        return 0;
    }
}
