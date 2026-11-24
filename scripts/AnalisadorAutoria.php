<?php
/**
 * Classe: AnalisadorAutoria
 * Responsável: Erik Carvalho Nogueira (RA 2026106090008)
 * Descrição: lê os cabeçalhos "Classe/Responsável/Descrição" dos arquivos do projeto para conferir, listar e atualizar a autoria de cada integrante.
 */
declare(strict_types=1);

final class AnalisadorAutoria
{
    public const RA_PENDENTE = '00000000';
    public const MARCA_INICIO = '<!-- AUTORIA:INICIO -->';
    public const MARCA_FIM = '<!-- AUTORIA:FIM -->';

    private const EXTENSOES_DE_CODIGO = ['php', 'js', 'css'];
    private const PASTAS_IGNORADAS = ['data', 'node_modules', 'vendor'];

    private string $raiz;

    public function __construct(string $raiz)
    {
        $this->raiz = rtrim(str_replace('\\', '/', $raiz), '/');
    }

    public function listarArquivos(array $extensoes): array
    {
        $arquivos = [];
        $iterador = new RecursiveIteratorIterator(
            new RecursiveCallbackFilterIterator(
                new RecursiveDirectoryIterator($this->raiz, FilesystemIterator::SKIP_DOTS),
                static function (SplFileInfo $atual): bool {
                    $nome = $atual->getFilename();
                    return !($atual->isDir() && ($nome[0] === '.' || in_array($nome, self::PASTAS_IGNORADAS, true)));
                }
            )
        );
        foreach ($iterador as $arquivo) {
            if ($arquivo->isFile() && in_array(strtolower($arquivo->getExtension()), $extensoes, true)) {
                $caminho = str_replace('\\', '/', $arquivo->getPathname());
                $arquivos[] = ltrim(substr($caminho, strlen($this->raiz)), '/');
            }
        }
        sort($arquivos);
        return $arquivos;
    }

    public function listarArquivosDeCodigo(): array
    {
        return $this->listarArquivos(self::EXTENSOES_DE_CODIGO);
    }

    public function lerCabecalho(string $caminhoRelativo): ?array
    {
        $conteudo = @file_get_contents($this->raiz . '/' . $caminhoRelativo);
        if ($conteudo === false) {
            return null;
        }
        $linhas = preg_split('/\R/', $conteudo) ?: [];
        $topo = implode("\n", array_slice($linhas, 0, 15));

        if (
            preg_match('/^\s*\*\s*(Classe|Arquivo):\s*(.+?)\s*$/mu', $topo, $nome) !== 1
            || preg_match('/^\s*\*\s*Respons[áa]vel:\s*(.+?)\s*$/mu', $topo, $responsavel) !== 1
            || preg_match('/^\s*\*\s*Descri[çc][ãa]o:\s*(.+?)\s*$/mu', $topo, $descricao) !== 1
        ) {
            return null;
        }

        $responsaveis = [];
        if (preg_match_all('/([^()]+?)\s*\(RA\s*([^)]*)\)/u', $responsavel[1], $encontrados, PREG_SET_ORDER) > 0) {
            foreach ($encontrados as $par) {
                $nomeLimpo = trim((string) preg_replace('/^\s*(?:e|,|;|&)\s+/u', '', trim($par[1])));
                if ($nomeLimpo !== '') {
                    $responsaveis[] = ['nome' => $nomeLimpo, 'ra' => trim($par[2])];
                }
            }
        }
        if (count($responsaveis) === 0) {
            return null;
        }

        return [
            'tipo' => $nome[1],
            'nome' => $nome[2],
            'responsaveis' => $responsaveis,
            'descricao' => $descricao[1],
        ];
    }

