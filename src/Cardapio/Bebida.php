<?php
/**
 * Classe: Bebida
 * Responsável: Erik Carvalho Nogueira (RA 2026106090008)
 * Descrição: subclasse de ItemCardapio para as bebidas do bar; dá desconto progressivo a partir de 6 unidades.
 */
declare(strict_types=1);

class Bebida extends ItemCardapio
{
    public const QTD_ATACADO = 6;
    public const DESCONTO_ATACADO = 0.10;
    public const TEMPO_PREPARO = 3;
    public const VOLUME_MINIMO_ML = 50;
    public const VOLUME_MAXIMO_ML = 3000;

    private int $volumeMl = 300;
    private bool $alcoolica = false;
    private bool $gelada = true;

    public function __construct(
        string $nome,
        float $preco,
        int $volumeMl = 300,
        bool $alcoolica = false,
        bool $gelada = true,
        string $descricao = '',
        ?int $id = null
    ) {
        parent::__construct($nome, $preco, $descricao, $id);
        $this->setVolumeMl($volumeMl);
        $this->setAlcoolica($alcoolica);
        $this->setGelada($gelada);
    }

    public static function deArray(array $dados): self
    {
        $bebida = new self(
            Util::limparTexto($dados['nome'] ?? ''),
            Util::paraFloat($dados['preco'] ?? '', 'preço'),
            Util::paraInt($dados['volume_ml'] ?? 300, 'volume (ml)'),
            Util::paraBool($dados['alcoolica'] ?? false),
            Util::paraBool($dados['gelada'] ?? true),
            Util::limparTexto($dados['descricao'] ?? ''),
            self::lerId($dados)
        );
        self::aplicarDadosComuns($bebida, $dados);
        return $bebida;
    }

    public function getTipo(): string
    {
        return 'bebida';
    }

    public function getCategoria(): string
    {
        return $this->alcoolica ? 'Bebida alcoólica' : 'Bebida';
    }

    public function getSetorPreparo(): string
    {
        return 'Bar';
    }

    public function getTempoPreparoMinutos(): int
    {
        return self::TEMPO_PREPARO;
    }

    public function calcularPreco(int $quantidade): float
    {
        $this->exigirQuantidadeValida($quantidade);
        $total = $this->preco * $quantidade;
        if ($quantidade >= self::QTD_ATACADO) {
            $total *= (1 - self::DESCONTO_ATACADO);
        }
        return Util::arredondar($total);
    }

    public function getRegraDePreco(): string
    {
        return sprintf(
            '%s de desconto a partir de %d unidades.',
            Util::percentual(self::DESCONTO_ATACADO),
            self::QTD_ATACADO
        );
    }

    public function getDetalhes(): string
    {
        return sprintf('%d ml, %s', $this->volumeMl, $this->gelada ? 'gelada' : 'natural');
    }

    protected function dadosEspecificos(): array
    {
        return [
            'volume_ml' => $this->volumeMl,
            'alcoolica' => $this->alcoolica,
            'gelada' => $this->gelada,
        ];
    }

    public function getVolumeMl(): int
    {
        return $this->volumeMl;
    }

    public function setVolumeMl(int $volumeMl): void
    {
        if ($volumeMl < self::VOLUME_MINIMO_ML || $volumeMl > self::VOLUME_MAXIMO_ML) {
            throw DadosInvalidosException::campo(
                'volume (ml)',
                sprintf('deve estar entre %d e %d', self::VOLUME_MINIMO_ML, self::VOLUME_MAXIMO_ML)
            );
        }
        $this->volumeMl = $volumeMl;
    }

    public function isAlcoolica(): bool
    {
        return $this->alcoolica;
    }

    public function setAlcoolica(bool $alcoolica): void
    {
        $this->alcoolica = $alcoolica;
    }

    public function isGelada(): bool
    {
        return $this->gelada;
    }

    public function setGelada(bool $gelada): void
    {
        $this->gelada = $gelada;
    }
}
