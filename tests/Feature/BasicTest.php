<?php

namespace Tests\Feature;

use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BasicTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function pode_criar_usuario()
    {
        $usuario = Usuario::create([
            'primeiro_nome' => 'João',
            'segundo_nome' => 'Silva',
            'apelido' => 'joao123',
            'email' => 'joao@test.com',
            'senha' => bcrypt('password'),
            'telefone' => '11999999999',
            'numero_documento' => '12345678901',
            'data_nascimento' => '1990-01-01',
            'tipo_usuario' => 'usuario',
            'aceite_comunicacoes_email' => true,
            'aceite_comunicacoes_sms' => false,
            'aceite_comunicacoes_whatsapp' => false,
            'ativo' => true
        ]);

        $this->assertInstanceOf(Usuario::class, $usuario);
        $this->assertEquals('joao@test.com', $usuario->email);
    }
}