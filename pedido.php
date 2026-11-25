<?php
/**
 * Arquivo: pedido.php
 * Responsável: Erik Carvalho Nogueira (RA 2026106090008)
 * Descrição: comanda (detalhe de um pedido) — itens, totais com taxa de serviço, andamento na cozinha, cancelamento e cupom do pagamento.
 */
require __DIR__ . '/includes/bootstrap.php';

$restaurante = restaurante();
$numero = (int) consulta('numero', '0');

try {
    $pedido = $restaurante->buscarPedido($numero);
} catch (PedidoInvalidoException $excecao) {
    avisar('erro', $excecao->getTitulo() . ': ' . $excecao->getMessage());
    redirecionar('pedidos.php');
}
$cardapio = $restaurante->getCardapio();

if (ehPost()) {
    switch (post('acao')) {
        case 'adicionar_item':
            executarAcao(static function () use ($cardapio, $pedido): void {
                $item = $cardapio->buscarPorId(Util::paraInt(post('item'), 'item'));
                $pedido->adicionarItem($item, Util::paraInt(post('quantidade', '1'), 'quantidade'), post('observacao'));
            }, 'Item adicionado ao pedido.');
            break;

        case 'remover_item':
            executarAcao(static function () use ($pedido): void {
                $pedido->removerItem(Util::paraInt(post('posicao'), 'posição'));
            }, 'Item removido do pedido.');
            break;

        case 'avancar':
            executarAcao(static function () use ($pedido): void {
                $pedido->avancarStatus();
            }, 'Pedido avançou para a próxima etapa.');
            break;

        case 'cancelar':
            executarAcao(static function () use ($restaurante, $numero): void {
                $restaurante->cancelarPedido($numero);
            }, 'Pedido cancelado e mesa liberada.');
            break;
    }
    redirecionar('pedido.php?numero=' . $numero);
}

$rotulosDeAvanco = [
    StatusPedido::ABERTO => 'Mandar pra chapa',
    StatusPedido::EM_PREPARO => 'Mandar pro balcão',
    StatusPedido::PRONTO => 'Levar pra mesa',
];
$etapas = StatusPedido::etapasDoFluxo();
$posicaoAtual = array_search($pedido->getStatus(), $etapas, true);
$itensDoPedido = $pedido->getItens();
$pagamento = $pedido->getPagamento();
$garcom = nomeDoGarcom($restaurante, $pedido->getIdGarcom());

