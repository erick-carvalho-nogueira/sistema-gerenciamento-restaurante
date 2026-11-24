<?php
/**
 * Classe: ArmazenamentoJson
 * Responsável: Felipe Ferreira Rosas (RA 2026106090004)
 * Descrição: lê e grava os dados do restaurante em um arquivo JSON (com trava de arquivo) e lança ArmazenamentoException se algo falhar.
 */
declare(strict_types=1);

class ArmazenamentoJson
{
    private string $caminho;

    public function __construct(string $caminho)
    {
        $this->caminho = $caminho;
    }

    public function getCaminho(): string
    {
        return $this->caminho;
    }

    public function existe(): bool
    {
        return is_file($this->caminho);
    }

    public function carregar(): array
    {
        if (!$this->existe()) {
            return [];
        }

        $manipulador = @fopen($this->caminho, 'rb');
        if ($manipulador === false) {
            throw ArmazenamentoException::leitura($this->caminho, 'arquivo inacessível');
        }
        try {
            flock($manipulador, LOCK_SH);
            $conteudo = stream_get_contents($manipulador);
            flock($manipulador, LOCK_UN);
        } finally {
            fclose($manipulador);
        }

        if ($conteudo === false) {
            throw ArmazenamentoException::leitura($this->caminho, 'a leitura falhou');
        }
        if (strncmp($conteudo, "\xEF\xBB\xBF", 3) === 0) {
            $conteudo = substr($conteudo, 3);
        }
        $conteudo = trim($conteudo);
        if ($conteudo === '') {
            return [];
        }

        $dados = json_decode($conteudo, true);
        if (!is_array($dados)) {
            throw ArmazenamentoException::jsonInvalido($this->caminho, json_last_error_msg());
        }
        return $dados;
    }

    public function salvar(array $dados): void
    {
        $pasta = dirname($this->caminho);
        if (!is_dir($pasta) && !@mkdir($pasta, 0775, true) && !is_dir($pasta)) {
            throw ArmazenamentoException::escrita($this->caminho, 'não foi possível criar a pasta de dados');
        }

        $json = json_encode(
            $dados,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION
        );
        if ($json === false) {
            throw ArmazenamentoException::jsonInvalido($this->caminho, json_last_error_msg());
        }

        if (@file_put_contents($this->caminho, $json . PHP_EOL, LOCK_EX) === false) {
            throw ArmazenamentoException::escrita($this->caminho, 'verifique as permissões de escrita da pasta "data"');
        }
    }

    public function apagar(): void
    {
        if ($this->existe() && !@unlink($this->caminho)) {
            throw ArmazenamentoException::escrita($this->caminho, 'não foi possível apagar o arquivo');
        }
    }
}
