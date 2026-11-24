<?php
/**
 * Classe: FuncionarioFactory
 * Responsável: Felipe Ferreira Rosas (RA 2026106090004)
 * Descrição: padrão Factory — transforma o texto do cargo ("garcom", "cozinheiro", "gerente") no objeto da subclasse correta.
 */
declare(strict_types=1);

final class FuncionarioFactory
{
    private function __construct()
    {
    }

    public static function tiposDisponiveis(): array
    {
        return [
            'garcom' => 'Garçom',
            'cozinheiro' => 'Cozinheiro',
            'gerente' => 'Gerente',
        ];
    }

    public static function criar(string $tipo, array $dados): Funcionario
    {
        switch (strtolower(trim($tipo))) {
            case 'garcom':
                return Garcom::deArray($dados);
            case 'cozinheiro':
                return Cozinheiro::deArray($dados);
            case 'gerente':
                return Gerente::deArray($dados);
            default:
                throw DadosInvalidosException::campo(
                    'cargo',
                    sprintf('não aceita "%s" (use garcom, cozinheiro ou gerente)', $tipo)
                );
        }
    }
}