cabecalho('Comanda nº ' . $pedido->getNumero(), 'pedidos.php');
?>
    <p class="pequeno"><a href="pedidos.php">← Todas as comandas</a></p>
    <h1>Comanda nº <?= e($pedido->getNumero()) ?> <?= seloStatus($pedido->getStatus()) ?></h1>
    <p class="subtitulo">
        Mesa <?= e($pedido->getNumeroMesa()) ?> · <?= e($pedido->getPessoas()) ?> pessoa(s) ·
        garçom: <?= e($garcom) ?> ·
        aberta em <?= e(Util::formatarDataHora($pedido->getCriadoEm())) ?>
    </p>

    <div class="comanda-grid">
        <section class="papel" aria-label="Itens da comanda">
            <div class="comanda-cab">
                <div class="comanda-titulo">Comanda <b>Nº <?= e($pedido->getNumero()) ?></b></div>
                <p>Mesa <?= e($pedido->getNumeroMesa()) ?> · <?= e($pedido->getPessoas()) ?> pessoa(s)</p>
                <p>Garçom: <?= e($garcom) ?></p>
            </div>

            <?php if (count($itensDoPedido) === 0): ?>
                <p class="vazio-comanda">Comanda em branco: anota o primeiro item aí do lado.</p>
            <?php else: ?>
                <ul class="itens-comanda">
                <?php foreach ($itensDoPedido as $posicao => $linha): $item = $linha->getItem(); ?>
                    <li>
                        <span class="q"><?= e($linha->getQuantidade()) ?> ×</span>
                        <strong class="nome-item"><?= e($item->getNome()) ?></strong>
                        <span class="pt"></span>
                        <span class="v"><?= e(moeda($linha->getSubtotal())) ?></span>
                        <?php if ($pedido->getStatus() === StatusPedido::ABERTO): ?>
                            <form method="post" action="pedido.php?numero=<?= e($pedido->getNumero()) ?>">
                                <input type="hidden" name="acao" value="remover_item">
                                <input type="hidden" name="posicao" value="<?= e($posicao) ?>">
                                <button type="submit" class="botao-perigo botao-pequeno" title="Riscar este item da comanda">riscar</button>
                            </form>
                        <?php endif; ?>
                        <span class="detalhe"><?= e(moeda($item->getPreco())) ?> cada · <?= e($item->getCategoria()) ?> · <?= e($item->getRegraDePreco()) ?></span>
                        <?php if ($linha->getObservacao() !== ''): ?><span class="obs-mao">↳ <?= e($linha->getObservacao()) ?></span><?php endif; ?>
                    </li>
                <?php endforeach; ?>
                </ul>

                <div class="somas">
                    <div><span>Subtotal</span><span><?= e(moeda($pedido->getSubtotal())) ?></span></div>
                    <div><span>Taxa de serviço (<?= e(Util::percentual(Pedido::TAXA_SERVICO)) ?>)</span><span><?= e(moeda($pedido->getTaxaServico())) ?></span></div>
                </div>
                <div class="total-carimbo"><small>TOTAL DA CONTA</small><?= e(moeda($pedido->getTotal())) ?></div>
                <p class="pequeno mt">
                    Preparo estimado: <strong><?= e($pedido->estimarTempoPreparoMinutos()) ?> min</strong> ·
                    setores envolvidos: <?= e(implode(', ', $pedido->getSetoresEnvolvidos())) ?>
                </p>
            <?php endif; ?>
        </section>

        <aside class="comanda-lado">
            <h2>Andamento</h2>
            <?php if ($posicaoAtual !== false): ?>
                <ol class="etapas" aria-label="Andamento da comanda">
                    <?php foreach ($etapas as $indice => $etapa): ?>
                        <li class="<?= $indice < $posicaoAtual ? 'feita' : ($indice === $posicaoAtual ? 'atual etapa-' . e($etapa) : '') ?>"><?= e(rotuloDoStatus($etapa)) ?></li>
                    <?php endforeach; ?>
                </ol>
            <?php endif; ?>

            <?php if (!$pedido->estaFinalizado()): ?>
                <div class="acoes">
                    <?php if (isset($rotulosDeAvanco[$pedido->getStatus()])): ?>
                        <form method="post" action="pedido.php?numero=<?= e($pedido->getNumero()) ?>">
                            <input type="hidden" name="acao" value="avancar">
                            <button type="submit"><?= e($rotulosDeAvanco[$pedido->getStatus()]) ?></button>
                        </form>
                    <?php endif; ?>
                    <?php if ($pedido->getStatus() === StatusPedido::ENTREGUE): ?>
                        <a class="botao" href="pagamento.php?pedido=<?= e($pedido->getNumero()) ?>">Fechar a conta</a>
                    <?php endif; ?>
                    <form method="post" action="pedido.php?numero=<?= e($pedido->getNumero()) ?>" data-confirmar="Cancelar o pedido #<?= e($pedido->getNumero()) ?> e liberar a mesa?">
                        <input type="hidden" name="acao" value="cancelar">
                        <button type="submit" class="botao-perigo">riscar a comanda</button>
                    </form>
                </div>

                <section class="bloco">
                    <h2>mais um item?</h2>
                    <form method="post" action="pedido.php?numero=<?= e($pedido->getNumero()) ?>">
                        <input type="hidden" name="acao" value="adicionar_item">
                        <div class="campo">
                            <label for="item">Item do cardápio</label>
                            <select id="item" name="item" required>
                                <?php foreach ($cardapio->agruparPorTipo() as $tipo => $itensDoTipo): ?>
                                    <optgroup label="<?= e(ItemCardapioFactory::tiposDisponiveis()[$tipo]) ?>">
                                        <?php foreach ($itensDoTipo as $item): ?>
                                            <?php if (!$item->isDisponivel()) { continue; } ?>
                                            <option value="<?= e($item->getId()) ?>"><?= e($item->getNome()) ?> — <?= e(moeda($item->getPreco())) ?></option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-grade">
                            <div class="campo">
                                <label for="quantidade">Quantidade</label>
                                <input type="number" id="quantidade" name="quantidade" min="1" max="<?= e(Pedido::MAX_QUANTIDADE_POR_ITEM) ?>" value="1" required>
                            </div>
                            <div class="campo">
                                <label for="observacao">Observação (opcional)</label>
                                <input type="text" id="observacao" name="observacao" maxlength="<?= e(ItemPedido::TAMANHO_MAXIMO_OBSERVACAO) ?>" placeholder="ex.: sem cebola">
                            </div>
                        </div>
                        <button type="submit">Anotar na comanda</button>
                    </form>
                    <p class="pequeno mt">Limites: até <?= e(Pedido::MAX_ITENS) ?> itens diferentes por comanda e <?= e(Pedido::MAX_QUANTIDADE_POR_ITEM) ?> unidades por item.</p>
                </section>
            <?php endif; ?>

            <?php if ($pagamento !== null): ?>
                <?= cupomDaConta($pedido, $pagamento, false) ?>
            <?php endif; ?>
        </aside>
    </div>
<?php rodape(); ?>
