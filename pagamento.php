<?php
/**
 * Arquivo: pagamento.php
 * Responsável: Danilo Sales Coutinho (RA 2026106090020)
 * Descrição: tela de pagamento — simula a conta em cada forma (polimorfismo), cria o pagamento pela Factory e trata PagamentoInvalidoException.
 */
require __DIR__ . '/includes/bootstrap.php';

$restaurante = restaurante();
$numero = (int) consulta('pedido', '0');

try {
    $pedido = $restaurante->buscarPedido($numero);
} catch (PedidoInvalidoException $excecao) {
    avisar('erro', $excecao->getTitulo() . ': ' . $excecao->getMessage());
    redirecionar('pedidos.php');
}

if (ehPost()) {
    $pago = executarAcao(static function () use ($restaurante, $numero): void {
        $pagamento = PagamentoFactory::criar(post('forma'), $_POST);
        $restaurante->pagarPedido($numero, $pagamento);
    }, 'Pagamento aprovado! A mesa foi liberada.');

    $aindaPodePagar = !$pago && $pedido->getStatus() === StatusPedido::ENTREGUE;
    redirecionar($aindaPodePagar ? 'pagamento.php?pedido=' . $numero : 'pedido.php?numero=' . $numero);
}

if ($pedido->getStatus() !== StatusPedido::ENTREGUE) {
    $motivo = PedidoInvalidoException::naoPodePagar($numero, $pedido->getStatus());
    avisar('erro', $motivo->getTitulo() . ': ' . $motivo->getMessage());
    redirecionar('pedido.php?numero=' . $numero);
}

$conta = $pedido->getTotal();

$simulacoes = [
    new PagamentoDinheiro(),
    new PagamentoPix(),
    new PagamentoCartao(PagamentoCartao::DEBITO),
    new PagamentoCartao(PagamentoCartao::CREDITO, 1),
    new PagamentoCartao(PagamentoCartao::CREDITO, 2),
    new PagamentoCartao(PagamentoCartao::CREDITO, 3),
];

cabecalho('Pagamento da comanda nº ' . $pedido->getNumero(), 'pedidos.php');
?>
    <p class="pequeno"><a href="pedido.php?numero=<?= e($pedido->getNumero()) ?>">← Voltar à comanda nº <?= e($pedido->getNumero()) ?></a></p>
    <h1>Fechar a conta — comanda nº <?= e($pedido->getNumero()) ?></h1>
    <p class="subtitulo">Mesa <?= e($pedido->getNumeroMesa()) ?> · conta de <?= e(moeda($conta)) ?>
        (subtotal <?= e(moeda($pedido->getSubtotal())) ?> + taxa de serviço de <?= e(Util::percentual(Pedido::TAXA_SERVICO)) ?>)</p>

    <div class="pagamento-grid">
        <?= cupomDaConta($pedido) ?>

        <div class="pagamento-forma">
            <h2>Quanto fica em cada forma de pagamento</h2>
            <div class="tabela-rolagem">
                <table class="tabela">
                    <thead><tr><th>Forma</th><th>Regra</th><th class="num">Cobrança</th></tr></thead>
                    <tbody>
                    <?php foreach ($simulacoes as $forma): ?>
                        <tr>
                            <td><strong><?= e($forma->getNome()) ?></strong></td>
                            <td><?= e($forma->getRegra()) ?></td>
                            <td class="num"><?= e($forma->descreverCobranca($conta)) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <section class="cartao mt">
                <h2>Receber pagamento</h2>
                <form method="post" action="pagamento.php?pedido=<?= e($pedido->getNumero()) ?>">
                    <div class="campo">
                        <label for="forma">Forma de pagamento</label>
                        <select id="forma" name="forma" data-alternar="forma-pagamento">
                            <?php foreach (PagamentoFactory::tiposDisponiveis() as $valor => $rotulo): ?>
                                <option value="<?= e($valor) ?>"><?= e($rotulo) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="campo" data-grupo="forma-pagamento" data-valor="dinheiro">
                        <label for="valor_recebido">Valor recebido (R$)</label>
                        <input type="text" id="valor_recebido" name="valor_recebido" inputmode="decimal" value="<?= e(number_format($conta, 2, ',', '')) ?>">
                    </div>

                    <div class="form-grade" data-grupo="forma-pagamento" data-valor="cartao">
                        <div class="campo">
                            <label for="modalidade">Modalidade</label>
                            <select id="modalidade" name="modalidade">
                                <option value="<?= e(PagamentoCartao::CREDITO) ?>">Crédito</option>
                                <option value="<?= e(PagamentoCartao::DEBITO) ?>">Débito</option>
                            </select>
                        </div>
                        <div class="campo">
                            <label for="parcelas">Parcelas (só crédito)</label>
                            <select id="parcelas" name="parcelas">
                                <?php for ($parcelas = 1; $parcelas <= PagamentoCartao::MAX_PARCELAS; $parcelas++): ?>
                                    <option value="<?= e($parcelas) ?>"><?= e($parcelas) ?>x</option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>

                    <p class="pequeno" data-grupo="forma-pagamento" data-valor="pix">Chave Pix do restaurante (exemplo): <span class="codigo"><?= e(Config::CHAVE_PIX) ?></span></p>

                    <button type="submit">Confirmar pagamento</button>
                </form>
            </section>
        </div>
    </div>
<?php rodape(); ?>
