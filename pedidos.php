<?php
/**
 * Arquivo: pedidos.php
 * Responsável: Erik Carvalho Nogueira (RA 2026106090008)
 * Descrição: lista as comandas (pedidos) do restaurante com filtro por situação e atalho para abrir uma nova pelas mesas.
 */
require __DIR__ . '/includes/bootstrap.php';

$restaurante = restaurante();

$opcoes = ['andamento' => 'Em andamento', 'todos' => 'Todos'];
foreach (StatusPedido::todos() as $situacao) {
    $opcoes[$situacao] = rotuloDoStatus($situacao);
}
$filtro = consulta('status', 'andamento');
if (!isset($opcoes[$filtro])) {
    $filtro = 'andamento';
}

$pedidos = array_values(array_filter(
    array_reverse($restaurante->getPedidos()),
    static function (Pedido $pedido) use ($filtro): bool {
        if ($filtro === 'todos') {
            return true;
        }
        if ($filtro === 'andamento') {
            return !$pedido->estaFinalizado();
        }
        return $pedido->getStatus() === $filtro;
    }
));

cabecalho('Comandas', 'pedidos.php');
?>
    <h1>Comandas</h1>
    <p class="subtitulo">Pra abrir uma comanda nova, é só sentar o povo numa mesa livre no <a href="mesas.php">salão</a>.</p>

    <form method="get" action="pedidos.php" class="linha mb">
        <div class="campo campo-inline">
            <label for="status">Situação</label>
            <select id="status" name="status">
                <?php foreach ($opcoes as $valor => $rotulo): ?>
                    <option value="<?= e($valor) ?>"<?= $valor === $filtro ? ' selected' : '' ?>><?= e($rotulo) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="botao-claro">Filtrar</button>
    </form>

    <?php if (count($pedidos) === 0): ?>
        <div class="cartao vazio"><?= ilustracaoVazia('Nenhuma comanda por aqui') ?>
            <p>Nada encontrado para este filtro.</p>
        </div>
    <?php else: ?>
        <div class="tabela-rolagem">
            <table class="tabela">
                <thead>
                    <tr><th>Comanda</th><th>Mesa</th><th>Garçom</th><th class="num">Itens</th><th>Situação</th><th class="num">Total</th><th>Aberta em</th><th></th></tr>
                </thead>
                <tbody>
                <?php foreach ($pedidos as $pedido): ?>
                    <tr>
                        <td><strong>Nº <?= e($pedido->getNumero()) ?></strong></td>
                        <td>Mesa <?= e($pedido->getNumeroMesa()) ?></td>
                        <td><?= e(nomeDoGarcom($restaurante, $pedido->getIdGarcom())) ?></td>
                        <td class="num"><?= e($pedido->getQuantidadeDeUnidades()) ?></td>
                        <td><?= seloStatus($pedido->getStatus()) ?></td>
                        <td class="num"><?= e(moeda($pedido->getTotalCobrado())) ?></td>
                        <td class="sem-quebra"><?= e(Util::formatarDataHora($pedido->getCriadoEm())) ?></td>
                        <td class="num"><a class="botao botao-claro botao-pequeno" href="pedido.php?numero=<?= e($pedido->getNumero()) ?>">Abrir</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
<?php rodape(); ?>
