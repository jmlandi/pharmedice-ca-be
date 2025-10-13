<?php

namespace App\DTOs;

use Illuminate\Http\UploadedFile;

class LaudoDTO
{
    public function __construct(
        public readonly string $titulo,
        public readonly ?string $descricao,
        public readonly ?string $url_arquivo = null,
        public readonly ?UploadedFile $arquivo = null,
        public readonly bool $ativo = true
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            titulo: $data['titulo'],
            descricao: $data['descricao'] ?? null,
            url_arquivo: $data['url_arquivo'] ?? null,
            arquivo: $data['arquivo'] ?? null,
            ativo: $data['ativo'] ?? true
        );
    }

    public function toArray(): array
    {
        return [
            'titulo' => $this->titulo,
            'descricao' => $this->descricao,
            'url_arquivo' => $this->url_arquivo,
            'ativo' => $this->ativo,
        ];
    }

    public function hasFile(): bool
    {
        return $this->arquivo instanceof UploadedFile;
    }
}