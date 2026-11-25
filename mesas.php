<?php
/**
 * Arquivo: mesas.php
 * Responsável: Danilo Sales Coutinho (RA 2026106090020)
 * Descrição: página do salão — mostra as mesas, abre mesa (cria o pedido), reserva e libera (trata MesaIndisponivelException).
 */
require __DIR__ . '/includes/bootstrap.php';

$restaurante = restaurante();

if (ehPost()) {
    $destino = 'mesas.php';

    switch (post('acao')) {
        case 'abrir':
            $numeroDoPedido = null;
            $abriu = executarAcao(static function () use ($restaurante, &$numeroDoPedido): void {
                $idGarcom = post('garcom') === '' ? null : Util::paraInt(post('garcom'), 'garçom');
                $pedido = $restaurante->abrirPedido(
                    Util::paraInt(post('mesa'), 'mesa'),
                    Util::paraInt(post('pessoas'), 'quantidade de pessoas'),
                    $idGarcom
                );
                $numeroDoPedido = $pedido->getNumero();
            }, 'Mesa aberta! Lance os itens do pedido.');
            if ($abriu && $numeroDoPedido !== null) {
                $destino = 'pedido.php?numero=' . $numeroDoPedido;
            }
            break;

        case 'reservar':
            executarAcao(static function () use ($restaurante): void {
                $restaurante->reservarMesa(Util::paraInt(post('mesa'), 'mesa'), post('nome'));
            }, 'Mesa reservada.');
            break;

        case 'liberar':
            executarAcao(static function () use ($restaurante): void {
                $restaurante->liberarMesa(Util::paraInt(post('mesa'), 'mesa'));
            }, 'Mesa liberada.');
            break;
    }
    redirecionar($destino);
}

$mesas = $restaurante->getMesas();
$garcons = $restaurante->getEquipe()->garcons();
$emAndamento = $restaurante->pedidosEmAndamento();

cabecalho('Mesas', 'mesas.php');
?>
    <h1>Mesas do salão</h1>
    <p class="subtitulo">Toca numa mesa livre pra sentar o povo e abrir a comanda. Mesa ocupada não abre de novo.</p>

    <div class="grade grade-mesas">
    <?php foreach ($mesas as $mesa): ?>
        <?php
        $pedidoDaMesa = null;
        if ($mesa->getPedidoAtual() !== null) {
            try {
                $pedidoDaMesa = $restaurante->buscarPedido($mesa->getPedidoAtual());
            } catch (PedidoInvalidoException $excecao) {
                $pedidoDaMesa = null;
            }
        }
        ?>
        <article class="cartao mesa mesa-<?= e($mesa->getStatus()) ?>" id="mesa-<?= e($mesa->getNumero()) ?>">
            <?= desenharMesa($mesa, $pedidoDaMesa) ?>
            <div class="mesa-ficha">
                <div class="mesa-topo">
                    <h3>Mesa <?= e($mesa->getNumero()) ?></h3>
                    <span class="selo mesa-<?= e($mesa->getStatus()) ?>"><?= e($mesa->getRotuloStatus()) ?></span>
                </div>
                <p class="pequeno"><?= e($mesa->getCapacidade()) ?> lugares</p>

                <?php if ($mesa->getStatus() === Mesa::OCUPADA): ?>
                    <p><?= e($mesa->getPessoas()) ?> pessoa(s)
                        <?php if ($pedidoDaMesa !== null): ?>
                            · comanda <strong>nº <?= e($pedidoDaMesa->getNumero()) ?></strong> <?= seloStatus($pedidoDaMesa->getStatus()) ?>
                        <?php endif; ?>
                    </p>
                    <?php if ($pedidoDaMesa !== null): ?>
                        <p class="pequeno">Parcial: <?= e(moeda($pedidoDaMesa->getTotal())) ?> · garçom: <?= e(nomeDoGarcom($restaurante, $pedidoDaMesa->getIdGarcom())) ?></p>
                        <a class="botao" href="pedido.php?numero=<?= e($pedidoDaMesa->getNumero()) ?>">Abrir a comanda</a>
                    <?php endif; ?>

                <?php else: ?>
                    <?php if ($mesa->getStatus() === Mesa::RESERVADA): ?>
                        <p>Reservada para <strong><?= e($mesa->getReservadaPara()) ?></strong></p>
                    <?php endif; ?>

                    <form method="post" action="mesas.php" class="linha">
                        <input type="hidden" name="acao" value="abrir">
                        <input type="hidden" name="mesa" value="<?= e($mesa->getNumero()) ?>">
                        <div class="campo campo-inline">
                            <label for="pessoas-<?= e($mesa->getNumero()) ?>">Pessoas</label>
                            <input type="number" id="pessoas-<?= e($mesa->getNumero()) ?>" name="pessoas" min="1" max="<?= e($mesa->getCapacidade()) ?>" value="<?= e(min(2, $mesa->getCapacidade())) ?>" required>
                        </div>
                        <div class="campo campo-inline">
                            <label for="garcom-<?= e($mesa->getNumero()) ?>">Garçom</label>
                            <select id="garcom-<?= e($mesa->getNumero()) ?>" name="garcom">
                                <option value="">— sem garçom —</option>
                                <?php foreach ($garcons as $garcom): ?>
                                    <option value="<?= e($garcom->getId()) ?>"><?= e($garcom->getNome()) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit">Sentar o povo</button>
                    </form>

                    <?php if ($mesa->getStatus() === Mesa::LIVRE): ?>
                        <details>
                            <summary>Reservar</summary>
                            <form method="post" action="mesas.php" class="linha mt">
                                <input type="hidden" name="acao" value="reservar">
                                <input type="hidden" name="mesa" value="<?= e($mesa->getNumero()) ?>">
                                <div class="campo campo-inline">
                                    <input type="text" name="nome" maxlength="<?= e(Mesa::TAMANHO_MAXIMO_RESERVA) ?>" placeholder="Nome da reserva" aria-label="Nome da reserva da mesa <?= e($mesa->getNumero()) ?>" required>
                                </div>
                                <button type="submit" class="botao-claro">Reservar</button>
                            </form>
                        </details>
                    <?php else: ?>
                        <form method="post" action="mesas.php">
                            <input type="hidden" name="acao" value="liberar">
                            <input type="hidden" name="mesa" value="<?= e($mesa->getNumero()) ?>">
                            <button type="submit" class="botao-claro botao-pequeno">Cancelar reserva</button>
                        </form>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </article>
    <?php endforeach; ?>
    </div>

    <ul class="legenda-mesas">
        <li><i></i>Livre</li>
        <li><i class="ocupada"></i>Ocupada, com a comanda</li>
        <li><i class="reservada"></i>Reservada</li>
    </ul>

    <?php if (count($emAndamento) > 0): ?>
        <h2>Comandas na cozinha</h2>
        <?= trilhoDeComandas($emAndamento, $restaurante) ?>
    <?php endif; ?>
<?php rodape(); ?>
