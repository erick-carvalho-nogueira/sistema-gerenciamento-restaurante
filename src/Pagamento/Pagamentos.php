<?php

/**
 * Classe: Pagamento
 * Responsável: Danilo Sales Coutinho (RA 2026106090020)
 * Descrição: classe definida para compor os diferentes métodos de pagamento.
 */

declare(strict_types=1);

abstract class Pagamentos {

    protected abstract float $valorConta;
    protected abstract float $referencia;
    protected abstract function calcularvalorcobrado(float $valorConta);
    protected abstract function processar(float $valorConta, float $referencia);
}

class PagamentoBoleto extends Pagamentos{

    protected float $valorConta;
    protected float $referencia;

    public function calcularvalorcobrado(float $valorConta): float{
        return $valorConta;
    }

    public function processar(float $valorConta, float $referencia){
        echo "Pagamento realizado com boleto";
    }
    
}

class PagamentoPix extends Pagamentos{

    protected float $valorConta;
    protected float $referencia;
    private const DESCONTO = 10/100;
    
    public function calcularvalorcobrado(float $valorConta): float{
        $this->referencia = $this->valorConta * (self::DESCONTO);
        $this->valorConta = $this->referencia;
        return $this->valorConta;
    }   
    
    public function processar(float $valorConta, float $referencia){
        echo "Pagamento realizado com pix";
    }


}

class PagamentoCartao extends Pagamentos{

    protected float $valorConta;
    protected float $referencia;

    private const MAX_PARCELAS = 12;
    private const JUROS_POR_PARCELA = 5/100;
    
    public function calcularvalorcobrado(float $valorConta): float{
        return $valorConta;
    }    
    
    public function processar(float $valorConta, float $referencia){
        echo "Pagamento realizado com cartão";
    }

}
