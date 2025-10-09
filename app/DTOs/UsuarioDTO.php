<?php

namespace App\DTOs;

class UsuarioDTO
{
    public function __construct(
        public readonly string $primeiro_nome,
        public readonly string $segundo_nome,
        public readonly string $apelido,
        public readonly string $email,
        public readonly ?string $senha,
        public readonly string $telefone,
        public readonly string $numero_documento,
        public readonly string $data_nascimento,
        public readonly string $tipo_usuario = 'usuario',
        public readonly bool $aceite_comunicacoes_email = false,
        public readonly bool $aceite_comunicacoes_sms = false,
        public readonly bool $aceite_comunicacoes_whatsapp = false,
        public readonly bool $ativo = true
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            primeiro_nome: $data['primeiro_nome'],
            segundo_nome: $data['segundo_nome'],
            apelido: $data['apelido'],
            email: $data['email'],
            senha: $data['senha'] ?? null,
            telefone: $data['telefone'],
            numero_documento: $data['numero_documento'],
            data_nascimento: $data['data_nascimento'],
            tipo_usuario: $data['tipo_usuario'] ?? 'usuario',
            aceite_comunicacoes_email: $data['aceite_comunicacoes_email'] ?? false,
            aceite_comunicacoes_sms: $data['aceite_comunicacoes_sms'] ?? false,
            aceite_comunicacoes_whatsapp: $data['aceite_comunicacoes_whatsapp'] ?? false,
            ativo: $data['ativo'] ?? true
        );
    }

    public function toArray(): array
    {
        $data = [
            'primeiro_nome' => $this->primeiro_nome,
            'segundo_nome' => $this->segundo_nome,
            'apelido' => $this->apelido,
            'email' => $this->email,
            'telefone' => $this->telefone,
            'numero_documento' => $this->numero_documento,
            'data_nascimento' => $this->data_nascimento,
            'tipo_usuario' => $this->tipo_usuario,
            'aceite_comunicacoes_email' => $this->aceite_comunicacoes_email,
            'aceite_comunicacoes_sms' => $this->aceite_comunicacoes_sms,
            'aceite_comunicacoes_whatsapp' => $this->aceite_comunicacoes_whatsapp,
            'ativo' => $this->ativo,
        ];

        if ($this->senha !== null) {
            $data['senha'] = $this->senha;
        }

        return $data;
    }

    public function toArrayWithoutPassword(): array
    {
        return [
            'primeiro_nome' => $this->primeiro_nome,
            'segundo_nome' => $this->segundo_nome,
            'apelido' => $this->apelido,
            'email' => $this->email,
            'telefone' => $this->telefone,
            'numero_documento' => $this->numero_documento,
            'data_nascimento' => $this->data_nascimento,
            'tipo_usuario' => $this->tipo_usuario,
            'aceite_comunicacoes_email' => $this->aceite_comunicacoes_email,
            'aceite_comunicacoes_sms' => $this->aceite_comunicacoes_sms,
            'aceite_comunicacoes_whatsapp' => $this->aceite_comunicacoes_whatsapp,
            'ativo' => $this->ativo,
        ];
    }
}