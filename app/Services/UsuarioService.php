<?php

namespace App\Services;

use App\DTOs\UsuarioDTO;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class UsuarioService
{
    public function listar(int $perPage = 15, array $filtros = []): LengthAwarePaginator
    {
        $query = Usuario::query()->ativo();

        // Aplicar filtros
        if (!empty($filtros['tipo_usuario'])) {
            $query->where('tipo_usuario', $filtros['tipo_usuario']);
        }

        if (!empty($filtros['email'])) {
            $query->where('email', 'like', '%' . $filtros['email'] . '%');
        }

        if (!empty($filtros['nome'])) {
            $query->where(function($q) use ($filtros) {
                $q->where('primeiro_nome', 'like', '%' . $filtros['nome'] . '%')
                  ->orWhere('segundo_nome', 'like', '%' . $filtros['nome'] . '%')
                  ->orWhere('apelido', 'like', '%' . $filtros['nome'] . '%');
            });
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    public function buscarPorId(string $id): Usuario
    {
        $usuario = Usuario::ativo()->find($id);
        
        if (!$usuario) {
            throw new \Exception('Usuário não encontrado', 404);
        }

        return $usuario;
    }

    public function criar(UsuarioDTO $usuarioDTO): Usuario
    {
        // Verificar se email já existe
        if (Usuario::where('email', $usuarioDTO->email)->exists()) {
            throw new \Exception('Email já cadastrado', 422);
        }

        // Verificar se documento já existe
        if (Usuario::where('numero_documento', $usuarioDTO->numero_documento)->exists()) {
            throw new \Exception('Número de documento já cadastrado', 422);
        }

        return Usuario::create($usuarioDTO->toArray());
    }

    public function atualizar(string $id, UsuarioDTO $usuarioDTO): Usuario
    {
        $usuario = $this->buscarPorId($id);

        // Verificar se email já existe (exceto para o próprio usuário)
        if (Usuario::where('email', $usuarioDTO->email)->where('id', '!=', $id)->exists()) {
            throw new \Exception('Email já cadastrado', 422);
        }

        // Verificar se documento já existe (exceto para o próprio usuário)
        if (Usuario::where('numero_documento', $usuarioDTO->numero_documento)->where('id', '!=', $id)->exists()) {
            throw new \Exception('Número de documento já cadastrado', 422);
        }

        $dados = $usuarioDTO->toArray();
        
        // Se não tem senha, remove do array
        if (empty($dados['senha'])) {
            unset($dados['senha']);
        }

        $usuario->update($dados);
        
        return $usuario->refresh();
    }

    public function deletar(string $id): bool
    {
        $usuario = $this->buscarPorId($id);
        
        // Soft delete - apenas marca como inativo
        return $usuario->update(['ativo' => false]);
    }

    public function alterarSenha(string $id, string $novaSenha): bool
    {
        $usuario = $this->buscarPorId($id);
        
        return $usuario->update(['senha' => $novaSenha]);
    }

    public function buscarPorEmail(string $email): ?Usuario
    {
        return Usuario::where('email', $email)->ativo()->first();
    }
}