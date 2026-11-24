<?php
/**
 * Classe: PagamentoPix
 * Responsável: Danilo Sales Coutinho (RA 2026106090020)
 * Descrição: subclasse de Pagamento para Pix; concede 5% de desconto sobre o valor da conta.
 */
declare(strict_types=1);

class PagamentoPix extends Pagamento
{
    public const DESCONTO = 0.05;

    public function getTipo(): string
    {
        return 'pix';
    }

    public function getNome(): string
    {
        return 'Pix';
    }

    public function getRegra(): string
    {
        return sprintf('%s de desconto sobre o valor da conta.', Util::percentual(self::DESCONTO));
    }

    public function calcularValorCobrado(float $valorConta): float
    {
        return Util::arredondar($valorConta * (1 - self::DESCONTO));
    }

    public function getComprovante(): array
    {
        return [
            'Chave Pix do restaurante' => Config::CHAVE_PIX,
            'Desconto Pix' => Util::percentual(self::DESCONTO) . ' (' . Util::moeda($this->getValorConta() - $this->getValorCobrado()) . ')',
        ];
    }

    protected function dadosEspecificos(): array
    {
        return [];
    }
}
