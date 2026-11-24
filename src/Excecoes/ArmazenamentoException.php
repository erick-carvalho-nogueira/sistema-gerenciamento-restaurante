<?php
/**
 * Classe: ArmazenamentoException
 * Responsável: Felipe Ferreira Rosas (RA 2026106090004)
 * Descrição: exceção lançada quando o arquivo JSON de dados não pode ser lido, gravado ou interpretado.
 */
declare(strict_types=1);

class ArmazenamentoException extends RestauranteException
{
    public static function leitura(string $caminho, string $motivo): self
    {
        return new self(sprintf('Não foi possível ler "%s": %s.', $caminho, $motivo));
    }

    public static function escrita(string $caminho, string $motivo): self
    {
        return new self(sprintf('Não foi possível gravar "%s": %s.', $caminho, $motivo));
    }

    public static function jsonInvalido(string $caminho, string $detalhe): self
    {
        return new self(sprintf(
            'O arquivo "%s" não contém um JSON válido (%s). Apague-o para o sistema recriar os dados de exemplo.',
            $caminho,
            $detalhe
        ));
    }

    public function getTitulo(): string
    {
        return 'Falha no armazenamento';
    }
}
