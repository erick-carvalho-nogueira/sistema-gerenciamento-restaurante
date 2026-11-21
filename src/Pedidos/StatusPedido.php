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

    /** Etapas que avancarStatus() percorre; de ENTREGUE em diante só o pagamento (ou o cancelamento) muda o status. */
    private const FLUXO = [
        self::ABERTO => self::EM_PREPARO,
        self::EM_PREPARO => self::PRONTO,
        self::PRONTO => self::ENTREGUE,
    ];

    /** Classe utilitária: só tem membros estáticos, por isso não pode ser instanciada. */
    private function __construct()
    {
    }

    /** @return string[] todos os status possíveis */
    public static function todos(): array
    {
        return array_keys(self::ROTULOS);
    }

    /** @return string[] etapas do fluxo normal, na ordem (usadas na barra de progresso) */
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

    /** Próximo status do fluxo, ou null quando não há (entregue, pago ou cancelado). */
    public static function proximo(string $status): ?string
    {
        return self::FLUXO[$status] ?? null;
    }

    /** Pedido pago ou cancelado não aceita mais nenhuma alteração. */
    public static function estaFinalizado(string $status): bool
    {
        return $status === self::PAGO || $status === self::CANCELADO;
    }
}
