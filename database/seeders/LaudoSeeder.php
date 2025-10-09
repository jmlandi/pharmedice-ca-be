<?php

namespace Database\Seeders;

use App\Models\Laudo;
use App\Models\Usuario;
use Illuminate\Database\Seeder;

class LaudoSeeder extends Seeder
{
    public function run(): void
    {
        $admin = Usuario::where('email', 'admin@pharmedice.com')->first();
        $cliente = Usuario::where('email', 'joao@exemplo.com')->first();

        if ($admin && $cliente) {
            // Laudos criados pelo administrador
            Laudo::create([
                'usuario_id' => $admin->id, // Admin criou este laudo
                'titulo' => 'Laudo de Hemograma Completo',
                'descricao' => 'Resultado de exame de sangue completo',
                'url_arquivo' => 'laudos/2024/10/exemplo_hemograma.pdf',
                'ativo' => true,
            ]);

            Laudo::create([
                'usuario_id' => $admin->id, // Admin criou este laudo
                'titulo' => 'Exame de Urina Tipo I',
                'descricao' => 'Análise completa de urina',
                'url_arquivo' => 'laudos/2024/10/exemplo_urina.pdf',
                'ativo' => true,
            ]);

            // Laudo criado pelo cliente (se tivesse permissão)
            Laudo::create([
                'usuario_id' => $cliente->id, // Cliente criou este laudo
                'titulo' => 'Relatório Médico Particular',
                'descricao' => 'Documento enviado pelo próprio paciente',
                'url_arquivo' => 'laudos/2024/10/exemplo_relatorio.pdf',
                'ativo' => true,
            ]);

            $this->command->info('Laudos de exemplo criados com sucesso!');
            $this->command->info('- 2 laudos criados pelo administrador');
            $this->command->info('- 1 laudo criado pelo cliente');
        }
    }
}