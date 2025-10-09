<?php

namespace Database\Factories;

use App\Models\Usuario;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Usuario>
 */
class UsuarioFactory extends Factory
{
    protected $model = Usuario::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'primeiro_nome' => fake()->firstName(),
            'segundo_nome' => fake()->lastName(),
            'apelido' => fake()->userName(),
            'email' => fake()->unique()->safeEmail(),
            'senha' => Hash::make('password'), // password
            'telefone' => fake()->phoneNumber(),
            'numero_documento' => fake()->unique()->numerify('###########'),
            'data_nascimento' => fake()->date('Y-m-d', '2000-01-01'),
            'tipo_usuario' => 'usuario',
            'aceite_comunicacoes_email' => true,
            'aceite_comunicacoes_sms' => false,
            'aceite_comunicacoes_whatsapp' => false,
            'ativo' => true,
        ];
    }



    /**
     * Indicate that the user should be an administrator.
     */
    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'tipo_usuario' => 'administrador',
        ]);
    }

    /**
     * Indicate that the user should be a regular user.
     */
    public function cliente(): static
    {
        return $this->state(fn (array $attributes) => [
            'tipo_usuario' => 'usuario',
        ]);
    }

    /**
     * Indicate that the user should be inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'ativo' => false,
        ]);
    }
}