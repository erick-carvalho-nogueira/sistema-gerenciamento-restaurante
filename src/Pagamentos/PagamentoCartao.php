<?php
/**
 * Classe: PagamentoCartao
 * Responsável: Danilo Sales Coutinho (RA 2026106090020)
 * Descrição: subclasse de Pagamento para cartão; débito à vista e crédito em até 3x com 2% de juros por parcela adicional.
 */
declare(strict_types=1);

class PagamentoCartao extends Pagamento
{
    public const DEBITO = 'debito';
    public const CREDITO = 'credito';
    public const MAX_PARCELAS = 3;
    public const JUROS_POR_PARCELA = 0.02;
    public const VALOR_MINIMO_PARCELA = 20.00;

    private string $modalidade;
    private int $parcelas;

    public function __construct(string $modalidade = self::CREDITO, int $parcelas = 1)
    {
        $modalidade = strtolower(trim($modalidade));
        if ($modalidade !== self::DEBITO && $modalidade !== self::CREDITO) {
            throw PagamentoInvalidoException::modalidadeInvalida($modalidade);
        }
        if ($parcelas < 1 || $parcelas > self::MAX_PARCELAS) {
            throw PagamentoInvalidoException::parcelasInvalidas($parcelas, self::MAX_PARCELAS);
        }
        if ($modalidade === self::DEBITO && $parcelas !== 1) {
            throw PagamentoInvalidoException::debitoNaoParcela();
        }
        $this->modalidade = $modalidade;
        $this->parcelas = $parcelas;
    }

    public static function deArray(array $dados): self
    {
        return new self(
            Util::limparTexto($dados['modalidade'] ?? self::CREDITO),
            Util::paraInt($dados['parcelas'] ?? 1, 'parcelas')
        );
    }

    public function getTipo(): string
    {
        return 'cartao';
    }

    public function getNome(): string
    {
        if ($this->modalidade === self::DEBITO) {
            return 'Cartão de débito';
        }
        return sprintf('Cartão de crédito (%dx)', $this->parcelas);
    }

    public function getRegra(): string
    {
        if ($this->modalidade === self::DEBITO) {
            return 'Débito: pago à vista, sem acréscimo.';
        }
        if ($this->parcelas === 1) {
            return 'Crédito à vista (1x): sem acréscimo.';
        }
        return sprintf(
            'Crédito em %dx: %s de juros sobre a conta (%s por parcela adicional); parcela mínima de %s.',
            $this->parcelas,
            Util::percentual(self::JUROS_POR_PARCELA * ($this->parcelas - 1)),
            Util::percentual(self::JUROS_POR_PARCELA),
            Util::moeda(self::VALOR_MINIMO_PARCELA)
        );
    }

    public function calcularValorCobrado(float $valorConta): float
    {
        if ($this->parcelas === 1) {
            return Util::arredondar($valorConta);
        }
        return Util::arredondar($valorConta * (1 + self::JUROS_POR_PARCELA * ($this->parcelas - 1)));
    }

    protected function validar(float $valorCobrado): void
    {
        if ($this->parcelas > 1 && $valorCobrado / $this->parcelas < self::VALOR_MINIMO_PARCELA) {
            throw PagamentoInvalidoException::parcelaMinima(self::VALOR_MINIMO_PARCELA);
        }
    }

    public function descreverCobranca(float $valorConta): string
    {
        $total = $this->calcularValorCobrado($valorConta);
        if ($this->parcelas === 1) {
            return Util::moeda($total);
        }
        return sprintf(
            '%dx de %s (total %s)',
            $this->parcelas,
            Util::moeda($this->calcularValorParcela($valorConta)),
            Util::moeda($total)
        );
    }

    public function getComprovante(): array
    {
        return [
            'Modalidade' => $this->modalidade === self::DEBITO ? 'Débito' : 'Crédito',
            'Parcelas' => sprintf('%dx de %s', $this->parcelas, Util::moeda($this->getValorParcela())),
        ];
    }

    protected function dadosEspecificos(): array
    {
        return [
            'modalidade' => $this->modalidade,
            'parcelas' => $this->parcelas,
        ];
    }

    public function getModalidade(): string
    {
        return $this->modalidade;
    }

    public function getParcelas(): int
    {
        return $this->parcelas;
    }

    public function getValorParcela(): float
    {
        return Util::arredondar($this->getValorCobrado() / $this->parcelas);
    }

    public function calcularValorParcela(float $valorConta): float
    {
        return Util::arredondar($this->calcularValorCobrado($valorConta) / $this->parcelas);
    }
}
