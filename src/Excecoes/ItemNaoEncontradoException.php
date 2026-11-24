<?php
/**
 * Classe: ItemNaoEncontradoException
 * Responsável: Erik Carvalho Nogueira (RA 2026106090008)
 * Descrição: exceção lançada ao buscar ou remover um item do cardápio cujo código não existe.
 */
declare(strict_types=1);

class ItemNaoEncontradoException extends RestauranteException
{
    public static function porId(int $id): self
    {
        return new self(sprintf('Não existe item no cardápio com o código #%d.', $id));
    }

    public function getTitulo(): string
    {
        return 'Item não encontrado';
    }
}
