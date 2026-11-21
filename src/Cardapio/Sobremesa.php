<?php
/**
 * Classe: Sobremesa
 * Responsável: Erik Carvalho Nogueira (RA 2026106090008)
 * Descrição: subclasse de ItemCardapio para as sobremesas da confeitaria; aplica a promoção "leve 3, pague 2".
 */
declare(strict_types=1);

class Sobremesa extends ItemCardapio
{
    public const LEVE = 3;
    public const PAGUE = 2;
    public const TEMPO_PREPARO = 8;
    public const CALORIAS_MAXIMAS = 3000;

    private int $calorias = 250;
    private bool $servidaGelada = true;

    public function __construct(
        string $nome,
        float $preco,
        int $calorias = 250,
        bool $servidaGelada = true,
        string $descricao = '',
        ?int $id = null
    ) {
        parent::__construct($nome, $preco, $descricao, $id);
        $this->setCalorias($calorias);
        $this->setServidaGelada($servidaGelada);
    }

    /** Monta uma Sobremesa a partir de um array (formulário ou JSON). Usado pela ItemCardapioFactory. */
    public static function deArray(array $dados): self
    {
        $sobremesa = new self(
            Util::limparTexto($dados['nome'] ?? ''),
            Util::paraFloat($dados['preco'] ?? '', 'preço'),
            Util::paraInt($dados['calorias'] ?? 250, 'calorias'),
            Util::paraBool($dados['servida_gelada'] ?? true),
            Util::limparTexto($dados['descricao'] ?? ''),
            self::lerId($dados)
        );
        self::aplicarDadosComuns($sobremesa, $dados);
        return $sobremesa;
    }

    // implementação do contrato de ItemCardapio

    public function getTipo(): string
    {
        return 'sobremesa';
    }

    public function getCategoria(): string
    {
        return 'Sobremesa';
    }

    public function getSetorPreparo(): string
    {
        return 'Confeitaria';
    }

    public function getTempoPreparoMinutos(): int
    {
        return self::TEMPO_PREPARO;
    }

    /** Sobremesa: a cada LEVE unidades, (LEVE - PAGUE) saem de graça (ex.: leve 3, pague 2). */
    public function calcularPreco(int $quantidade): float
    {
        $this->exigirQuantidadeValida($quantidade);
        $gratis = intdiv($quantidade, self::LEVE) * (self::LEVE - self::PAGUE);
        return Util::arredondar($this->preco * ($quantidade - $gratis));
    }

    public function getRegraDePreco(): string
    {
        return sprintf('Leve %d, pague %d.', self::LEVE, self::PAGUE);
    }

    public function getDetalhes(): string
    {
        return sprintf('%d kcal, %s', $this->calorias, $this->servidaGelada ? 'servida gelada' : 'servida natural');
    }

    protected function dadosEspecificos(): array
    {
        return [
            'calorias' => $this->calorias,
            'servida_gelada' => $this->servidaGelada,
        ];
    }

    // getters e setters próprios

    public function getCalorias(): int
    {
        return $this->calorias;
    }

    public function setCalorias(int $calorias): void
    {
        if ($calorias < 0 || $calorias > self::CALORIAS_MAXIMAS) {
            throw DadosInvalidosException::campo(
                'calorias',
                sprintf('devem estar entre 0 e %d', self::CALORIAS_MAXIMAS)
            );
        }
        $this->calorias = $calorias;
    }

    public function isServidaGelada(): bool
    {
        return $this->servidaGelada;
    }

    public function setServidaGelada(bool $servidaGelada): void
    {
        $this->servidaGelada = $servidaGelada;
    }
}
