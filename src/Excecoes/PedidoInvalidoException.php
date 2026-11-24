<?php
/**
 * Classe: PedidoInvalidoException
 * Responsável: Erik Carvalho Nogueira (RA 2026106090008)
 * Descrição: exceção lançada quando uma operação não é permitida para o pedido (inexistente, finalizado, sem itens, limite excedido).
 */
declare(strict_types=1);

class PedidoInvalidoException extends RestauranteException
{
    public static function naoEncontrado(int $numero): self
    {
        return new self(sprintf('Não existe o pedido nº %d.', $numero));
    }

    public static function semItens(): self
    {
        return new self('O pedido ainda não tem nenhum item.');
    }

    public static function statusFinal(int $numero, string $status): self
    {
        return new self(sprintf(
            'O pedido nº %d está %s e não pode mais ser alterado.',
            $numero,
            strtolower(StatusPedido::rotulo($status))
        ));
    }

    public static function itemIndisponivel(string $nome): self
    {
        return new self(sprintf('"%s" está indisponível no momento.', $nome));
    }

    public static function quantidadeInvalida(int $quantidade, int $maximo): self
    {
        return new self(sprintf('Quantidade inválida (%d): informe um valor de 1 a %d.', $quantidade, $maximo));
    }

    public static function limiteDeItens(int $maximo): self
    {
        return new self(sprintf('Um pedido aceita no máximo %d itens diferentes.', $maximo));
    }

    public static function itemInexistente(int $posicao): self
    {
        return new self(sprintf('O pedido não possui item na posição %d.', $posicao));
    }

    public static function itensJaEnviados(): self
    {
        return new self('Itens só podem ser removidos enquanto o pedido está aberto, antes de ir para a cozinha.');
    }

    public static function semProximaEtapa(string $status): self
    {
        if ($status === StatusPedido::ENTREGUE) {
            return new self('O pedido já foi entregue: registre o pagamento para fechar a conta.');
        }
        return new self(sprintf(
            'O pedido está %s e não tem próxima etapa.',
            strtolower(StatusPedido::rotulo($status))
        ));
    }

    public static function naoPodePagar(int $numero, string $status): self
    {
        return new self(sprintf(
            'O pedido nº %d está %s: só é possível pagar pedidos já entregues.',
            $numero,
            strtolower(StatusPedido::rotulo($status))
        ));
    }

    public function getTitulo(): string
    {
        return 'Pedido inválido';
    }
}
