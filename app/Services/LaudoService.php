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
    public function listar(int $perPage = 15, array $filtros = []): LengthAwarePaginator
    {
        $query = Laudo::query()->ativo();

        // Filtro removido - laudos não são mais associados a usuários
        // if (!empty($filtros['usuario_id'])) {
        //     $query->doUsuario($filtros['usuario_id']);
        // }

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

    public function buscarPorId(string $id): Laudo
    {
        $query = Laudo::query()->ativo();
        
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

    public function atualizar(string $id, LaudoDTO $laudoDTO): Laudo
    {
        $laudo = $this->buscarPorId($id);
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

    public function deletar(string $id): bool
    {
        $laudo = $this->buscarPorId($id);
        
        // Remove arquivo do S3
        if ($laudo->url_arquivo) {
            $this->removerArquivo($laudo->url_arquivo);
        }
        
        // Soft delete - apenas marca como inativo
        return $laudo->update(['ativo' => false]);
    }

    public function downloadLaudo(string $id): array
    {
        $laudo = $this->buscarPorId($id);
        
        if (!$laudo->url_arquivo) {
            throw new \Exception('Arquivo não encontrado', 404);
        }

        // Verifica se arquivo existe no S3
        if (!Storage::disk('s3')->exists($laudo->url_arquivo)) {
            throw new \Exception('Arquivo não encontrado no storage', 404);
        }

        // Por enquanto, gera URL direta do S3 (pode ser configurada como pública ou usar pre-signed URLs)
        $bucketName = config('filesystems.disks.s3.bucket');
        $region = config('filesystems.disks.s3.region');
        $url = "https://{$bucketName}.s3.{$region}.amazonaws.com/{$laudo->url_arquivo}";

        return [
            'url' => $url,
            'nome_arquivo' => $laudo->nome_arquivo,
            'nome_arquivo_original' => $laudo->nome_arquivo_original,
            'titulo' => $laudo->titulo,
            'tamanho_arquivo' => $this->getTamanhoArquivo($laudo->url_arquivo),
            'content_type' => 'application/pdf'
        ];
    }

    private function uploadArquivo($arquivo): string
    {
        // Preserva o nome original do arquivo
        $nomeOriginal = pathinfo($arquivo->getClientOriginalName(), PATHINFO_FILENAME);
        $extensao = $arquivo->getClientOriginalExtension();
        
        // Gera nome único mantendo referência ao original
        $nomeArquivo = Str::uuid() . '_' . time() . '_' . Str::slug($nomeOriginal) . '.' . $extensao;
        
        // Define o caminho no S3 organizando por ano/mês
        $diretorio = 'laudos/' . date('Y/m');
        
        // Faz upload para S3
        $path = Storage::disk('s3')->putFileAs(
            $diretorio,
            $arquivo,
            $nomeArquivo,
            'private' // Arquivo privado - só acessível via URLs assinadas
        );

        if (!$path) {
            throw new \Exception('Erro ao fazer upload do arquivo para o S3', 500);
        }

        return $path;
    }

    private function removerArquivo(string $caminho): bool
    {
        if (Storage::disk('s3')->exists($caminho)) {
            return Storage::disk('s3')->delete($caminho);
        }
        
        return true;
    }

    private function getTamanhoArquivo(string $caminho): ?int
    {
        try {
            return Storage::disk('s3')->size($caminho);
        } catch (\Exception $e) {
            return null;
        }
    }

    // Método removido - não é mais necessário verificar permissões baseadas em ownership
}