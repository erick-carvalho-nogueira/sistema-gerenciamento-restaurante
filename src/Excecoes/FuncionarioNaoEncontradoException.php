<?php
/**
 * Classe: FuncionarioNaoEncontradoException
 * Responsável: Felipe Ferreira Rosas (RA 2026106090004)
 * Descrição: exceção lançada ao buscar/demitir um funcionário inexistente ou ao escolher como garçom quem não é garçom.
 */
declare(strict_types=1);

class FuncionarioNaoEncontradoException extends RestauranteException
{
    public static function porId(int $id): self
    {
        return new self(sprintf('Não existe funcionário com o código #%d.', $id));
    }

    public static function naoEGarcom(int $id): self
    {
        return new self(sprintf('O funcionário #%d não é garçom e não pode atender mesas.', $id));
    }

    public function getTitulo(): string
    {
        return 'Funcionário não encontrado';
    }
}
