<?php
/**
 * Classe: Funcionario
 * Responsável: Felipe Ferreira Rosas (RA 2026106090004)
 * Descrição: classe abstrata (o "contrato" da equipe) de qualquer funcionário; define o que todo cargo precisa saber fazer.
 */
declare(strict_types=1);

abstract class Funcionario implements Persistivel
{
    public const TAMANHO_MAXIMO_NOME = 60;
    public const SALARIO_MINIMO = 100.00;
    public const SALARIO_MAXIMO = 50000.00;

    /** Membro estático com propósito real: contador de funcionários instanciados na execução. */
    private static int $totalFuncionarios = 0;

    private ?int $id;
    protected string $nome = '';
    protected float $salarioBase = 0.0;

    public function __construct(string $nome, float $salarioBase, ?int $id = null)
    {
        $this->id = $id;
        $this->setNome($nome);
        $this->setSalarioBase($salarioBase);
        self::$totalFuncionarios++;
    }

    // Contrato: cada cargo responde do seu jeito (polimorfismo)

    /** Identificador curto do cargo, usado no JSON e na FuncionarioFactory (ex.: "garcom"). */
    abstract public function getTipo(): string;

    /** Nome legível do cargo (ex.: "Garçom"). */
    abstract public function getCargo(): string;

    /** Salário final do mês: o salário base mais o que o cargo acrescenta (comissão, hora extra, bônus). */
    abstract public function calcularSalario(): float;

    /**
     * Chamado quando uma conta é paga. Cada cargo decide o que fazer com a venda:
     * o garçom soma se atendeu o pedido, o gerente soma ao faturamento, o cozinheiro ignora.
     *
     * @param float    $valor     valor da conta paga
     * @param int|null $idGarcom  código do garçom que atendeu o pedido (null se não houver)
     */
    abstract public function aoFecharConta(float $valor, ?int $idGarcom): void;

    /** Frase curta com a regra de salário do cargo (exibida na interface). */
    abstract public function getRegraDeSalario(): string;

    /** Campos próprios do cargo que entram no JSON. */
    abstract protected function dadosEspecificos(): array;

    // Comportamento comum, herdado por todas as subclasses

    public function descrever(): string
    {
        return sprintf(
            '%s: %s — salário base %s',
            $this->getCargo(),
            $this->nome,
            Util::moeda($this->salarioBase)
        );
    }

    public function toArray(): array
    {
        return array_merge([
            'id' => $this->id,
            'tipo' => $this->getTipo(),
            'nome' => $this->nome,
            'salario_base' => $this->salarioBase,
        ], $this->dadosEspecificos());
    }

    /** Lê o código (id) do array, se existir; usado pelos deArray() das subclasses. */
    protected static function lerId(array $dados): ?int
    {
        if (!isset($dados['id']) || $dados['id'] === '') {
            return null;
        }
        return Util::paraInt($dados['id'], 'código');
    }

    // Getters e setters (encapsulamento: os atributos não são públicos)

    public function getId(): ?int
    {
        return $this->id;
    }

    /** O código só pode ser definido uma vez (quando o funcionário entra na equipe). */
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

    /** Quantos funcionários já foram instanciados nesta execução (membro estático). */
    public static function getTotalFuncionarios(): int
    {
        return self::$totalFuncionarios;
    }
}
