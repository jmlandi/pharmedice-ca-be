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
        'usuario_id', // ID do usuário que CRIOU o laudo (não necessariamente o paciente)
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

    // Relationships
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class);
    }

    // Alias para deixar claro que é o criador do laudo
    public function criador(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    // Scopes
    public function scopeAtivo($query)
    {
        return $query->where('ativo', true);
    }

    public function scopeDoUsuario($query, $usuarioId)
    {
        return $query->where('usuario_id', $usuarioId);
    }

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

    // Methods
    public function pertenceAoUsuario($usuarioId): bool
    {
        return $this->usuario_id == $usuarioId;
    }

    public function deleteArquivo(): bool
    {
        if ($this->url_arquivo && Storage::disk('s3')->exists($this->url_arquivo)) {
            return Storage::disk('s3')->delete($this->url_arquivo);
        }
        return true;
    }
}
