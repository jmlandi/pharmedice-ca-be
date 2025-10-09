<?php

namespace App\Services;

use App\DTOs\LaudoDTO;
use App\Models\Laudo;
use App\Models\Usuario;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class LaudoService
{
    public function listar(int $perPage = 15, array $filtros = [], ?string $usuarioId = null): LengthAwarePaginator
    {
        $query = Laudo::query()->ativo()->with('usuario');

        // Aplicar filtros
        if (!empty($filtros['usuario_id'])) {
            $query->doUsuario($filtros['usuario_id']);
        }

        if (!empty($filtros['titulo'])) {
            $query->where('titulo', 'like', '%' . $filtros['titulo'] . '%');
        }

        // Novo filtro por nome do arquivo
        if (!empty($filtros['nome_arquivo'])) {
            $query->where('url_arquivo', 'like', '%' . $filtros['nome_arquivo'] . '%');
        }

        // Filtro geral que busca em título e nome do arquivo
        if (!empty($filtros['busca'])) {
            $query->where(function($q) use ($filtros) {
                $q->where('titulo', 'like', '%' . $filtros['busca'] . '%')
                  ->orWhere('url_arquivo', 'like', '%' . $filtros['busca'] . '%');
            });
        }

        if (!empty($filtros['data_inicio'])) {
            $query->whereDate('created_at', '>=', $filtros['data_inicio']);
        }

        if (!empty($filtros['data_fim'])) {
            $query->whereDate('created_at', '<=', $filtros['data_fim']);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    public function buscarPorId(string $id, ?string $usuarioId = null): Laudo
    {
        $query = Laudo::query()->ativo()->with('usuario');
        
        // Qualquer usuário autenticado pode ver qualquer laudo
        $laudo = $query->find($id);
        
        if (!$laudo) {
            throw new \Exception('Laudo não encontrado', 404);
        }

        return $laudo;
    }

    public function criar(LaudoDTO $laudoDTO): Laudo
    {
        $dados = $laudoDTO->toArray();

        // Se tem arquivo, faz upload para S3
        if ($laudoDTO->hasFile()) {
            $dados['url_arquivo'] = $this->uploadArquivo($laudoDTO->arquivo);
        }

        return Laudo::create($dados);
    }

    public function atualizar(string $id, LaudoDTO $laudoDTO, ?string $usuarioId = null): Laudo
    {
        $laudo = $this->buscarPorId($id, $usuarioId);
        $dados = $laudoDTO->toArray();

        // Se tem novo arquivo, faz upload e remove o antigo
        if ($laudoDTO->hasFile()) {
            // Remove arquivo antigo
            if ($laudo->url_arquivo) {
                $this->removerArquivo($laudo->url_arquivo);
            }
            
            // Faz upload do novo arquivo
            $dados['url_arquivo'] = $this->uploadArquivo($laudoDTO->arquivo);
        } else {
            // Remove url_arquivo dos dados se não tem arquivo novo
            unset($dados['url_arquivo']);
        }

        $laudo->update($dados);
        
        return $laudo->refresh();
    }

    public function deletar(string $id, ?string $usuarioId = null): bool
    {
        $laudo = $this->buscarPorId($id, $usuarioId);
        
        // Remove arquivo do S3
        if ($laudo->url_arquivo) {
            $this->removerArquivo($laudo->url_arquivo);
        }
        
        // Soft delete - apenas marca como inativo
        return $laudo->update(['ativo' => false]);
    }

    public function downloadLaudo(string $id, ?string $usuarioId = null): array
    {
        $laudo = $this->buscarPorId($id, $usuarioId);
        
        if (!$laudo->url_arquivo) {
            throw new \Exception('Arquivo não encontrado', 404);
        }

        // Verifica se arquivo existe no S3
        if (!Storage::disk('s3')->exists($laudo->url_arquivo)) {
            throw new \Exception('Arquivo não encontrado no storage', 404);
        }

        // Por enquanto retorna o caminho - será implementada URL assinada depois
        $bucketName = config('filesystems.disks.s3.bucket');
        $region = config('filesystems.disks.s3.region');
        $url = "https://{$bucketName}.s3.{$region}.amazonaws.com/{$laudo->url_arquivo}";

        return [
            'url' => $url,
            'nome_arquivo' => $laudo->nome_arquivo,
            'titulo' => $laudo->titulo
        ];
    }

    private function uploadArquivo($arquivo): string
    {
        // Gera nome único para o arquivo
        $nomeArquivo = Str::uuid() . '_' . time() . '.' . $arquivo->getClientOriginalExtension();
        
        // Define o caminho no S3
        $caminho = 'laudos/' . date('Y/m') . '/' . $nomeArquivo;
        
        // Faz upload para S3
        $path = Storage::disk('s3')->putFileAs(
            'laudos/' . date('Y/m'),
            $arquivo,
            $nomeArquivo,
            'private' // Arquivo privado
        );

        return $path;
    }

    private function removerArquivo(string $caminho): bool
    {
        if (Storage::disk('s3')->exists($caminho)) {
            return Storage::disk('s3')->delete($caminho);
        }
        
        return true;
    }

    private function isAdmin(?string $usuarioId): bool
    {
        if (!$usuarioId) {
            return false;
        }
        
        $usuario = Usuario::find($usuarioId);
        return $usuario && $usuario->isAdmin();
    }
}