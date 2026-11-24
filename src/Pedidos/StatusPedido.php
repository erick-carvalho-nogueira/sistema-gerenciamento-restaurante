<?php
/**
 * Classe: StatusPedido
 * Responsável: Erik Carvalho Nogueira (RA 2026106090008)
 * Descrição: constantes e regras do fluxo de um pedido (aberto → em preparo → pronto → entregue → pago, ou cancelado).
 */
declare(strict_types=1);

final class StatusPedido
{
    public const ABERTO = 'aberto';
    public const EM_PREPARO = 'em_preparo';
    public const PRONTO = 'pronto';
    public const ENTREGUE = 'entregue';
    public const PAGO = 'pago';
    public const CANCELADO = 'cancelado';

    private const ROTULOS = [
        self::ABERTO => 'Aberto',
        self::EM_PREPARO => 'Em preparo',
        self::PRONTO => 'Pronto',
        self::ENTREGUE => 'Entregue',
        self::PAGO => 'Pago',
        self::CANCELADO => 'Cancelado',
    ];

    private const FLUXO = [
        self::ABERTO => self::EM_PREPARO,
        self::EM_PREPARO => self::PRONTO,
        self::PRONTO => self::ENTREGUE,
    ];

    private function __construct()
    {
    }

    public static function todos(): array
    {
        return array_keys(self::ROTULOS);
    }

    public static function etapasDoFluxo(): array
    {
        return [self::ABERTO, self::EM_PREPARO, self::PRONTO, self::ENTREGUE, self::PAGO];
    }

    public static function existe(string $status): bool
    {
        return isset(self::ROTULOS[$status]);
    }

    public static function rotulo(string $status): string
    {
        return self::ROTULOS[$status] ?? $status;
    }

    public static function proximo(string $status): ?string
    {
        return self::FLUXO[$status] ?? null;
    }

    public static function estaFinalizado(string $status): bool
    {
        return $status === self::PAGO || $status === self::CANCELADO;
    }
}
