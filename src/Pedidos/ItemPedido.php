<?php
/**
 * Classe: ItemPedido
 * Responsável: Erik Carvalho Nogueira (RA 2026106090008)
 * Descrição: uma linha do pedido (item do cardápio + quantidade + observação); guarda uma cópia do item para preservar o preço da época.
 */
declare(strict_types=1);

class ItemPedido implements Persistivel
{
    public const TAMANHO_MAXIMO_OBSERVACAO = 80;

    private ItemCardapio $item;
    private int $quantidade = 1;
    private string $observacao = '';

    public function __construct(ItemCardapio $item, int $quantidade, string $observacao = '')
    {
        $this->item = clone $item;
        $this->setQuantidade($quantidade);
        $this->setObservacao($observacao);
    }

    public function getSubtotal(): float
    {
        return $this->item->calcularPreco($this->quantidade);
    }

    public function getItem(): ItemCardapio
    {
        return $this->item;
    }

    public function getQuantidade(): int
    {
        return $this->quantidade;
    }

    public function setQuantidade(int $quantidade): void
    {
        if ($quantidade < 1 || $quantidade > Pedido::MAX_QUANTIDADE_POR_ITEM) {
            throw PedidoInvalidoException::quantidadeInvalida($quantidade, Pedido::MAX_QUANTIDADE_POR_ITEM);
        }
        $this->quantidade = $quantidade;
    }

    public function getObservacao(): string
    {
        return $this->observacao;
    }

    public function setObservacao(string $observacao): void
    {
        $observacao = Util::limparTexto($observacao);
        if (Util::tamanho($observacao) > self::TAMANHO_MAXIMO_OBSERVACAO) {
            throw DadosInvalidosException::campo(
                'observação',
                sprintf('deve ter no máximo %d caracteres', self::TAMANHO_MAXIMO_OBSERVACAO)
            );
        }
        $this->observacao = $observacao;
    }

    public function toArray(): array
    {
        return [
            'item' => $this->item->toArray(),
            'quantidade' => $this->quantidade,
            'observacao' => $this->observacao,
        ];
    }

    public static function deArray(array $dados): self
    {
        $dadosItem = $dados['item'] ?? null;
        if (!is_array($dadosItem)) {
            throw DadosInvalidosException::campo('item do pedido', 'está ausente nos dados gravados');
        }
        return new self(
            ItemCardapioFactory::criar((string) ($dadosItem['tipo'] ?? ''), $dadosItem),
            Util::paraInt($dados['quantidade'] ?? '', 'quantidade'),
            Util::limparTexto($dados['observacao'] ?? '')
        );
    }
}
