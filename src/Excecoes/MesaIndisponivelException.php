<?php

/**
 * Classe: MesaIndisponivelException
 * Responsável: Danilo Sales Coutinho (RA 2026106090020)
 * Descrição: exceção lançada quando uma mesa sofre com algum tipo de inconveniência em sua reserva.
 */

declare(strict_types=1);

class MesaIndisponivelException extends RestauranteException
{
    /** Ex.: MesaIndisponivelException::mesaOcupada(5) → A mesa 5 está ocupada. */
    public static function mesaOcupada(int $numeroMesa): self
    {
        return new self(sprintf('A mesa %d está ocupada.', $numeroMesa));
    }

    public static function pessoasExcedemCapacidade(int $numeroMesa, int $capacidade): self
    {
        return new self(sprintf('A mesa %d não comporta mais pessoas. Capacidade máxima: %d.', $numeroMesa, $capacidade));
    }

    public static function mesaNaoExiste(int $numeroMesa): self
    {
        return new self(sprintf('A mesa %d não existe.', $numeroMesa));
    }

    public function getTitulo(): string
    {
        return 'Mesa indisponível';
    }
}
?>