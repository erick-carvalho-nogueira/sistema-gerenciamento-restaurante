<?php

/**
 * Classe: Pagamento
 * Responsável: Danilo Sales Coutinho (RA 2026106090020)
 * Descrição: arquivo feito para um pagamento para ser usufruído, escolhendo o modo de pagamento a partir de um Factory de criação de uma instância (Fábrica).
 */

declare(strict_types=1);

final class PagamentoFactory
{
    private function __construct(){
        // A formação de um construct impede a criação de instâncias da classe
    }

    public static function criar($escolhaPagamento): Pagamentos
    {
        return match ($escolhaPagamento) {
            'Debito' => new PagamentoBoleto(),
            'Cartao' => new PagamentoCartao(),
            'Pix' => new PagamentoPix(),
            default => throw new InvalidArgumentException("Método de pagamento inválido: $escolhaPagamento"),
        };
        /* O método descrito acima serve para criar uma instância de pagamento (Boleto, Cartão ou Pix) com base na escolha do usuário,
        visto que, para funcionar, é necessário que utilizar uma das subclasses de Pagamentos. 
        Se a escolha não corresponde a nenhuma  delas, a exceção - que levará a um erro - será lançada.
        */
        }

}

?>