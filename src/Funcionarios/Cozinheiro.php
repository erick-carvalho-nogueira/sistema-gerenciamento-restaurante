<?php
/**
 * Classe: Cozinheiro
 * Responsável: Felipe Ferreira Rosas (RA 2026106090004)
 * Descrição: subclasse de Funcionario que prepara os pratos; o salário é a base mais as horas extras com 50% de adicional.
 */
declare(strict_types=1);

class Cozinheiro extends Funcionario
{
    public const FATOR_HORA_EXTRA = 1.5;
    public const HORAS_NORMAIS_NO_MES = 220;
    public const LIMITE_HORAS_EXTRAS = 60.0;
    public const TAMANHO_MAXIMO_ESPECIALIDADE = 40;

    private string $especialidade = 'Cozinha geral';
    private float $horasExtras = 0.0;

    public function __construct(
        string $nome,
        float $salarioBase,
        string $especialidade = 'Cozinha geral',
        ?string $dataAdmissao = null,
        ?int $id = null
    ) {
        parent::__construct($nome, $salarioBase, $dataAdmissao, $id);
        $this->setEspecialidade($especialidade);
    }

    public static function deArray(array $dados): self
    {
        $especialidade = Util::limparTexto($dados['especialidade'] ?? '');
        $cozinheiro = new self(
            Util::limparTexto($dados['nome'] ?? ''),
            Util::paraFloat($dados['salario_base'] ?? '', 'salário base'),
            $especialidade === '' ? 'Cozinha geral' : $especialidade,
            self::lerAdmissao($dados),
            self::lerId($dados)
        );
        if (isset($dados['horas_extras'])) {
            $cozinheiro->horasExtras = max(0.0, Util::paraFloat($dados['horas_extras'], 'horas extras'));
        }
        return $cozinheiro;
    }

    public function getTipo(): string
    {
        return 'cozinheiro';
    }

    public function getCargo(): string
    {
        return 'Cozinheiro';
    }

    public function calcularSalario(): float
    {
        $valorHoraNormal = $this->salarioBase / self::HORAS_NORMAIS_NO_MES;
        return Util::arredondar(
            $this->salarioBase + $this->horasExtras * $valorHoraNormal * self::FATOR_HORA_EXTRA
        );
    }

    public function getDetalhes(): string
    {
        return sprintf(
            'especialidade: %s; %s h extras no mês',
            $this->especialidade,
            number_format($this->horasExtras, 1, ',', '.')
        );
    }

    public function iniciarNovoMes(): void
    {
        $this->horasExtras = 0.0;
    }

    protected function dadosEspecificos(): array
    {
        return [
            'especialidade' => $this->especialidade,
            'horas_extras' => $this->horasExtras,
        ];
    }

    public function getEspecialidade(): string
    {
        return $this->especialidade;
    }

    public function setEspecialidade(string $especialidade): void
    {
        $especialidade = Util::limparTexto($especialidade);
        if ($especialidade === '') {
            throw DadosInvalidosException::campo('especialidade', 'é obrigatória');
        }
        if (Util::tamanho($especialidade) > self::TAMANHO_MAXIMO_ESPECIALIDADE) {
            throw DadosInvalidosException::campo(
                'especialidade',
                sprintf('deve ter no máximo %d caracteres', self::TAMANHO_MAXIMO_ESPECIALIDADE)
            );
        }
        $this->especialidade = $especialidade;
    }

    public function getHorasExtras(): float
    {
        return $this->horasExtras;
    }

    public function registrarHorasExtras(float $horas): void
    {
        if (is_nan($horas) || $horas <= 0) {
            throw DadosInvalidosException::campo('horas extras', 'devem ser maiores que zero');
        }
        $total = $this->horasExtras + $horas;
        if ($total > self::LIMITE_HORAS_EXTRAS) {
            throw DadosInvalidosException::campo(
                'horas extras',
                sprintf('não podem passar de %s horas no mês', number_format(self::LIMITE_HORAS_EXTRAS, 0, ',', '.'))
            );
        }
        $this->horasExtras = round($total, 2);
    }
}
