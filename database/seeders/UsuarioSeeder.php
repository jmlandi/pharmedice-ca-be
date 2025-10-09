<?php

namespace Database\Seeders;

use App\Models\Usuario;
use App\Models\Permissao;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsuarioSeeder extends Seeder
{
    public function run(): void
    {
        // Criar usuário administrador padrão
        $admin = Usuario::create([
            'primeiro_nome' => 'Admin',
            'segundo_nome' => 'Sistema',
            'apelido' => 'Administrador',
            'email' => 'admin@pharmedice.com',
            'senha' => 'admin123',
            'telefone' => '(11) 99999-9999',
            'numero_documento' => '000.000.000-00',
            'data_nascimento' => '1990-01-01',
            'tipo_usuario' => 'administrador',
            'aceite_comunicacoes_email' => true,
            'aceite_comunicacoes_sms' => false,
            'aceite_comunicacoes_whatsapp' => false,
            'ativo' => true,
        ]);

        // Criar usuário cliente de exemplo
        $cliente = Usuario::create([
            'primeiro_nome' => 'João',
            'segundo_nome' => 'Silva',
            'apelido' => 'João',
            'email' => 'joao@exemplo.com',
            'senha' => '123456',
            'telefone' => '(11) 88888-8888',
            'numero_documento' => '111.111.111-11',
            'data_nascimento' => '1985-05-15',
            'tipo_usuario' => 'usuario',
            'aceite_comunicacoes_email' => true,
            'aceite_comunicacoes_sms' => true,
            'aceite_comunicacoes_whatsapp' => true,
            'ativo' => true,
        ]);

        $this->command->info('Usuários criados:');
        $this->command->info("Admin: {$admin->email} / admin123");
        $this->command->info("Cliente: {$cliente->email} / 123456");
    }
}