<?php
/**
 * Classe: Funcionario
 * Responsável: Felipe Ferreira Rosas (RA 2026106090004)
 * Descrição: classe abstrata (contrato) de qualquer pessoa da equipe; cada cargo calcula o próprio salário.
 */
declare(strict_types=1);

abstract class Funcionario implements Persistivel
{
    public const SALARIO_MINIMO = 1500.00;
    public const SALARIO_MAXIMO = 50000.00;
    public const TAMANHO_MAXIMO_NOME = 60;

    private static int $totalFuncionarios = 0;

    private ?int $id;
    protected string $nome = '';
    protected float $salarioBase = 0.0;
    private string $dataAdmissao = '';

    public function __construct(string $nome, float $salarioBase, ?string $dataAdmissao = null, ?int $id = null)
    {
        $this->id = $id;
        $this->setNome($nome);
        $this->setSalarioBase($salarioBase);
        $this->setDataAdmissao($dataAdmissao ?? Util::hoje());
        self::$totalFuncionarios++;
    }

    abstract public function getTipo(): string;

    abstract public function getCargo(): string;

    abstract public function calcularSalario(): float;

    abstract public function getDetalhes(): string;

    abstract protected function dadosEspecificos(): array;

    public function aoFecharConta(float $valorVenda, ?int $idGarcom): void
    {
    }

    public function iniciarNovoMes(): void
    {
    }

    public function toArray(): array
    {
        return array_merge([
            'id' => $this->id,
            'tipo' => $this->getTipo(),
            'nome' => $this->nome,
            'salario_base' => $this->salarioBase,
            'admissao' => $this->dataAdmissao,
        ], $this->dadosEspecificos());
    }

    protected static function lerId(array $dados): ?int
    {
        if (!isset($dados['id']) || $dados['id'] === '') {
            return null;
        }
        return Util::paraInt($dados['id'], 'código');
    }

    protected static function lerAdmissao(array $dados): ?string
    {
        $data = Util::limparTexto($dados['admissao'] ?? '');
        return $data === '' ? null : $data;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function definirId(int $id): void
    {
        if ($this->id !== null) {
            throw DadosInvalidosException::campo('código', 'já foi definido e não pode ser alterado');
        }
        if ($id < 1) {
            throw DadosInvalidosException::campo('código', 'deve ser um número positivo');
        }
        $this->id = $id;
    }

    public function getNome(): string
    {
        return $this->nome;
    }

    public function setNome(string $nome): void
    {
        $nome = Util::limparTexto($nome);
        if ($nome === '') {
            throw DadosInvalidosException::campo('nome', 'é obrigatório');
        }
        if (Util::tamanho($nome) > self::TAMANHO_MAXIMO_NOME) {
            throw DadosInvalidosException::campo(
                'nome',
                sprintf('deve ter no máximo %d caracteres', self::TAMANHO_MAXIMO_NOME)
            );
        }
        $this->nome = $nome;
    }

    public function getSalarioBase(): float
    {
        return $this->salarioBase;
    }

    public function setSalarioBase(float $salarioBase): void
    {
        if (is_nan($salarioBase) || $salarioBase < self::SALARIO_MINIMO || $salarioBase > self::SALARIO_MAXIMO) {
            throw DadosInvalidosException::campo(
                'salário base',
                sprintf(
                    'deve estar entre %s e %s',
                    Util::moeda(self::SALARIO_MINIMO),
                    Util::moeda(self::SALARIO_MAXIMO)
                )
            );
        }
        $this->salarioBase = Util::arredondar($salarioBase);
    }

    public function getDataAdmissao(): string
    {
        return $this->dataAdmissao;
    }

    public function setDataAdmissao(string $data): void
    {
        $data = trim($data);
        $dataValida = DateTime::createFromFormat('Y-m-d', $data);
        if ($dataValida === false || $dataValida->format('Y-m-d') !== $data) {
            throw DadosInvalidosException::campo('data de admissão', 'deve ser uma data válida (AAAA-MM-DD)');
        }
        $this->dataAdmissao = $data;
    }

    public static function getTotalFuncionarios(): int
    {
        return self::$totalFuncionarios;
    }
}
