<?php
/**
 * Classe: DadosExemplo
 * Responsável: Erik Carvalho Nogueira (RA 2026106090008)
 * Descrição: povoa o restaurante com cardápio, equipe, mesas e alguns pedidos de exemplo para o sistema já subir com o que mostrar.
 */
declare(strict_types=1);

final class DadosExemplo
{
    private function __construct()
    {
    }

    public static function popular(Restaurante $restaurante): void
    {
        self::criarCardapio($restaurante->getCardapio());
        self::criarEquipe($restaurante->getEquipe());
        self::criarMesas($restaurante);
        self::criarPedidos($restaurante);
    }

    private static function criarCardapio(Cardapio $cardapio): void
    {
        $cardapio->adicionar(new Prato('Feijoada Completa', 59.90, 35, 2, false, 'Feijão preto com carnes selecionadas, couve, farofa e laranja.'));
        $cardapio->adicionar(new Prato('Tambaqui na Brasa', 79.90, 45, 3, false, 'Costela de tambaqui assada na brasa, com baião de dois e vinagrete.'));
        $cardapio->adicionar(new Prato('Filé à Parmegiana', 49.90, 25, 1, false, 'Filé empanado com molho de tomate e queijo gratinado, arroz e batata frita.'));
        $cardapio->adicionar(new Prato('Risoto de Cogumelos', 44.90, 30, 1, true, 'Arroz arbóreo cremoso com mix de cogumelos frescos.'));
        $cardapio->adicionar(new Prato('Strogonoff de Frango', 38.90, 20, 1, false, 'Acompanha arroz branco e batata palha.'));

        $cardapio->adicionar(new Bebida('Suco de Cupuaçu', 12.00, 300, false, true, 'Polpa natural, sem açúcar adicionado.'));
        $cardapio->adicionar(new Bebida('Guaraná Lata', 6.50, 350, false, true));
        $cardapio->adicionar(new Bebida('Água Mineral', 5.00, 500, false, true));
        $cardapio->adicionar(new Bebida('Cerveja Long Neck', 12.00, 355, true, true));
        $cardapio->adicionar(new Bebida('Caipirinha de Limão', 18.00, 300, true, true, 'Cachaça, limão tahiti e açúcar.'));

        $cardapio->adicionar(new Sobremesa('Pudim de Leite', 14.00, 320, true, 'Pudim tradicional com calda de caramelo.'));
        $cardapio->adicionar(new Sobremesa('Mousse de Maracujá', 12.00, 250, true));
        $cardapio->adicionar(new Sobremesa('Sorvete de Açaí', 16.00, 280, true, 'Duas bolas com granola e banana.'));
    }

    private static function criarEquipe(Equipe $equipe): void
    {
        $equipe->contratar(new Garcom('Carlos Henrique Souza', 1800.00, '2024-03-04'));
        $equipe->contratar(new Garcom('Mariana Alves Lima', 1800.00, '2024-08-12'));
        $equipe->contratar(new Cozinheiro('Joaquim Pereira Matos', 2600.00, 'Culinária regional', '2023-05-02'));
        $equipe->contratar(new Cozinheiro('Ana Beatriz Costa', 2400.00, 'Confeitaria', '2024-01-15'));
        $equipe->contratar(new Gerente('Roberto Mendes Dias', 4500.00, Gerente::META_PADRAO, '2022-09-01'));
    }

    private static function criarMesas(Restaurante $restaurante): void
    {
        foreach ([2, 2, 4, 4, 4, 6, 6, 8] as $indice => $capacidade) {
            $restaurante->adicionarMesa(new Mesa($indice + 1, $capacidade));
        }
    }

    private static function criarPedidos(Restaurante $restaurante): void
    {
        $cardapio = $restaurante->getCardapio();

        $pedido1 = $restaurante->abrirPedido(1, 2, 1);
        $pedido1->adicionarItem($cardapio->buscarPorId(1), 1);
        $pedido1->adicionarItem($cardapio->buscarPorId(6), 2);
        $pedido1->adicionarItem($cardapio->buscarPorId(11), 3);
        self::entregar($pedido1);
        $restaurante->pagarPedido($pedido1->getNumero(), new PagamentoPix());

        $pedido2 = $restaurante->abrirPedido(3, 4, 2);
        $pedido2->adicionarItem($cardapio->buscarPorId(2), 1);
        $pedido2->adicionarItem($cardapio->buscarPorId(3), 1);
        $pedido2->adicionarItem($cardapio->buscarPorId(9), 6);
        self::entregar($pedido2);
        $restaurante->pagarPedido($pedido2->getNumero(), new PagamentoDinheiro(250.00));

        $pedido3 = $restaurante->abrirPedido(4, 3, 1);
        $pedido3->adicionarItem($cardapio->buscarPorId(5), 2);
        $pedido3->adicionarItem($cardapio->buscarPorId(7), 3);
        $pedido3->avancarStatus();

        $pedido4 = $restaurante->abrirPedido(5, 2, 2);
        $pedido4->adicionarItem($cardapio->buscarPorId(4), 1, 'sem pimenta');
        $pedido4->adicionarItem($cardapio->buscarPorId(8), 2);

        $restaurante->reservarMesa(6, 'Família Oliveira');
    }

    private static function entregar(Pedido $pedido): void
    {
        $pedido->avancarStatus();
        $pedido->avancarStatus();
        $pedido->avancarStatus();
    }
}
