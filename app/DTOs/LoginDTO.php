<?php

namespace App\DTOs;

class LoginDTO
{
    public function __construct(
        public readonly string $email,
        public readonly string $senha
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            email: $data['email'],
            senha: $data['senha']
        );
    }

    public function toArray(): array
    {
        return [
            'email' => $this->email,
            'senha' => $this->senha,
        ];
    }
}