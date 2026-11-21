<?php
/**
 * Classe: Prato
 * Responsável: Erik Carvalho Nogueira (RA 2026106090008)
 * Descrição: subclasse de ItemCardapio para os pratos da cozinha; cobra o preço cheio e informa tempo de preparo e porções.
 */
declare(strict_types=1);

class Prato extends ItemCardapio
{
    public const TEMPO_MINIMO = 5;
    public const TEMPO_MAXIMO = 120;
    public const MAX_PESSOAS = 10;

    private int $tempoPreparo = 20;
    private int $serve = 1;
    private bool $vegetariano = false;

    public function __construct(
        string $nome,
        float $preco,
        int $tempoPreparo = 20,
        int $serve = 1,
        bool $vegetariano = false,
        string $descricao = '',
        ?int $id = null
    ) {
        parent::__construct($nome, $preco, $descricao, $id);
        $this->setTempoPreparo($tempoPreparo);
        $this->setServe($serve);
        $this->setVegetariano($vegetariano);
    }

    /** Monta um Prato a partir de um array (formulário ou JSON). Usado pela ItemCardapioFactory. */
    public static function deArray(array $dados): self
    {
        $prato = new self(
            Util::limparTexto($dados['nome'] ?? ''),
            Util::paraFloat($dados['preco'] ?? '', 'preço'),
            Util::paraInt($dados['tempo_preparo'] ?? 20, 'tempo de preparo'),
            Util::paraInt($dados['serve'] ?? 1, 'serve (pessoas)'),
            Util::paraBool($dados['vegetariano'] ?? false),
            Util::limparTexto($dados['descricao'] ?? ''),
            self::lerId($dados)
        );
        self::aplicarDadosComuns($prato, $dados);
        return $prato;
    }

    // implementação do contrato de ItemCardapio

    public function getTipo(): string
    {
        return 'prato';
    }

    public function getCategoria(): string
    {
        return 'Prato';
    }

    public function getSetorPreparo(): string
    {
        return 'Cozinha';
    }

    public function getTempoPreparoMinutos(): int
    {
        return $this->tempoPreparo;
    }

    /** Prato: sem promoção, paga-se o preço cheio de cada unidade. */
    public function calcularPreco(int $quantidade): float
    {
        $this->exigirQuantidadeValida($quantidade);
        return Util::arredondar($this->preco * $quantidade);
    }

    public function getRegraDePreco(): string
    {
        return 'Preço cheio por unidade.';
    }

    public function getDetalhes(): string
    {
        $texto = sprintf(
            'serve %d %s, %d min de preparo',
            $this->serve,
            $this->serve === 1 ? 'pessoa' : 'pessoas',
            $this->tempoPreparo
        );
        return $this->vegetariano ? $texto . ', vegetariano' : $texto;
    }

    protected function dadosEspecificos(): array
    {
        return [
            'tempo_preparo' => $this->tempoPreparo,
            'serve' => $this->serve,
            'vegetariano' => $this->vegetariano,
        ];
    }

    // getters e setters próprios

    public function setTempoPreparo(int $minutos): void
    {
        if ($minutos < self::TEMPO_MINIMO || $minutos > self::TEMPO_MAXIMO) {
            throw DadosInvalidosException::campo(
                'tempo de preparo',
                sprintf('deve estar entre %d e %d minutos', self::TEMPO_MINIMO, self::TEMPO_MAXIMO)
            );
        }
        $this->tempoPreparo = $minutos;
    }

    public function getServe(): int
    {
        return $this->serve;
    }

    public function setServe(int $pessoas): void
    {
        if ($pessoas < 1 || $pessoas > self::MAX_PESSOAS) {
            throw DadosInvalidosException::campo(
                'serve (pessoas)',
                sprintf('deve estar entre 1 e %d', self::MAX_PESSOAS)
            );
        }
        $this->serve = $pessoas;
    }

    public function isVegetariano(): bool
    {
        return $this->vegetariano;
    }

    public function setVegetariano(bool $vegetariano): void
    {
        $this->vegetariano = $vegetariano;
    }
}
