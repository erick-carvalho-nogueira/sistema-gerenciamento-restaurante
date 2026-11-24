<?php
/**
 * Classe: RestauranteException
 * Responsável: Erik Carvalho Nogueira (RA 2026106090008)
 * Descrição: exceção-base do sistema (extends Exception); todas as exceções de regra de negócio herdam dela.
 */
declare(strict_types=1);

class RestauranteException extends Exception
{
    public function getTitulo(): string
    {
        return 'Erro no sistema';
    }
}
