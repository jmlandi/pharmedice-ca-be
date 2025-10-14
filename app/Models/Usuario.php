<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Support\Facades\Hash;

class Usuario extends Authenticatable implements MustVerifyEmail, JWTSubject
{
    use HasFactory, Notifiable, HasUlids;

    protected $table = 'usuarios';

    protected $fillable = [
        'primeiro_nome',
        'segundo_nome',
        'apelido',
        'email',
        'senha',
        'telefone',
        'numero_documento',
        'data_nascimento',
        'tipo_usuario',
        'aceite_comunicacoes_email',
        'aceite_comunicacoes_sms',
        'aceite_comunicacoes_whatsapp',
        'ativo',
        'email_verified_at',
        'google_id',
        'provider',
        'avatar',
    ];

    protected $hidden = [
        'senha',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'data_nascimento' => 'date',
        'aceite_comunicacoes_email' => 'boolean',
        'aceite_comunicacoes_sms' => 'boolean',
        'aceite_comunicacoes_whatsapp' => 'boolean',
        'ativo' => 'boolean',
    ];

    // JWT Methods
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [
            'tipo_usuario' => $this->tipo_usuario,
            'email' => $this->email,
            'nome_completo' => $this->nome_completo,
        ];
    }

    // Password handling for Laravel Auth
    public function getAuthPassword()
    {
        return $this->senha;
    }

    // Mutators
    public function setSenhaAttribute($value)
    {
        // Apenas aplicar hash se o valor não for null
        if ($value !== null) {
            $this->attributes['senha'] = Hash::make($value);
        }
    }

    // Accessors
    public function getNomeCompletoAttribute()
    {
        $nome = $this->primeiro_nome;
        if ($this->segundo_nome) {
            $nome .= ' ' . $this->segundo_nome;
        }
        return trim($nome);
    }

    public function getIsAdminAttribute()
    {
        return $this->tipo_usuario === 'administrador';
    }

    // Relationships
    public function permissoes(): BelongsToMany
    {
        return $this->belongsToMany(Permissao::class, 'permissoes_de_usuario');
    }

    // Scopes
    public function scopeAtivo($query)
    {
        return $query->where('ativo', true);
    }

    public function scopeAdmin($query)
    {
        return $query->where('tipo_usuario', 'administrador');
    }

    public function scopeCliente($query)
    {
        return $query->where('tipo_usuario', 'usuario');
    }

    // Methods
    public function hasPermission(string $permission): bool
    {
        return $this->permissoes()->where('nome', $permission)->where('ativo', true)->exists();
    }

    public function isAdmin(): bool
    {
        return $this->tipo_usuario === 'administrador';
    }
}
