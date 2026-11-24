<?php
/**
 * Classe: ItemCardapio
 * Responsável: Erik Carvalho Nogueira (RA 2026106090008)
 * Descrição: classe abstrata (o "contrato" do sistema) de qualquer item vendido; define o que todo item precisa saber fazer.
 */
declare(strict_types=1);

abstract class ItemCardapio implements Persistivel
{
    public const PRECO_MINIMO = 0.50;
    public const PRECO_MAXIMO = 9999.99;
    public const TAMANHO_MAXIMO_NOME = 60;
    public const TAMANHO_MAXIMO_DESCRICAO = 160;

    private static int $totalInstancias = 0;

    private ?int $id;
    protected string $nome = '';
    protected float $preco = 0.0;
    private string $descricao = '';
    private bool $disponivel = true;

    public function __construct(string $nome, float $preco, string $descricao = '', ?int $id = null)
    {
        $this->id = $id;
        $this->setNome($nome);
        $this->setPreco($preco);
        $this->setDescricao($descricao);
        self::$totalInstancias++;
    }

    abstract public function getTipo(): string;

    abstract public function getCategoria(): string;

    abstract public function getSetorPreparo(): string;

    abstract public function getTempoPreparoMinutos(): int;

    abstract public function calcularPreco(int $quantidade): float;

    abstract public function getRegraDePreco(): string;

    abstract public function getDetalhes(): string;

    abstract protected function dadosEspecificos(): array;

    public function descrever(): string
    {
        return sprintf(
            '%s: %s — %s (%s)',
            $this->getCategoria(),
            $this->nome,
            Util::moeda($this->preco),
            $this->getDetalhes()
        );
    }

    public function toArray(): array
    {
        return array_merge([
            'id' => $this->id,
            'tipo' => $this->getTipo(),
            'nome' => $this->nome,
            'descricao' => $this->descricao,
            'preco' => $this->preco,
            'disponivel' => $this->disponivel,
        ], $this->dadosEspecificos());
    }

    protected function exigirQuantidadeValida(int $quantidade): void
    {
        if ($quantidade < 1) {
            throw DadosInvalidosException::campo('quantidade', 'deve ser pelo menos 1');
        }
    }

    protected static function lerId(array $dados): ?int
    {
        if (!isset($dados['id']) || $dados['id'] === '') {
            return null;
        }
        return Util::paraInt($dados['id'], 'código');
    }

    protected static function aplicarDadosComuns(ItemCardapio $item, array $dados): void
    {
        if (array_key_exists('disponivel', $dados)) {
            $item->setDisponivel(Util::paraBool($dados['disponivel']));
        }
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

    public function getPreco(): float
    {
        return $this->preco;
    }

    public function setPreco(float $preco): void
    {
        if (is_nan($preco) || $preco < self::PRECO_MINIMO || $preco > self::PRECO_MAXIMO) {
            throw DadosInvalidosException::campo(
                'preço',
                sprintf(
                    'deve estar entre %s e %s',
                    Util::moeda(self::PRECO_MINIMO),
                    Util::moeda(self::PRECO_MAXIMO)
                )
            );
        }
        $this->preco = Util::arredondar($preco);
    }

    public function getDescricao(): string
    {
        return $this->descricao;
    }

    public function setDescricao(string $descricao): void
    {
        $descricao = Util::limparTexto($descricao);
        if (Util::tamanho($descricao) > self::TAMANHO_MAXIMO_DESCRICAO) {
            throw DadosInvalidosException::campo(
                'descrição',
                sprintf('deve ter no máximo %d caracteres', self::TAMANHO_MAXIMO_DESCRICAO)
            );
        }
        $this->descricao = $descricao;
    }

    public function isDisponivel(): bool
    {
        return $this->disponivel;
    }

    public function setDisponivel(bool $disponivel): void
    {
        $this->disponivel = $disponivel;
    }

    public static function getTotalInstancias(): int
    {
        return self::$totalInstancias;
    }
}
