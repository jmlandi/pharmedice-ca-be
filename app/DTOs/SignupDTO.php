<?php

namespace App\DTOs;

class SignupDTO
{
    public function __construct(
        public readonly string $primeiro_nome,
        public readonly string $segundo_nome,
        public readonly string $apelido,
        public readonly string $email,
        public readonly string $senha,
        public readonly string $confirmacao_senha,
        public readonly string $telefone,
        public readonly string $numero_documento,
        public readonly string $data_nascimento,
        public readonly string $tipo_usuario = 'usuario',
        public readonly bool $aceite_comunicacoes_email = false,
        public readonly bool $aceite_comunicacoes_sms = false,
        public readonly bool $aceite_comunicacoes_whatsapp = false,
        public readonly bool $aceite_termos_uso = true,
        public readonly bool $aceite_politica_privacidade = true
    ) {}

    /**
     * Cria uma instância do DTO a partir dos dados da requisição
     * 
     * @param array $dados_requisicao Dados vindos da requisição HTTP
     * @return self
     */
    public static function fromRequest(array $dados_requisicao): self
    {
        return new self(
            primeiro_nome: $dados_requisicao['primeiro_nome'],
            segundo_nome: $dados_requisicao['segundo_nome'],
            apelido: $dados_requisicao['apelido'],
            email: strtolower(trim($dados_requisicao['email'])), // Normaliza o email
            senha: $dados_requisicao['senha'],
            confirmacao_senha: $dados_requisicao['confirmacao_senha'],
            telefone: $dados_requisicao['telefone'],
            numero_documento: preg_replace('/\D/', '', $dados_requisicao['numero_documento']), // Remove formatação
            data_nascimento: $dados_requisicao['data_nascimento'],
            tipo_usuario: $dados_requisicao['tipo_usuario'] ?? 'usuario',
            aceite_comunicacoes_email: $dados_requisicao['aceite_comunicacoes_email'] ?? false,
            aceite_comunicacoes_sms: $dados_requisicao['aceite_comunicacoes_sms'] ?? false,
            aceite_comunicacoes_whatsapp: $dados_requisicao['aceite_comunicacoes_whatsapp'] ?? false,
            aceite_termos_uso: $dados_requisicao['aceite_termos_uso'] ?? true,
            aceite_politica_privacidade: $dados_requisicao['aceite_politica_privacidade'] ?? true
        );
    }

    /**
     * Converte o DTO para array para criação no banco de dados
     * 
     * @return array
     */
    public function toArray(): array
    {
        return [
            'primeiro_nome' => $this->primeiro_nome,
            'segundo_nome' => $this->segundo_nome,
            'apelido' => $this->apelido,
            'email' => $this->email,
            'senha' => $this->senha, // Será hasheada no mutator do model
            'telefone' => $this->telefone,
            'numero_documento' => $this->numero_documento,
            'data_nascimento' => $this->data_nascimento,
            'tipo_usuario' => $this->tipo_usuario,
            'aceite_comunicacoes_email' => $this->aceite_comunicacoes_email,
            'aceite_comunicacoes_sms' => $this->aceite_comunicacoes_sms,
            'aceite_comunicacoes_whatsapp' => $this->aceite_comunicacoes_whatsapp,
            'ativo' => true, // Novos usuários começam ativos
        ];
    }

    /**
     * Valida se as senhas coincidem
     * 
     * @return bool
     */
    public function senhasCoicidem(): bool
    {
        return $this->senha === $this->confirmacao_senha;
    }
}