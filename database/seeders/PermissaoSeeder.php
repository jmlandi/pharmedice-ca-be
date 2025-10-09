<?php

namespace Database\Seeders;

use App\Models\Permissao;
use Illuminate\Database\Seeder;

class PermissaoSeeder extends Seeder
{
    public function run(): void
    {
        $permissoes = [
            [
                'nome' => 'gerenciar_usuarios',
                'descricao' => 'Criar, editar e remover usuários',
                'permissao_admin' => true,
                'ativo' => true,
            ],
            [
                'nome' => 'gerenciar_laudos',
                'descricao' => 'Criar, editar e remover laudos de qualquer usuário',
                'permissao_admin' => true,
                'ativo' => true,
            ],
            [
                'nome' => 'visualizar_laudos_proprios',
                'descricao' => 'Visualizar e baixar os próprios laudos',
                'permissao_admin' => false,
                'ativo' => true,
            ],
            [
                'nome' => 'alterar_dados_proprios',
                'descricao' => 'Alterar os próprios dados da conta',
                'permissao_admin' => false,
                'ativo' => true,
            ],
        ];

        foreach ($permissoes as $permissao) {
            Permissao::create($permissao);
        }

        $this->command->info('Permissões criadas com sucesso!');
    }
}