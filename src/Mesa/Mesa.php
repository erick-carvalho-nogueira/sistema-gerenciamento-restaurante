<?php

/**
 * Classe: Pagamento
 * Responsável: Danilo Sales Coutinho (RA 2026106090020)
 * Descrição: classe definida para compor as mesas disponibilizadas aos clientes.
 */

declare(strict_types=1);
include_once __DIR__ . '/Excecoes/MesaIndisponivelException.php';

class Mesa{

    protected const int CAPACIDADE_MAXIMA = 6;
    protected int $numeroMesa = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10];
    protected int $clientesOcupadosNaMesa = 0;
    protected int $clientesReservados = 0;

    public function ocupar($numeroMesa, $clientesReservados, $clientesOcupadosNaMesa)
    {
        if ($clientesReservados == self::CAPACIDADE_MAXIMA) {
            echo MesaIndisponivelException::mesaOcupada($numeroMesa);
        } else if ($clientesReservados > self::CAPACIDADE_MAXIMA) {
            echo MesaIndisponivelException::pessoasExcedemCapacidade($numeroMesa, self::CAPACIDADE_MAXIMA);
        } else {
            $clientesOcupadosNaMesa += $this->clientesOcupadosNaMesa;
        }
    }

    public function reservar($clientesReservados, $numeroMesa)
    {
        for ($i = 0; $i < count($numeroMesa); $i++) {
            if ($this->numeroMesa == $numeroMesa) {
                break;
            } else {
                echo MesaIndisponivelException::mesaNaoExiste($numeroMesa);
            }
        }

        if ($clientesReservados != 0) {
            echo MesaIndisponivelException::mesaNaoVazia($numeroMesa);
        } else {
            $clientesReservados += $this->clientesReservados;
        }

    }

}

?>