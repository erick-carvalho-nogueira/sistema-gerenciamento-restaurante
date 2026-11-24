<?php
/**
 * Classe: PagamentoInvalidoException
 * Responsável: Danilo Sales Coutinho (RA 2026106090020)
 * Descrição: exceção lançada quando um pagamento não pode ser concluído (dinheiro insuficiente, parcelas inválidas, forma desconhecida).
 */
declare(strict_types=1);

class PagamentoInvalidoException extends RestauranteException
{
    public static function valorInsuficiente(float $recebido, float $devido): self
    {
        return new self(sprintf(
            'O valor recebido (%s) é menor que o valor a pagar (%s).',
            Util::moeda($recebido),
            Util::moeda($devido)
        ));
    }

    public static function formaDesconhecida(string $tipo): self
    {
        return new self(sprintf('Forma de pagamento desconhecida: "%s".', $tipo));
    }

    public static function modalidadeInvalida(string $modalidade): self
    {
        return new self(sprintf('Modalidade de cartão inválida: "%s" (use débito ou crédito).', $modalidade));
    }

    public static function parcelasInvalidas(int $parcelas, int $maximo): self
    {
        return new self(sprintf('Número de parcelas inválido (%d): escolha de 1 a %d.', $parcelas, $maximo));
    }

    public static function debitoNaoParcela(): self
    {
        return new self('Cartão de débito só permite pagamento à vista (1x).');
    }

    public static function parcelaMinima(float $minimo): self
    {
        return new self(sprintf('Cada parcela deve ser de pelo menos %s.', Util::moeda($minimo)));
    }

    public static function jaProcessado(): self
    {
        return new self('Este pagamento já foi processado.');
    }

    public static function valorInvalido(): self
    {
        return new self('O valor da conta deve ser maior que zero.');
    }

    public function getTitulo(): string
    {
        return 'Pagamento recusado';
    }
}
