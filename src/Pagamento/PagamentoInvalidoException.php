<?php
/**
 * Classe: PagamentoInvalidoException
 * Responsável: Danilo Sales Coutinho (RA 2026106090020)
 * Descrição: exceção lançada quando ocorre uma ocasião indesejada no processo de pagamento.
 */

declare(strict_types=1);

class PagamentoInvalidoException extends RestauranteException
{
    /** Ex.: PagamentoInvalidoException::valorNegativo(-100) → O valor do pagamento não pode ser negativo. */

    public static function dinheiroInsuficiente(float $valor): self
    {
        return new self(sprintf('A quantia de dinheiro registrada não coincide com a quantidade exigida: %.2f', $valor));
    }

    public static function parcelasInsuficientes(float $valor, int $parcelas)
    {
        return new self(sprintf('As parcelas, de quantia de %d, não foram pagas, sob o total de: %.2f'));
    }

    public static function pagamentoFormaDesc(int $valor, string $escolhapagar): self
    {
        return new self(sprintf('O método de pagamento %s não está em nenhuma das formas de pagamento processadas, sob o valor de: %2.f', $escolhapagar, $valor));
    }

    public static function valorNegativo(float $valor): self
    {
        return new self(sprintf('O valor do pagamento é negativo e não pode ser processado: %.2f.', $valor));
    }

    public function getTitulo(): string
    {
        return 'Pagamento inválido';
    }
}
?>