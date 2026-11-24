<?php
/**
 * Classe: DadosInvalidosException
 * Responsável: Erik Carvalho Nogueira (RA 2026106090008)
 * Descrição: exceção lançada quando um dado informado (formulário, JSON ou setter) não respeita as regras do sistema.
 */
declare(strict_types=1);

class DadosInvalidosException extends RestauranteException
{
    public static function campo(string $campo, string $motivo): self
    {
        return new self(sprintf('O campo "%s" %s.', $campo, $motivo));
    }

    public function getTitulo(): string
    {
        return 'Dados inválidos';
    }
}
