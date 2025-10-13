<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

class Laudo extends Model
{
    use HasFactory, HasUlids;

    protected $table = 'laudos';

    protected $fillable = [
        'titulo',
        'descricao',
        'url_arquivo',
        'ativo',
    ];

    protected $casts = [
        'ativo' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relacionamentos removidos - laudos agora são independentes de usuários específicos

    // Scopes
    public function scopeAtivo($query)
    {
        return $query->where('ativo', true);
    }

    // Scope removido - laudos não são mais associados a usuários específicos

    // Accessors
    public function getArquivoUrlAttribute()
    {
        if (!$this->url_arquivo) {
            return null;
        }

        // Se já é uma URL completa, retorna como está
        if (filter_var($this->url_arquivo, FILTER_VALIDATE_URL)) {
            return $this->url_arquivo;
        }

        // Para S3, retorna o caminho - será processado no controller
        return $this->url_arquivo;
    }

    public function getNomeArquivoAttribute()
    {
        if (!$this->url_arquivo) {
            return null;
        }
        return basename($this->url_arquivo);
    }

    public function getNomeArquivoOriginalAttribute()
    {
        if (!$this->url_arquivo) {
            return null;
        }
        
        $nomeCompleto = basename($this->url_arquivo);
        
        // Remove o UUID e timestamp do início do nome se existir
        // Formato esperado: uuid_timestamp_nome_original.pdf
        if (preg_match('/^[a-f0-9-]+_\d+_(.+)$/i', $nomeCompleto, $matches)) {
            return $matches[1];
        }
        
        return $nomeCompleto;
    }

    // Método removido - laudos não pertencem mais a usuários específicos

    public function deleteArquivo(): bool
    {
        if ($this->url_arquivo && Storage::disk('s3')->exists($this->url_arquivo)) {
            return Storage::disk('s3')->delete($this->url_arquivo);
        }
        return true;
    }
}
