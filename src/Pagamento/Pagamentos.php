<?php

/**
 * Classe: Pagamento
 * Responsável: Danilo Sales Coutinho (RA 2026106090020)
 * Descrição: classe definida para compor os diferentes métodos de pagamento.
 */

declare(strict_types=1);
include_once __DIR__ . '/Excecoes/PagamentoInvalidoException.php';
//Com o include_once, estou utilizando o método "dinheiroInsuficiente" e o "pagamentoFormaDesc", apesar de não saber se vai funcionar

abstract class Pagamentos {

    protected abstract float $valorConta;
    protected abstract float $referencia;
    //protected abstract $escolhapagamento = ["Pix","Cartão", "Débito"];

    //A escolha de criar um array para cada escolha de pagamento se deriva do fato de eu preferir não fazer uma estrutura condicional após todo o código das classes, pois não sei muito bem como poderia juntar toda a causa deles em uma só

    protected abstract function calcularvalorcobrado($valorConta)/*{

        Tentativa de implementar o método "pagamentoFormaDesc" no código.

        if(
        Dinheiro::(Possível número de pagamento escolhido aqui) != $numpagamento[0] &&
        Dinheiro::(Possível número de pagamento escolhido aqui) != $numpagamento[1] &&
        Dinheiro::(Possível número de pagamento escolhido aqui) != $numpagamento[2])
        {
            echo PagamentoInvalidoException::pagamentoFormaDesc($numpagamento);
        }
    }*/;
    protected abstract function processar($valorConta, $referencia);

}

class PagamentoBoleto extends Pagamentos{

    protected float $valorConta;
    protected float $referencia;
    //private $escolhapagamento[0];

    public function calcularvalorcobrado($valorConta): float{
        /*if($valorconta > Dinheiro::(valor integral do dinheiro registrado aqui)){
            echo PagamentoInvalidoException::dinheiroInsuficiente($valorconta);
            return 0;
        }*/
        return $valorConta;
    }

    public function processar($valorConta, $referencia){
        echo "Pagamento realizado com boleto";
    }
    
}

class PagamentoPix extends Pagamentos{

    protected float $valorConta;
    protected float $referencia;
    //private $escolhapagamento[1];
    private const DESCONTO = 5/100;
    private float $temp;
    
    public function calcularvalorcobrado($valorConta): float{
        $temp = $this->valorConta * (self::DESCONTO);
        $this->valorConta -= $temp;
        /*if($valorconta > Dinheiro::(valor integral do dinheiro registrado aqui)){
            echo PagamentoInvalidoException::dinheiroInsuficiente($valorconta);
            return 0;
        }*/
        return $this->valorConta;
    }   
    
    public function processar($valorConta, $referencia){
        echo "Pagamento realizado com pix";
    }

}

class PagamentoCartao extends Pagamentos{

    protected float $valorConta;
    protected float $referencia;
    //private $escolhapagamento[2];
    private float $temp;
    private const MAX_PARCELAS = 3;
    private const JUROS_POR_PARCELA = 2/100;
    
    public function calcularvalorcobrado($valorConta): float{
        return $valorConta;
    }    
    
    public function processar($valorConta, $referencia){
        echo "Pagamento realizado com cartão";
    }

}

?>