<?php
/**
 * Classe: MesaIndisponivelException
 * Responsável: Danilo Sales Coutinho (RA 2026106090020)
 * Descrição: exceção lançada quando uma mesa não pode ser usada (inexistente, ocupada, lotação excedida, com pedido aberto).
 */
declare(strict_types=1);

class MesaIndisponivelException extends RestauranteException
{
    public static function inexistente(int $numero): self
    {
        return new self(sprintf('A mesa %d não existe.', $numero));
    }

    public static function ocupada(int $numero): self
    {
        return new self(sprintf('A mesa %d já está ocupada.', $numero));
    }

    public static function naoEstaLivre(int $numero, string $situacao): self
    {
        return new self(sprintf('A mesa %d não está livre (situação atual: %s).', $numero, $situacao));
    }

    public static function capacidadeExcedida(int $numero, int $capacidade, int $pessoas): self
    {
        return new self(sprintf(
            'A mesa %d comporta no máximo %d pessoas e o grupo tem %d.',
            $numero,
            $capacidade,
            $pessoas
        ));
    }

    public static function comPedidoAberto(int $numero): self
    {
        return new self(sprintf(
            'A mesa %d tem um pedido em andamento: pague ou cancele o pedido para liberá-la.',
            $numero
        ));
    }

    public function getTitulo(): string
    {
        return 'Mesa indisponível';
    }
}
