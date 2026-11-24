<?php
/**
 * Classe: PagamentoDinheiro
 * Responsável: Danilo Sales Coutinho (RA 2026106090020)
 * Descrição: subclasse de Pagamento para dinheiro; cobra o valor integral, recusa valor insuficiente e calcula o troco.
 */
declare(strict_types=1);

class PagamentoDinheiro extends Pagamento
{
    private float $valorRecebido;

    public function __construct(float $valorRecebido = 0.0)
    {
        if (is_nan($valorRecebido) || $valorRecebido < 0) {
            throw DadosInvalidosException::campo('valor recebido', 'não pode ser negativo');
        }
        $this->valorRecebido = Util::arredondar($valorRecebido);
    }

    public static function deArray(array $dados): self
    {
        return new self(Util::paraFloat($dados['valor_recebido'] ?? '', 'valor recebido'));
    }

    public function getTipo(): string
    {
        return 'dinheiro';
    }

    public function getNome(): string
    {
        return 'Dinheiro';
    }

    public function getRegra(): string
    {
        return 'Valor integral, sem desconto nem acréscimo; o troco é devolvido ao cliente.';
    }

    public function calcularValorCobrado(float $valorConta): float
    {
        return Util::arredondar($valorConta);
    }

    protected function validar(float $valorCobrado): void
    {
        if ($this->valorRecebido < $valorCobrado) {
            throw PagamentoInvalidoException::valorInsuficiente($this->valorRecebido, $valorCobrado);
        }
    }

    public function getComprovante(): array
    {
        return [
            'Valor recebido' => Util::moeda($this->valorRecebido),
            'Troco' => Util::moeda($this->getTroco()),
        ];
    }

    protected function dadosEspecificos(): array
    {
        return ['valor_recebido' => $this->valorRecebido];
    }

    public function getValorRecebido(): float
    {
        return $this->valorRecebido;
    }

    public function getTroco(): float
    {
        if (!$this->isProcessado()) {
            return 0.0;
        }
        return Util::arredondar($this->valorRecebido - $this->getValorCobrado());
    }
}
