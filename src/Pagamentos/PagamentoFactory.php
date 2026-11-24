<?php
/**
 * Classe: PagamentoFactory
 * Responsável: Danilo Sales Coutinho (RA 2026106090020)
 * Descrição: padrão Factory — transforma o texto da forma de pagamento ("dinheiro", "pix", "cartao") no objeto da subclasse correta.
 */
declare(strict_types=1);

final class PagamentoFactory
{
    private function __construct()
    {
    }

    public static function tiposDisponiveis(): array
    {
        return [
            'dinheiro' => 'Dinheiro',
            'pix' => 'Pix',
            'cartao' => 'Cartão',
        ];
    }

    public static function criar(string $tipo, array $dados = []): Pagamento
    {
        switch (strtolower(trim($tipo))) {
            case 'dinheiro':
                return PagamentoDinheiro::deArray($dados);
            case 'pix':
                return new PagamentoPix();
            case 'cartao':
                return PagamentoCartao::deArray($dados);
            default:
                throw PagamentoInvalidoException::formaDesconhecida($tipo);
        }
    }

    public static function restaurar(array $dados): Pagamento
    {
        $pagamento = self::criar((string) ($dados['tipo'] ?? ''), $dados);
        $pagamento->restaurarEstado($dados);
        return $pagamento;
    }
}
