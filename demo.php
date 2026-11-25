<?php
/**
 * Arquivo: demo.php
 * Responsável: Erik Carvalho Nogueira (RA 2026106090008)
 * Descrição: "Laboratório POO" — executa ao vivo cada requisito do trabalho (herança, polimorfismo, encapsulamento, exceções, estáticos/constantes e padrões de projeto).
 */
require __DIR__ . '/includes/bootstrap.php';

$restaurante = restaurante();

function capturar(callable $trecho): array
{
    try {
        $trecho();
    } catch (Throwable $erro) {
        return ['capturou' => true, 'classe' => get_class($erro), 'mensagem' => $erro->getMessage()];
    }
    return ['capturou' => false, 'classe' => '(nada foi lançado)', 'mensagem' => ''];
}

$hierarquias = [
    'ItemCardapio' => ['Prato', 'Bebida', 'Sobremesa'],
    'Funcionario' => ['Garcom', 'Cozinheiro', 'Gerente'],
    'Pagamento' => ['PagamentoDinheiro', 'PagamentoPix', 'PagamentoCartao'],
];
$tentativaAbstrata = capturar(static function (): void {
    $classe = 'ItemCardapio';
    new $classe('Teste', 10.00);
});

$cardapioDemo = new Cardapio();
$prato = $cardapioDemo->adicionar(new Prato('Feijoada', 60.00, 35, 2));
$bebida = $cardapioDemo->adicionar(new Bebida('Suco', 10.00));
$sobremesa = $cardapioDemo->adicionar(new Sobremesa('Pudim', 12.00));

$equipeDemo = new Equipe();
$garcomDemo = $equipeDemo->contratar(new Garcom('Garçom de teste', 1800.00));
$cozinheiroDemo = $equipeDemo->contratar(new Cozinheiro('Cozinheiro de teste', 2400.00, 'Grelhados'));
$equipeDemo->contratar(new Gerente('Gerente de teste', 4500.00, 500.00));
$cozinheiroDemo->registrarHorasExtras(10.0);
$equipeDemo->registrarVenda(800.00, $garcomDemo->getId());
$folhaDemo = $equipeDemo->calcularFolha();

$pedidoDemo = new Pedido(1, 2);
$pedidoDemo->adicionarItem($prato, 1);
$pedidoDemo->adicionarItem($bebida, 6);
$pedidoDemo->adicionarItem($sobremesa, 3);

$contaDemo = 200.00;
$formasDemo = [
    new PagamentoDinheiro(),
    new PagamentoPix(),
    new PagamentoCartao(PagamentoCartao::DEBITO),
    new PagamentoCartao(PagamentoCartao::CREDITO, 3),
];

$leituraDireta = capturar(static function () use ($prato): void {
    $valor = $prato->preco;
});
$escritaDireta = capturar(static function () use ($prato): void {
    $prato->nome = 'Alterado de fora';
});
$setterInvalido = capturar(static function () use ($prato): void {
    $prato->setPreco(-5.00);
});

$visibilidade = [];
$totalPublicas = 0;
foreach (glob(__DIR__ . '/src/*/*.php') ?: [] as $arquivo) {
    $nomeDaClasse = basename($arquivo, '.php');
    if (!class_exists($nomeDaClasse) && !interface_exists($nomeDaClasse)) {
        continue;
    }
    $reflexao = new ReflectionClass($nomeDaClasse);
    $contagem = ['publicas' => 0, 'protegidas' => 0, 'privadas' => 0];
    foreach ($reflexao->getProperties() as $propriedade) {
        if ($propriedade->getDeclaringClass()->getName() !== $reflexao->getName()) {
            continue;
        }
        if ($propriedade->isPublic()) {
            $contagem['publicas']++;
        } elseif ($propriedade->isProtected()) {
            $contagem['protegidas']++;
        } else {
            $contagem['privadas']++;
        }
    }
    $visibilidade[$nomeDaClasse] = $contagem;
    $totalPublicas += $contagem['publicas'];
}
ksort($visibilidade);

