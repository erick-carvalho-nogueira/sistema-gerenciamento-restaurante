<?php
/**
 * Arquivo: cardapio.php
 * Responsável: Erik Carvalho Nogueira (RA 2026106090008)
 * Descrição: página do módulo Cardápio — lista os itens por tipo, busca por código (trata ItemNaoEncontradoException), cadastra e remove itens.
 */
require __DIR__ . '/includes/bootstrap.php';

$restaurante = restaurante();
$cardapio = $restaurante->getCardapio();

if (ehPost()) {
    switch (post('acao')) {
        case 'adicionar':
            executarAcao(static function () use ($cardapio): void {
                $cardapio->adicionar(ItemCardapioFactory::criar(post('tipo'), $_POST));
            }, 'Item cadastrado no cardápio.');
            break;

        case 'remover':
            executarAcao(static function () use ($cardapio): void {
                $cardapio->remover(Util::paraInt(post('id'), 'código'));
            }, 'Item removido do cardápio.');
            break;
    }
    redirecionar('cardapio.php');
}

$codigoBuscado = consulta('codigo');
$itemEncontrado = null;
$erroDaBusca = null;
if ($codigoBuscado !== '') {
    try {
        $itemEncontrado = $cardapio->buscarPorId(Util::paraInt($codigoBuscado, 'código'));
    } catch (ItemNaoEncontradoException $excecao) {
        $erroDaBusca = $excecao->getTitulo() . ': ' . $excecao->getMessage();
    } catch (DadosInvalidosException $excecao) {
        $erroDaBusca = $excecao->getTitulo() . ': ' . $excecao->getMessage();
    }
}

$nomesNoSingular = ['prato' => 'Prato', 'bebida' => 'Bebida', 'sobremesa' => 'Sobremesa'];

$apelidos = ['prato' => 'Pra forrar', 'bebida' => 'Pra molhar a garganta', 'sobremesa' => 'Pra adoçar'];
$desenhos = ['prato' => 'peixe', 'bebida' => 'copo', 'sobremesa' => 'tigela'];

$recomendado = null;
foreach ($cardapio->todos() as $candidato) {
    if ($candidato instanceof Prato && $candidato->isDisponivel() && ($recomendado === null || $candidato->getPreco() > $recomendado->getPreco())) {
        $recomendado = $candidato;
    }
}

