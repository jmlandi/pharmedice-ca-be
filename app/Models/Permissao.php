<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Permissao extends Model
{
    use HasFactory, HasUlids;

    protected $table = 'permissoes';

    protected $fillable = [
        'nome',
        'descricao',
        'permissao_admin',
        'ativo',
    ];

    protected $casts = [
        'permissao_admin' => 'boolean',
        'ativo' => 'boolean',
    ];

    // Relationships
    public function usuarios(): BelongsToMany
    {
        return $this->belongsToMany(Usuario::class, 'permissoes_de_usuario');
    }

    // Scopes
    public function scopeAtivo($query)
    {
        return $query->where('ativo', true);
    }

    public function scopeAdmin($query)
    {
        return $query->where('permissao_admin', true);
    }
}