$cenarios = [
    [
        'Buscar um item de cardápio que não existe',
        'Cardapio::buscarPorId()',
        static function () use ($restaurante): void {
            $restaurante->getCardapio()->buscarPorId(9999);
        },
    ],
    [
        'Ocupar uma mesa que já está ocupada',
        'Mesa::ocupar()',
        static function (): void {
            $mesa = new Mesa(1, 4);
            $mesa->ocupar(2);
            $mesa->ocupar(2);
        },
    ],
    [
        'Grupo maior que a capacidade da mesa',
        'Mesa::ocupar()',
        static function (): void {
            (new Mesa(2, 2))->ocupar(5);
        },
    ],
    [
        'Pagar em dinheiro com valor insuficiente',
        'PagamentoDinheiro::validar()',
        static function (): void {
            (new PagamentoDinheiro(10.00))->processar(100.00, 1);
        },
    ],
    [
        'Parcelar no cartão de débito',
        'PagamentoCartao::__construct()',
        static function (): void {
            new PagamentoCartao(PagamentoCartao::DEBITO, 3);
        },
    ],
    [
        'Alterar um pedido que já foi cancelado',
        'Pedido::adicionarItem()',
        static function () use ($prato): void {
            $pedido = new Pedido(1, 2);
            $pedido->cancelar();
            $pedido->adicionarItem($prato, 1);
        },
    ],
    [
        'Cadastrar um prato com preço inválido',
        'ItemCardapio::setPreco()',
        static function (): void {
            new Prato('Prato grátis', 0.00);
        },
    ],
    [
        'Escolher como garçom quem não existe',
        'Equipe::buscarGarcom()',
        static function () use ($restaurante): void {
            $restaurante->getEquipe()->buscarGarcom(9999);
        },
    ],
];
$resultadosDasExcecoes = [];
foreach ($cenarios as $cenario) {
    $resultadosDasExcecoes[] = [$cenario[0], $cenario[1], capturar($cenario[2])];
}
$cadeiaDeHeranca = array_merge(['ItemNaoEncontradoException'], array_values(class_parents('ItemNaoEncontradoException')));

$constantes = [
    ['Pedido::TAXA_SERVICO', Util::percentual(Pedido::TAXA_SERVICO), 'Pedido::getTaxaServico(): taxa de serviço fixa de todo pedido'],
    ['Pedido::MAX_ITENS', (string) Pedido::MAX_ITENS, 'Pedido::adicionarItem(): limite de itens diferentes'],
    ['Pedido::MAX_QUANTIDADE_POR_ITEM', (string) Pedido::MAX_QUANTIDADE_POR_ITEM, 'Pedido::adicionarItem(): limite de unidades por item'],
    ['Mesa::CAPACIDADE_MAXIMA', (string) Mesa::CAPACIDADE_MAXIMA, 'Mesa::__construct(): lugares por mesa'],
    ['Equipe::LIMITE_FUNCIONARIOS', (string) Equipe::LIMITE_FUNCIONARIOS, 'Equipe::contratar(): tamanho máximo da equipe'],
    ['PagamentoPix::DESCONTO', Util::percentual(PagamentoPix::DESCONTO), 'PagamentoPix::calcularValorCobrado()'],
    ['PagamentoCartao::MAX_PARCELAS', (string) PagamentoCartao::MAX_PARCELAS, 'PagamentoCartao::__construct()'],
    ['Garcom::PERCENTUAL_COMISSAO', Util::percentual(Garcom::PERCENTUAL_COMISSAO), 'Garcom::calcularComissao()'],
    ['Config::NOME_RESTAURANTE', Config::NOME_RESTAURANTE, 'cabeçalho de todas as páginas'],
];

$itensAntes = ItemCardapio::getTotalInstancias();
new Prato('Contador A', 10.00);
new Bebida('Contador B', 5.00);
$itensDepois = ItemCardapio::getTotalInstancias();

$funcionariosAntes = Funcionario::getTotalFuncionarios();
new Garcom('Contador C', 1800.00);
$funcionariosDepois = Funcionario::getTotalFuncionarios();

$pedidoAntes = Pedido::getProximoNumero();
new Pedido(1, 1);
$pedidoDepois = Pedido::getProximoNumero();

$instanciaA = Restaurante::getInstance();
$instanciaB = Restaurante::getInstance();
$tentativaNew = capturar(static function (): void {
    $classe = 'Restaurante';
    new $classe(new ArmazenamentoJson('nao-usado.json'));
});
$tentativaClone = capturar(static function () use ($instanciaA): void {
    $copia = clone $instanciaA;
});