cabecalho('Cardápio', 'cardapio.php');
?>
    <h1>Cardápio do Zé</h1>
    <p class="subtitulo"><?= e($cardapio->quantidade()) ?> itens cadastrados. Cada tipo calcula o preço e o preparo do seu jeito.</p>

    <section class="cartao">
        <h2>Buscar item por código</h2>
        <form method="get" action="cardapio.php" class="linha">
            <div class="campo campo-inline">
                <label for="codigo">Código</label>
                <input type="text" id="codigo" name="codigo" inputmode="numeric" value="<?= e($codigoBuscado) ?>" placeholder="ex.: 3">
            </div>
            <button type="submit">Buscar</button>
        </form>
        <?php if ($erroDaBusca !== null): ?>
            <div class="aviso aviso-erro mt" role="alert"><?= e($erroDaBusca) ?></div>
        <?php elseif ($itemEncontrado !== null): ?>
            <div class="aviso aviso-sucesso mt"><?= e($itemEncontrado->descrever()) ?> · setor <?= e($itemEncontrado->getSetorPreparo()) ?> · <?= e($itemEncontrado->getRegraDePreco()) ?></div>
        <?php endif; ?>
    </section>

    <div class="carta">
        <?php if ($recomendado !== null): ?>
            <div class="selo-ze">Hoje o Zé<br>recomenda<br><?= e($recomendado->getNome()) ?></div>
        <?php endif; ?>

        <div class="carta-colunas">
        <?php foreach ($cardapio->agruparPorTipo() as $tipo => $itens): ?>
            <section class="carta-secao">
                <h2><?= icone($desenhos[$tipo] ?? 'prato') ?> <?= e($apelidos[$tipo] ?? ItemCardapioFactory::tiposDisponiveis()[$tipo]) ?></h2>
                <p class="pequeno"><?= e(ItemCardapioFactory::tiposDisponiveis()[$tipo]) ?> · <?= count($itens) ?> <?= count($itens) === 1 ? 'item' : 'itens' ?></p>
                <?php if (count($itens) === 0): ?>
                    <div class="vazio">Esse canto do cardápio tá vazio.</div>
                <?php else: ?>
                    <?php foreach ($itens as $item): ?>
                        <article class="item-carta">
                            <div class="linha-item">
                                <span class="codigo-item">#<?= e($item->getId()) ?></span>
                                <span class="nome-item"><?= e($item->getNome()) ?></span>
                                <?php if ($item instanceof Bebida && $item->isAlcoolica()): ?><em class="tag-alcool">álcool</em><?php endif; ?>
                                <?php if ($item instanceof Prato && $item->isVegetariano()): ?><em class="tag-veg">veg</em><?php endif; ?>
                                <?php if (!$item->isDisponivel()): ?><em class="tag-fora">indisponível</em><?php endif; ?>
                                <span class="pt"></span>
                                <span class="preco"><?= e(moeda($item->getPreco())) ?></span>
                            </div>
                            <?php if ($item->getDescricao() !== ''): ?><p><?= e($item->getDescricao()) ?></p><?php endif; ?>
                            <span class="detalhe"><?= e($item->getCategoria()) ?> · <?= e($item->getDetalhes()) ?></span>
                            <div class="item-acoes">
                                <span class="detalhe">setor <?= e($item->getSetorPreparo()) ?> · <?= e($item->getTempoPreparoMinutos()) ?> min · <?= e($item->getRegraDePreco()) ?></span>
                                <form method="post" action="cardapio.php" data-confirmar="Remover &quot;<?= e($item->getNome()) ?>&quot; do cardápio?">
                                    <input type="hidden" name="acao" value="remover">
                                    <input type="hidden" name="id" value="<?= e($item->getId()) ?>">
                                    <button type="submit" class="botao-perigo botao-pequeno" title="Tirar este item do cardápio">riscar</button>
                                </form>
                            </div>
                        </article>
                    <?php endforeach; ?>
                <?php endif; ?>
            </section>
        <?php endforeach; ?>
        </div>

        <p class="rodape-carta">Serviço de <?= e(Util::percentual(Pedido::TAXA_SERVICO)) ?> sobre a conta · Aceitamos: <?= e(implode(', ', array_map('mb_strtolower', array_values(PagamentoFactory::tiposDisponiveis())))) ?>.</p>
    </div>

    <h2>Cadastrar novo item</h2>
    <section class="cartao">
        <form method="post" action="cardapio.php">
            <input type="hidden" name="acao" value="adicionar">
            <div class="form-grade">
                <div class="campo">
                    <label for="tipo">Tipo</label>
                    <select id="tipo" name="tipo" data-alternar="tipo-item">
                        <?php foreach ($nomesNoSingular as $valor => $rotulo): ?>
                            <option value="<?= e($valor) ?>"><?= e($rotulo) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="campo">
                    <label for="nome">Nome</label>
                    <input type="text" id="nome" name="nome" maxlength="<?= e(ItemCardapio::TAMANHO_MAXIMO_NOME) ?>" required>
                </div>
                <div class="campo">
                    <label for="preco">Preço (R$)</label>
                    <input type="text" id="preco" name="preco" inputmode="decimal" placeholder="ex.: 29,90" required>
                </div>
                <div class="campo">
                    <label for="descricao">Descrição (opcional)</label>
                    <input type="text" id="descricao" name="descricao" maxlength="<?= e(ItemCardapio::TAMANHO_MAXIMO_DESCRICAO) ?>">
                </div>
            </div>

            <div class="form-grade" data-grupo="tipo-item" data-valor="prato">
                <div class="campo">
                    <label for="tempo_preparo">Tempo de preparo (min)</label>
                    <input type="number" id="tempo_preparo" name="tempo_preparo" min="<?= e(Prato::TEMPO_MINIMO) ?>" max="<?= e(Prato::TEMPO_MAXIMO) ?>" value="20">
                </div>
                <div class="campo">
                    <label for="serve">Serve quantas pessoas</label>
                    <input type="number" id="serve" name="serve" min="1" max="<?= e(Prato::MAX_PESSOAS) ?>" value="1">
                </div>
                <label class="opcao"><input type="checkbox" name="vegetariano" value="1"> Vegetariano</label>
            </div>

            <div class="form-grade" data-grupo="tipo-item" data-valor="bebida">
                <div class="campo">
                    <label for="volume_ml">Volume (ml)</label>
                    <input type="number" id="volume_ml" name="volume_ml" min="<?= e(Bebida::VOLUME_MINIMO_ML) ?>" max="<?= e(Bebida::VOLUME_MAXIMO_ML) ?>" value="300">
                </div>
                <label class="opcao"><input type="checkbox" name="alcoolica" value="1"> Alcoólica</label>
                <label class="opcao"><input type="hidden" name="gelada" value="0"><input type="checkbox" name="gelada" value="1" checked> Servida gelada</label>
            </div>

            <div class="form-grade" data-grupo="tipo-item" data-valor="sobremesa">
                <div class="campo">
                    <label for="calorias">Calorias (kcal)</label>
                    <input type="number" id="calorias" name="calorias" min="0" max="<?= e(Sobremesa::CALORIAS_MAXIMAS) ?>" value="250">
                </div>
                <label class="opcao"><input type="hidden" name="servida_gelada" value="0"><input type="checkbox" name="servida_gelada" value="1" checked> Servida gelada</label>
            </div>

            <button type="submit">Cadastrar item</button>
        </form>
    </section>
<?php rodape(); ?>
