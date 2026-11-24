<?php
/**
 * Classe: Pagamento
 * Responsável: Danilo Sales Coutinho (RA 2026106090020)
 * Descrição: classe abstrata (contrato) de uma forma de pagamento; processar() é um Template Method que delega as regras às subclasses.
 */
declare(strict_types=1);

abstract class Pagamento implements Persistivel
{
    private float $valorConta = 0.0;
    private float $valorCobrado = 0.0;
    private bool $processado = false;
    private ?string $codigo = null;
    private ?string $processadoEm = null;

    abstract public function getTipo(): string;

    abstract public function getNome(): string;

    abstract public function getRegra(): string;

    abstract public function calcularValorCobrado(float $valorConta): float;

    abstract public function getComprovante(): array;

    abstract protected function dadosEspecificos(): array;

    protected function validar(float $valorCobrado): void
    {
    }

    public function descreverCobranca(float $valorConta): string
    {
        return Util::moeda($this->calcularValorCobrado($valorConta));
    }

    final public function processar(float $valorConta, int $referencia): void
    {
        if ($this->processado) {
            throw PagamentoInvalidoException::jaProcessado();
        }
        if (is_nan($valorConta) || $valorConta <= 0) {
            throw PagamentoInvalidoException::valorInvalido();
        }

        $valorCobrado = $this->calcularValorCobrado($valorConta);
        $this->validar($valorCobrado);

        $this->valorConta = Util::arredondar($valorConta);
        $this->valorCobrado = Util::arredondar($valorCobrado);
        $this->codigo = sprintf('PG%05d', $referencia);
        $this->processadoEm = Util::agora();
        $this->processado = true;
    }

    public function toArray(): array
    {
        return array_merge([
            'tipo' => $this->getTipo(),
            'processado' => $this->processado,
            'valor_conta' => $this->valorConta,
            'valor_cobrado' => $this->valorCobrado,
            'codigo' => $this->codigo,
            'processado_em' => $this->processadoEm,
        ], $this->dadosEspecificos());
    }

    public function restaurarEstado(array $dados): void
    {
        $this->processado = Util::paraBool($dados['processado'] ?? false);
        $this->valorConta = Util::paraFloat($dados['valor_conta'] ?? 0, 'valor da conta');
        $this->valorCobrado = Util::paraFloat($dados['valor_cobrado'] ?? 0, 'valor cobrado');
        $codigo = Util::limparTexto($dados['codigo'] ?? '');
        $this->codigo = $codigo === '' ? null : $codigo;
        $momento = Util::limparTexto($dados['processado_em'] ?? '');
        $this->processadoEm = $momento === '' ? null : $momento;
    }

    public function isProcessado(): bool
    {
        return $this->processado;
    }

    public function getValorConta(): float
    {
        return $this->valorConta;
    }

    public function getValorCobrado(): float
    {
        return $this->valorCobrado;
    }

    public function getCodigo(): ?string
    {
        return $this->codigo;
    }

    public function getProcessadoEm(): ?string
    {
        return $this->processadoEm;
    }
}