$fabricados = [
    ['ItemCardapioFactory::criar(\'prato\')', ItemCardapioFactory::criar('prato', ['nome' => 'Prato da Fábrica', 'preco' => '25,90'])],
    ['ItemCardapioFactory::criar(\'bebida\')', ItemCardapioFactory::criar('bebida', ['nome' => 'Bebida da Fábrica', 'preco' => '7,50'])],
    ['ItemCardapioFactory::criar(\'sobremesa\')', ItemCardapioFactory::criar('sobremesa', ['nome' => 'Sobremesa da Fábrica', 'preco' => '11,00'])],
    ['FuncionarioFactory::criar(\'garcom\')', FuncionarioFactory::criar('garcom', ['nome' => 'Garçom da Fábrica', 'salario_base' => '1.800,00'])],
    ['FuncionarioFactory::criar(\'cozinheiro\')', FuncionarioFactory::criar('cozinheiro', ['nome' => 'Cozinheiro da Fábrica', 'salario_base' => '2.400,00'])],
    ['FuncionarioFactory::criar(\'gerente\')', FuncionarioFactory::criar('gerente', ['nome' => 'Gerente da Fábrica', 'salario_base' => '4.500,00'])],
    ['PagamentoFactory::criar(\'dinheiro\')', PagamentoFactory::criar('dinheiro', ['valor_recebido' => '100,00'])],
    ['PagamentoFactory::criar(\'pix\')', PagamentoFactory::criar('pix')],
    ['PagamentoFactory::criar(\'cartao\')', PagamentoFactory::criar('cartao', ['modalidade' => 'credito', 'parcelas' => '2'])],
];
$tipoDesconhecido = capturar(static function (): void {
    ItemCardapioFactory::criar('sushi', []);
});
$processarEhFinal = (new ReflectionMethod('Pagamento', 'processar'))->isFinal();

