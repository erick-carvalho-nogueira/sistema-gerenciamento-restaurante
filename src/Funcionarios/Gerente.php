<?php
/**
 * Classe: Gerente
 * Responsável: Felipe Ferreira Rosas (RA 2026106090004)
 * Descrição: subclasse de Funcionario que administra o restaurante; ganha 20% de bônus sobre a base quando a meta de faturamento é atingida.
 */
declare(strict_types=1);

class Gerente extends Funcionario
{
    public const PERCENTUAL_BONUS = 0.20;
    public const META_PADRAO = 30000.00;

    private float $metaMensal = self::META_PADRAO;
    private float $faturamentoEquipe = 0.0;

    public function __construct(
        string $nome,
        float $salarioBase,
        float $metaMensal = self::META_PADRAO,
        ?string $dataAdmissao = null,
        ?int $id = null
    ) {
        parent::__construct($nome, $salarioBase, $dataAdmissao, $id);
        $this->setMetaMensal($metaMensal);
    }

    public static function deArray(array $dados): self
    {
        $meta = $dados['meta_mensal'] ?? '';
        $gerente = new self(
            Util::limparTexto($dados['nome'] ?? ''),
            Util::paraFloat($dados['salario_base'] ?? '', 'salário base'),
            $meta === '' ? self::META_PADRAO : Util::paraFloat($meta, 'meta mensal'),
            self::lerAdmissao($dados),
            self::lerId($dados)
        );
        if (isset($dados['faturamento_equipe'])) {
            $gerente->faturamentoEquipe = max(
                0.0,
                Util::arredondar(Util::paraFloat($dados['faturamento_equipe'], 'faturamento da equipe'))
            );
        }
        return $gerente;
    }

    public function getTipo(): string
    {
        return 'gerente';
    }

    public function getCargo(): string
    {
        return 'Gerente';
    }

    public function calcularSalario(): float
    {
        return Util::arredondar($this->salarioBase + $this->calcularBonus());
    }

    public function getDetalhes(): string
    {
        return sprintf(
            'meta de %s; faturamento de %s (%s)',
            Util::moeda($this->metaMensal),
            Util::moeda($this->faturamentoEquipe),
            $this->metaAtingida() ? 'meta atingida' : 'meta não atingida'
        );
    }

    public function aoFecharConta(float $valorVenda, ?int $idGarcom): void
    {
        $this->faturamentoEquipe = Util::arredondar($this->faturamentoEquipe + $valorVenda);
    }

    public function iniciarNovoMes(): void
    {
        $this->faturamentoEquipe = 0.0;
    }

    protected function dadosEspecificos(): array
    {
        return [
            'meta_mensal' => $this->metaMensal,
            'faturamento_equipe' => $this->faturamentoEquipe,
        ];
    }

    public function metaAtingida(): bool
    {
        return $this->faturamentoEquipe >= $this->metaMensal;
    }

    public function calcularBonus(): float
    {
        return $this->metaAtingida() ? Util::arredondar($this->salarioBase * self::PERCENTUAL_BONUS) : 0.0;
    }

    public function getMetaMensal(): float
    {
        return $this->metaMensal;
    }

    public function setMetaMensal(float $metaMensal): void
    {
        if (is_nan($metaMensal) || $metaMensal <= 0) {
            throw DadosInvalidosException::campo('meta mensal', 'deve ser maior que zero');
        }
        $this->metaMensal = Util::arredondar($metaMensal);
    }

    public function getFaturamentoEquipe(): float
    {
        return $this->faturamentoEquipe;
    }
}
