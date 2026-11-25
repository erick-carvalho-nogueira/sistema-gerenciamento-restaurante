<?php
/**
 * Arquivo: index.php
 * Responsável: Erik Carvalho Nogueira (RA 2026106090008)
 * Descrição: painel inicial — indicadores do dia, comandas em andamento, faturamento por forma de pagamento e mapa das mesas.
 */
require __DIR__ . '/includes/bootstrap.php';

$restaurante = restaurante();

if (ehPost() && post('acao') === 'resetar') {
    executarAcao(static function () use ($restaurante): void {
        $restaurante->resetarParaDadosExemplo();
    }, 'Dados de exemplo restaurados.');
    redirecionar('index.php');
}

$resumo = $restaurante->resumo();
$emAndamento = $restaurante->pedidosEmAndamento();
$porForma = $restaurante->faturamentoPorForma();
$mesas = $restaurante->getMesas();

cabecalho('Painel', 'index.php');
?>
    <h1>Painel do dia</h1>
    <p class="subtitulo">Visão geral do <?= e($restaurante->getNome()) ?></p>

    <section class="grade grade-indicadores" aria-label="Indicadores">
        <article class="indicador" style="--r:-1deg">
            <?= icone('moeda') ?>
            <span class="indicador-rotulo">Faturamento de hoje</span>
            <strong class="indicador-valor"><?= e(moeda($resumo['faturamento_dia'])) ?></strong>
            <span class="indicador-nota">comandas que já fecharam</span>
        </article>
        <article class="indicador" style="--r:0.8deg">
            <?= icone('relogio') ?>
            <span class="indicador-rotulo">Comandas em andamento</span>
            <strong class="indicador-valor"><?= e($resumo['pedidos_andamento']) ?></strong>
            <span class="indicador-nota">ainda não pagas</span>
        </article>
        <article class="indicador" style="--r:-0.6deg">
            <?= icone('cadeira') ?>
            <span class="indicador-rotulo">Mesas ocupadas</span>
            <strong class="indicador-valor"><?= e($resumo['mesas_ocupadas']) ?> de <?= e($resumo['mesas_total']) ?></strong>
            <span class="indicador-nota"><?= e($resumo['mesas_livres']) ?> livres · <?= e($resumo['mesas_reservadas']) ?> reservadas</span>
        </article>
        <article class="indicador" style="--r:1deg">
            <?= icone('prato') ?>
            <span class="indicador-rotulo">Itens no cardápio</span>
            <strong class="indicador-valor"><?= e($resumo['itens_cardapio']) ?></strong>
            <span class="indicador-nota">pratos, bebidas e sobremesas</span>
        </article>
        <article class="indicador" style="--r:-0.8deg">
            <?= icone('chapeu') ?>
            <span class="indicador-rotulo">Turma</span>
            <strong class="indicador-valor"><?= e($resumo['funcionarios']) ?></strong>
            <span class="indicador-nota">folha do mês: <?= e(moeda($resumo['folha_estimada'])) ?></span>
        </article>
    </section>

    <h2>Comandas na cozinha</h2>
    <?php if (count($emAndamento) === 0): ?>
        <div class="cartao vazio"><?= ilustracaoVazia('Tá tudo quieto por aqui') ?>
            <p>Nenhuma comanda em andamento. Sente o povo numa mesa pra começar.</p>
            <p class="mt"><a class="botao" href="mesas.php">Ir pro salão</a></p>
        </div>
    <?php else: ?>
        <?= trilhoDeComandas($emAndamento, $restaurante) ?>
        <p class="nota-mao">toca numa comanda pra abrir ↗</p>
    <?php endif; ?>

    <div class="grade grade-2">
        <section>
            <h2>Faturamento de hoje por forma de pagamento</h2>
            <div class="tabela-rolagem">
                <table class="tabela">
                    <thead><tr><th>Forma</th><th class="num">Comandas</th><th class="num">Total cobrado</th></tr></thead>
                    <tbody>
                    <?php foreach ($porForma as $linha): ?>
                        <tr>
                            <td><?= e($linha['nome']) ?></td>
                            <td class="num"><?= e($linha['quantidade']) ?></td>
                            <td class="num"><?= e(moeda($linha['total'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr><td colspan="2">Total</td><td class="num"><?= e(moeda($resumo['faturamento_dia'])) ?></td></tr>
                    </tfoot>
                </table>
            </div>
        </section>

        <section>
            <h2>Mapa das mesas</h2>
            <div class="cartao">
                <div class="mini-mesas">
                    <?php foreach ($mesas as $mesa): ?>
                        <a class="mini-mesa mesa-<?= e($mesa->getStatus()) ?>" href="mesas.php#mesa-<?= e($mesa->getNumero()) ?>"
                           title="Mesa <?= e($mesa->getNumero()) ?> · <?= e($mesa->getRotuloStatus()) ?> · <?= e($mesa->getCapacidade()) ?> lugares"><?= e($mesa->getNumero()) ?></a>
                    <?php endforeach; ?>
                </div>
                <ul class="legenda-mesas">
                    <li><i></i>Livre</li>
                    <li><i class="ocupada"></i>Ocupada, com a comanda</li>
                    <li><i class="reservada"></i>Reservada</li>
                </ul>
            </div>
        </section>
    </div>

    <h2>Sobre este sistema</h2>
    <div class="cartao">
        <p>Projeto de Programação Orientada a Objetos. O <a href="demo.php">caderno de receitas</a> (Laboratório POO) executa ao vivo cada requisito
            do trabalho (herança, polimorfismo, encapsulamento, exceção, estáticos/constantes e padrões de projeto).</p>
        <form method="post" action="index.php" data-confirmar="Isto apaga os dados atuais e restaura os dados de exemplo. Continuar?">
            <input type="hidden" name="acao" value="resetar">
            <button type="submit" class="botao-claro">Restaurar dados de exemplo</button>
        </form>
    </div>
<?php rodape(); ?>