cabecalho('Caderno de receitas', 'demo.php');
?>
    <h1>Caderno de receitas</h1>
    <p class="subtitulo">Laboratório POO: cada receita executa ao vivo um requisito do trabalho e mostra onde ele está no código.
        Nada aqui altera os dados do restaurante.</p>

    <nav class="cartao sumario" aria-label="Índice do caderno">
        <ul class="limpa">
            <li><a href="#heranca">1. Classe abstrata/interface e subclasses (herança)</a></li>
            <li><a href="#polimorfismo">2. Polimorfismo</a></li>
            <li><a href="#encapsulamento">3. Encapsulamento (private/protected)</a></li>
            <li><a href="#excecoes">4. Exceções customizadas com try/catch</a></li>
            <li><a href="#estaticos">5. Membros estáticos e constantes</a></li>
            <li><a href="#padroes">6. Padrões de projeto: Singleton, Factory e Template Method</a></li>
        </ul>
    </nav>

    <section class="cartao receita" id="heranca">
        <h2><?= icone('panela') ?> 1. Classe abstrata/interface e subclasses (herança)</h2>
        <p class="onde">Onde está no código: <span class="codigo">src/Cardapio/ItemCardapio.php</span>,
            <span class="codigo">src/Funcionarios/Funcionario.php</span>, <span class="codigo">src/Pagamentos/Pagamento.php</span>
            (abstratas) e <span class="codigo">src/Contratos/Persistivel.php</span> (interface).</p>
        <div class="tabela-rolagem">
            <table class="tabela">
                <thead><tr><th>Contrato (base)</th><th>Subclasses, cada uma com comportamento próprio</th></tr></thead>
                <tbody>
                <?php foreach ($hierarquias as $base => $filhas): ?>
                    <tr>
                        <td><span class="codigo"><?= e((new ReflectionClass($base))->isAbstract() ? 'abstract class ' : 'class ') ?><?= e($base) ?></span></td>
                        <td>
                            <?php foreach ($filhas as $filha): ?>
                                <span class="codigo">class <?= e($filha) ?> extends <?= e((new ReflectionClass($filha))->getParentClass()->getName()) ?></span>
                            <?php endforeach; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <p class="mt">Tentar criar um objeto da classe abstrata (<span class="codigo">new ItemCardapio(...)</span>) é proibido:</p>
        <p><span class="<?= $tentativaAbstrata['capturou'] ? 'resultado-ok' : 'resultado-erro' ?>"><?= e($tentativaAbstrata['classe']) ?></span> — <?= e($tentativaAbstrata['mensagem']) ?></p>
    </section>

    <section class="cartao receita" id="polimorfismo">
        <h2><?= icone('prato') ?> 2. Polimorfismo</h2>
        <p class="onde">Onde está no código: <span class="codigo">Pedido::getSubtotal()</span> e <span class="codigo">ItemPedido::getSubtotal()</span> (<span class="codigo">src/Pedidos</span>),
            <span class="codigo">Equipe::calcularFolha()</span> e <span class="codigo">Equipe::registrarVenda()</span> (<span class="codigo">src/Funcionarios/Equipe.php</span>),
            <span class="codigo">Pagamento::processar()</span> (<span class="codigo">src/Pagamentos</span>) e <span class="codigo">Cardapio::descricoes()</span>.</p>

        <h3>2.1 Itens do cardápio — o mesmo <span class="codigo">calcularPreco()</span>, regras diferentes</h3>
        <div class="tabela-rolagem">
            <table class="tabela">
                <thead><tr><th>Item (tipo-base ItemCardapio)</th><th class="num">3 unid.</th><th class="num">6 unid.</th><th>Setor</th><th class="num">Preparo</th><th>Regra</th></tr></thead>
                <tbody>
                <?php foreach ($cardapioDemo->todos() as $item): ?>
                    <tr>
                        <td><strong><?= e($item->getNome()) ?></strong> <span class="detalhe"><?= e(get_class($item)) ?> · preço <?= e(moeda($item->getPreco())) ?></span></td>
                        <td class="num"><?= e(moeda($item->calcularPreco(3))) ?></td>
                        <td class="num"><?= e(moeda($item->calcularPreco(6))) ?></td>
                        <td><?= e($item->getSetorPreparo()) ?></td>
                        <td class="num"><?= e($item->getTempoPreparoMinutos()) ?> min</td>
                        <td><?= e($item->getRegraDePreco()) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <h3 class="mt">2.2 Pedido — soma itens de tipos diferentes</h3>
        <ul class="limpa">
            <?php foreach ($pedidoDemo->getItens() as $linha): ?>
                <li><?= e($linha->getQuantidade()) ?> × <?= e($linha->getItem()->getNome()) ?> (<?= e(get_class($linha->getItem())) ?>) = <strong><?= e(moeda($linha->getSubtotal())) ?></strong></li>
            <?php endforeach; ?>
            <li>Subtotal <strong><?= e(moeda($pedidoDemo->getSubtotal())) ?></strong> + taxa de serviço <?= e(moeda($pedidoDemo->getTaxaServico())) ?> = <strong><?= e(moeda($pedidoDemo->getTotal())) ?></strong></li>
            <li>Preparo estimado: <?= e($pedidoDemo->estimarTempoPreparoMinutos()) ?> min · setores: <?= e(implode(', ', $pedidoDemo->getSetoresEnvolvidos())) ?></li>
        </ul>

        <h3 class="mt">2.3 Equipe — a mesma folha, salários calculados de jeitos diferentes</h3>
        <p class="pequeno">Neste exemplo, o cozinheiro fez 10 h extras e o garçom atendeu uma venda de R$ 800,00 (meta do gerente: R$ 500,00).</p>
        <div class="tabela-rolagem">
            <table class="tabela">
                <thead><tr><th>Funcionário (tipo-base Funcionario)</th><th>Cargo</th><th class="num">Salário base</th><th class="num">Salário do mês</th><th>Situação</th></tr></thead>
                <tbody>
                <?php foreach ($folhaDemo as $linha): $funcionario = $linha['funcionario']; ?>
                    <tr>
                        <td><?= e($funcionario->getNome()) ?> <span class="detalhe"><?= e(get_class($funcionario)) ?></span></td>
                        <td><?= e($linha['cargo']) ?></td>
                        <td class="num"><?= e(moeda($funcionario->getSalarioBase())) ?></td>
                        <td class="num"><strong><?= e(moeda($linha['salario'])) ?></strong></td>
                        <td><?= e($funcionario->getDetalhes()) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <h3 class="mt">2.4 Pagamento — a mesma conta de <?= e(moeda($contaDemo)) ?>, cobranças diferentes</h3>
        <div class="tabela-rolagem">
            <table class="tabela">
                <thead><tr><th>Forma (tipo-base Pagamento)</th><th>Regra</th><th class="num">Cobrança</th></tr></thead>
                <tbody>
                <?php foreach ($formasDemo as $forma): ?>
                    <tr>
                        <td><strong><?= e($forma->getNome()) ?></strong> <span class="detalhe"><?= e(get_class($forma)) ?></span></td>
                        <td><?= e($forma->getRegra()) ?></td>
                        <td class="num"><?= e($forma->descreverCobranca($contaDemo)) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section class="cartao receita" id="encapsulamento">
        <h2><?= icone('garrafa') ?> 3. Encapsulamento (private/protected)</h2>
        <p class="onde">Onde está no código: todas as classes de <span class="codigo">src/</span> — atributos <span class="codigo">private</span>/<span class="codigo">protected</span>
            com getters e setters que validam os dados.</p>
        <ul class="limpa">
            <li>Ler <span class="codigo">$prato->preco</span> de fora:
                <span class="<?= $leituraDireta['capturou'] ? 'resultado-ok' : 'resultado-erro' ?>"><?= e($leituraDireta['classe']) ?></span> — <?= e($leituraDireta['mensagem']) ?></li>
            <li>Gravar <span class="codigo">$prato->nome</span> de fora:
                <span class="<?= $escritaDireta['capturou'] ? 'resultado-ok' : 'resultado-erro' ?>"><?= e($escritaDireta['classe']) ?></span> — <?= e($escritaDireta['mensagem']) ?></li>
            <li>Usar o setter com valor inválido <span class="codigo">$prato->setPreco(-5)</span>:
                <span class="<?= $setterInvalido['capturou'] ? 'resultado-ok' : 'resultado-erro' ?>"><?= e($setterInvalido['classe']) ?></span> — <?= e($setterInvalido['mensagem']) ?></li>
        </ul>
        <p class="mt">Conferência automática por reflexão em <strong><?= count($visibilidade) ?></strong> classes/interfaces de <span class="codigo">src/</span>:
            <strong class="<?= $totalPublicas === 0 ? 'resultado-ok' : 'resultado-erro' ?>"><?= e($totalPublicas) ?> atributos públicos</strong>.</p>
        <details>
            <summary>Ver atributos por classe</summary>
            <div class="tabela-rolagem mt">
                <table class="tabela">
                    <thead><tr><th>Classe</th><th class="num">public</th><th class="num">protected</th><th class="num">private</th></tr></thead>
                    <tbody>
                    <?php foreach ($visibilidade as $nome => $contagem): ?>
                        <tr>
                            <td><span class="codigo"><?= e($nome) ?></span></td>
                            <td class="num"><?= e($contagem['publicas']) ?></td>
                            <td class="num"><?= e($contagem['protegidas']) ?></td>
                            <td class="num"><?= e($contagem['privadas']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </details>
    </section>

    <section class="cartao receita" id="excecoes">
        <h2><?= icone('sino') ?> 4. Exceções customizadas com try/catch</h2>
        <p class="onde">Onde está no código: <span class="codigo">src/Excecoes/</span> (<span class="codigo">RestauranteException extends Exception</span> e 7 filhas).
            Tratadas com <span class="codigo">try/catch</span> em <span class="codigo">includes/helpers.php</span>, <span class="codigo">cardapio.php</span>,
            <span class="codigo">pedido.php</span>, <span class="codigo">mesas.php</span> e aqui.</p>
        <p>Cadeia de herança: <span class="codigo"><?= e(implode(' → ', $cadeiaDeHeranca)) ?></span></p>
        <div class="tabela-rolagem">
            <table class="tabela">
                <thead><tr><th>Cenário</th><th>Lançada em</th><th>Exceção capturada pelo catch</th></tr></thead>
                <tbody>
                <?php foreach ($resultadosDasExcecoes as [$descricao, $origem, $resultado]): ?>
                    <tr>
                        <td><?= e($descricao) ?></td>
                        <td><span class="codigo"><?= e($origem) ?></span></td>
                        <td>
                            <span class="<?= $resultado['capturou'] ? 'resultado-ok' : 'resultado-erro' ?>"><?= e($resultado['classe']) ?></span>
                            <span class="detalhe"><?= e($resultado['mensagem']) ?></span>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section class="cartao receita" id="estaticos">
        <h2><?= icone('moeda') ?> 5. Membros estáticos e constantes</h2>
        <p class="onde">Onde está no código: <span class="codigo">Pedido::$proximoNumero</span>, <span class="codigo">ItemCardapio::$totalInstancias</span>,
            <span class="codigo">Funcionario::$totalFuncionarios</span>, <span class="codigo">Restaurante::$instancia</span> e as constantes <span class="codigo">const</span> de cada classe.</p>
        <div class="tabela-rolagem">
            <table class="tabela">
                <thead><tr><th>Constante</th><th>Valor</th><th>Para que serve</th></tr></thead>
                <tbody>
                <?php foreach ($constantes as [$nome, $valor, $uso]): ?>
                    <tr><td><span class="codigo"><?= e($nome) ?></span></td><td><strong><?= e($valor) ?></strong></td><td><?= e($uso) ?></td></tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <h3 class="mt">Contadores estáticos (compartilhados por todas as instâncias)</h3>
        <ul class="limpa">
            <li><span class="codigo">ItemCardapio::getTotalInstancias()</span>: <?= e($itensAntes) ?> → <strong><?= e($itensDepois) ?></strong> depois de criar 2 itens.</li>
            <li><span class="codigo">Funcionario::getTotalFuncionarios()</span>: <?= e($funcionariosAntes) ?> → <strong><?= e($funcionariosDepois) ?></strong> depois de criar 1 funcionário.</li>
            <li><span class="codigo">Pedido::getProximoNumero()</span>: <?= e($pedidoAntes) ?> → <strong><?= e($pedidoDepois) ?></strong> depois de criar 1 pedido (gerador do número do pedido).</li>
        </ul>
    </section>

    <section class="cartao receita" id="padroes">
        <h2><?= icone('chapeu') ?> 6. Padrões de projeto: Singleton, Factory e Template Method</h2>

        <h3>Singleton — <span class="codigo">Restaurante::getInstance()</span></h3>
        <p class="onde">Onde está no código: <span class="codigo">src/Infra/Restaurante.php</span> (construtor e <span class="codigo">__clone</span> privados).</p>
        <ul class="limpa">
            <li>Duas chamadas devolvem o mesmo objeto?
                <strong class="<?= $instanciaA === $instanciaB ? 'resultado-ok' : 'resultado-erro' ?>"><?= $instanciaA === $instanciaB ? 'Sim' : 'Não' ?></strong>
                (objeto nº <?= e(spl_object_id($instanciaA)) ?> e nº <?= e(spl_object_id($instanciaB)) ?>)</li>
            <li><span class="codigo">new Restaurante()</span>:
                <span class="<?= $tentativaNew['capturou'] ? 'resultado-ok' : 'resultado-erro' ?>"><?= e($tentativaNew['classe']) ?></span> — <?= e($tentativaNew['mensagem']) ?></li>
            <li><span class="codigo">clone $restaurante</span>:
                <span class="<?= $tentativaClone['capturou'] ? 'resultado-ok' : 'resultado-erro' ?>"><?= e($tentativaClone['classe']) ?></span> — <?= e($tentativaClone['mensagem']) ?></li>
        </ul>

        <h3 class="mt">Factory — texto vira objeto da subclasse certa</h3>
        <p class="onde">Onde está no código: <span class="codigo">src/Cardapio/ItemCardapioFactory.php</span>, <span class="codigo">src/Funcionarios/FuncionarioFactory.php</span>
            e <span class="codigo">src/Pagamentos/PagamentoFactory.php</span>.</p>
        <div class="tabela-rolagem">
            <table class="tabela">
                <thead><tr><th>Chamada</th><th>Objeto criado</th></tr></thead>
                <tbody>
                <?php foreach ($fabricados as [$chamada, $objeto]): ?>
                    <tr><td><span class="codigo"><?= e($chamada) ?></span></td><td><strong><?= e(get_class($objeto)) ?></strong></td></tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <p class="mt">Tipo inexistente (<span class="codigo">ItemCardapioFactory::criar('sushi')</span>):
            <span class="<?= $tipoDesconhecido['capturou'] ? 'resultado-ok' : 'resultado-erro' ?>"><?= e($tipoDesconhecido['classe']) ?></span> — <?= e($tipoDesconhecido['mensagem']) ?></p>

        <h3 class="mt">Template Method — <span class="codigo">Pagamento::processar()</span></h3>
        <p class="onde">Onde está no código: <span class="codigo">src/Pagamentos/Pagamento.php</span>.</p>
        <p>O roteiro do pagamento é fixo (<span class="codigo">processar()</span> é <span class="codigo">final</span>:
            <strong class="<?= $processarEhFinal ? 'resultado-ok' : 'resultado-erro' ?>"><?= $processarEhFinal ? 'sim' : 'não' ?></strong>);
            os passos <span class="codigo">calcularValorCobrado()</span> e <span class="codigo">validar()</span> são das subclasses.</p>
    </section>
<?php rodape(); ?>
