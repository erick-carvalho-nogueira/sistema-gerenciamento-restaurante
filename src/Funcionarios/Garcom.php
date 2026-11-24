<?php
/**
 * Classe: Garcom
 * Responsável: Felipe Ferreira Rosas (RA 2026106090004)
 * Descrição: subclasse de Funcionario que atende as mesas; o salário é a base mais 5% de comissão sobre as vendas que atendeu.
 */
declare(strict_types=1);

class Garcom extends Funcionario
{
    public const PERCENTUAL_COMISSAO = 0.05;

    private float $vendasNoMes = 0.0;
    private int $mesasAtendidas = 0;

    public static function deArray(array $dados): self
    {
        $garcom = new self(
            Util::limparTexto($dados['nome'] ?? ''),
            Util::paraFloat($dados['salario_base'] ?? '', 'salário base'),
            self::lerAdmissao($dados),
            self::lerId($dados)
        );
        if (isset($dados['vendas_mes'])) {
            $garcom->vendasNoMes = Util::arredondar(Util::paraFloat($dados['vendas_mes'], 'vendas do mês'));
        }
        if (isset($dados['mesas_atendidas'])) {
            $garcom->mesasAtendidas = max(0, Util::paraInt($dados['mesas_atendidas'], 'mesas atendidas'));
        }
        return $garcom;
    }

    public function getTipo(): string
    {
        return 'garcom';
    }

    public function getCargo(): string
    {
        return 'Garçom';
    }

    public function calcularSalario(): float
    {
        return Util::arredondar($this->salarioBase + $this->calcularComissao());
    }

    public function getDetalhes(): string
    {
        return sprintf(
            '%d %s atendida(s), vendas de %s no mês (comissão de %s)',
            $this->mesasAtendidas,
            $this->mesasAtendidas === 1 ? 'mesa' : 'mesas',
            Util::moeda($this->vendasNoMes),
            Util::percentual(self::PERCENTUAL_COMISSAO)
        );
    }

    public function aoFecharConta(float $valorVenda, ?int $idGarcom): void
    {
        if ($idGarcom !== null && $idGarcom === $this->getId()) {
            $this->vendasNoMes = Util::arredondar($this->vendasNoMes + $valorVenda);
            $this->mesasAtendidas++;
        }
    }

    public function iniciarNovoMes(): void
    {
        $this->vendasNoMes = 0.0;
        $this->mesasAtendidas = 0;
    }

    protected function dadosEspecificos(): array
    {
        return [
            'vendas_mes' => $this->vendasNoMes,
            'mesas_atendidas' => $this->mesasAtendidas,
        ];
    }

    public function calcularComissao(): float
    {
        return Util::arredondar($this->vendasNoMes * self::PERCENTUAL_COMISSAO);
    }

    public function getVendasNoMes(): float
    {
        return $this->vendasNoMes;
    }

    public function getMesasAtendidas(): int
    {
        return $this->mesasAtendidas;
    }
}