    public function analisar(): array
    {
        $arquivos = [];
        $semCabecalho = [];
        foreach ($this->listarArquivosDeCodigo() as $caminho) {
            $cabecalho = $this->lerCabecalho($caminho);
            if ($cabecalho === null) {
                $semCabecalho[] = $caminho;
            } else {
                $arquivos[$caminho] = $cabecalho;
            }
        }
        return ['arquivos' => $arquivos, 'sem_cabecalho' => $semCabecalho];
    }

    public function agruparPorIntegrante(): array
    {
        $grupos = [];
        foreach ($this->analisar()['arquivos'] as $caminho => $cabecalho) {
            $rotulo = $cabecalho['tipo'] === 'Classe' ? $cabecalho['nome'] : $caminho;
            foreach ($cabecalho['responsaveis'] as $responsavel) {
                $nome = $responsavel['nome'];
                if (!isset($grupos[$nome])) {
                    $grupos[$nome] = ['ra' => $responsavel['ra'], 'rotulos' => [], 'arquivos' => []];
                }
                $grupos[$nome]['rotulos'][] = $rotulo;
                $grupos[$nome]['arquivos'][] = $caminho;
            }
        }
        ksort($grupos);
        return $grupos;
    }

    public function integrantesComRaPendente(): array
    {
        $pendentes = [];
        foreach ($this->agruparPorIntegrante() as $nome => $dados) {
            if ($dados['ra'] === self::RA_PENDENTE) {
                $pendentes[] = $nome;
            }
        }
        return $pendentes;
    }

    public function montarBlocoDeAutoria(): string
    {
        $linhas = ['AUTORIA POR INTEGRANTE:'];
        foreach ($this->agruparPorIntegrante() as $nome => $dados) {
            $linhas[] = sprintf('- %s - RA %s: responsável por %s', $nome, $dados['ra'], implode(', ', $dados['rotulos']));
        }
        return implode("\n", $linhas);
    }

    public function atualizarTextosComAutoria(): array
    {
        $bloco = self::MARCA_INICIO . "\n```\n" . $this->montarBlocoDeAutoria() . "\n```\n" . self::MARCA_FIM;
        $atualizados = [];
        foreach ($this->listarArquivos(['md']) as $relativo) {
            $caminho = $this->raiz . '/' . $relativo;
            $conteudo = @file_get_contents($caminho);
            if ($conteudo === false) {
                continue;
            }
            $inicio = strpos($conteudo, self::MARCA_INICIO);
            $fim = strpos($conteudo, self::MARCA_FIM);
            if ($inicio === false || $fim === false || $fim < $inicio) {
                continue;
            }
            $novo = substr($conteudo, 0, $inicio) . $bloco . substr($conteudo, $fim + strlen(self::MARCA_FIM));
            if ($novo !== $conteudo) {
                file_put_contents($caminho, $novo);
            }
            $atualizados[] = $relativo;
        }
        return $atualizados;
    }

    public function definirRa(string $nomeDoIntegrante, string $ra): int
    {
        if (preg_match('/^[0-9A-Za-z.\-]{3,20}$/', $ra) !== 1) {
            throw new InvalidArgumentException('RA inválido: use de 3 a 20 letras, números, ponto ou hífen.');
        }

        $alterados = 0;
        $padrao = '/(' . preg_quote($nomeDoIntegrante, '/') . ')(\s*[-(]\s*RA\s*:?\s*)' . preg_quote(self::RA_PENDENTE, '/') . '/u';
        foreach ($this->listarArquivos(array_merge(self::EXTENSOES_DE_CODIGO, ['md'])) as $caminho) {
            $absoluto = $this->raiz . '/' . $caminho;
            $conteudo = file_get_contents($absoluto);
            if ($conteudo === false) {
                continue;
            }
            $novo = preg_replace_callback(
                $padrao,
                static function (array $partes) use ($ra): string {
                    return $partes[1] . $partes[2] . $ra;
                },
                $conteudo
            );
            if ($novo !== null && $novo !== $conteudo) {
                file_put_contents($absoluto, $novo);
                $alterados++;
            }
        }
        return $alterados;
    }
}
