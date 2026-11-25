<?php
/**
 * Arquivo: run_tests.php
 * Responsável: Erik Carvalho Nogueira (RA 2026106090008)
 * Descrição: testes automatizados das classes (herança, polimorfismo, encapsulamento, exceções, estáticos, padrões, persistência e fluxo completo).
 * Uso: php tests/run_tests.php
 */
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');
date_default_timezone_set('America/Porto_Velho');

$arquivoTemporario = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'restaurante_testes_' . getmypid() . '.json';
putenv('RESTAURANTE_DADOS=' . $arquivoTemporario);
register_shutdown_function(static function (): void {
    $padrao = str_replace('\\', '/', sys_get_temp_dir()) . '/restaurante_testes_' . getmypid() . '*';
    foreach (glob($padrao) ?: [] as $arquivo) {
        @unlink($arquivo);
    }
});

require __DIR__ . '/../src/autoload.php';
require __DIR__ . '/Teste.php';
require __DIR__ . '/../scripts/AnalisadorAutoria.php';

$raiz = dirname(__DIR__);

function novoRestaurante(string $arquivo): Restaurante
{
    @unlink($arquivo);
    Restaurante::reiniciarInstancia();
    return Restaurante::getInstance(new ArmazenamentoJson($arquivo));
}

function capturar(callable $acao): ?Throwable
{
    try {
        $acao();
    } catch (Throwable $erro) {
        return $erro;
    }
    return null;
}

Teste::grupo('1 e 2. Classe abstrata/interface e subclasses (heranca)');
$contratos = ['ItemCardapio', 'Funcionario', 'Pagamento'];
foreach ($contratos as $contrato) {
    $reflexao = new ReflectionClass($contrato);
    Teste::verdadeiro($reflexao->isAbstract(), "$contrato e uma classe abstrata");
    Teste::verdadeiro($reflexao->implementsInterface('Persistivel'), "$contrato implementa a interface Persistivel");
}
Teste::verdadeiro((new ReflectionClass('Persistivel'))->isInterface(), 'Persistivel e uma interface');

$hierarquias = [
    'ItemCardapio' => ['Prato', 'Bebida', 'Sobremesa'],
    'Funcionario' => ['Garcom', 'Cozinheiro', 'Gerente'],
    'Pagamento' => ['PagamentoDinheiro', 'PagamentoPix', 'PagamentoCartao'],
];
foreach ($hierarquias as $base => $filhas) {
    foreach ($filhas as $filha) {
        Teste::verdadeiro(is_subclass_of($filha, $base), "$filha extends $base");
        Teste::verdadeiro(!(new ReflectionClass($filha))->isAbstract(), "$filha e uma classe concreta");
    }
}
foreach ($contratos as $contrato) {
    Teste::lanca(Error::class, static function () use ($contrato): void {
        new $contrato();
    }, "nao e possivel instanciar a abstrata $contrato");
}

Teste::verdadeiro(is_subclass_of('RestauranteException', 'Exception'), 'RestauranteException extends Exception');
foreach (['DadosInvalidosException', 'ItemNaoEncontradoException', 'FuncionarioNaoEncontradoException', 'MesaIndisponivelException',
    'PedidoInvalidoException', 'PagamentoInvalidoException', 'ArmazenamentoException'] as $excecao) {
    Teste::verdadeiro(is_subclass_of($excecao, 'RestauranteException'), "$excecao extends RestauranteException");
}

Teste::grupo('3. Polimorfismo (mesma chamada, comportamento diferente)');
$cardapio = new Cardapio();
$prato = $cardapio->adicionar(new Prato('Feijoada', 60.00, 35, 2));
$bebida = $cardapio->adicionar(new Bebida('Suco', 10.00));
$sobremesa = $cardapio->adicionar(new Sobremesa('Pudim', 12.00));

Teste::aproximado(180.00, $prato->calcularPreco(3), 'Prato x3 = preco cheio (180,00)');
Teste::aproximado(360.00, $prato->calcularPreco(6), 'Prato x6 = preco cheio (360,00)');
Teste::aproximado(30.00, $bebida->calcularPreco(3), 'Bebida x3 = sem desconto (30,00)');
Teste::aproximado(54.00, $bebida->calcularPreco(6), 'Bebida x6 = 10% de desconto (54,00)');
Teste::aproximado(12.00, $sobremesa->calcularPreco(1), 'Sobremesa x1 = 12,00');
Teste::aproximado(24.00, $sobremesa->calcularPreco(2), 'Sobremesa x2 = 24,00');
Teste::aproximado(24.00, $sobremesa->calcularPreco(3), 'Sobremesa x3 = leve 3 pague 2 (24,00)');
Teste::aproximado(36.00, $sobremesa->calcularPreco(4), 'Sobremesa x4 = 36,00');
Teste::aproximado(48.00, $sobremesa->calcularPreco(6), 'Sobremesa x6 = 48,00');
Teste::igual(['Cozinha', 'Bar', 'Confeitaria'], array_map(static function (ItemCardapio $i): string {
    return $i->getSetorPreparo();
}, $cardapio->todos()), 'cada tipo de item informa o seu setor de preparo');
Teste::igual([35, 3, 8], array_map(static function (ItemCardapio $i): int {
    return $i->getTempoPreparoMinutos();
}, $cardapio->todos()), 'cada tipo de item informa o seu tempo de preparo');
Teste::igual(3, count($cardapio->descricoes()), 'Cardapio::descricoes() percorre os 3 tipos');
Teste::verdadeiro(strpos($cardapio->descricoes()[2], 'Sobremesa:') === 0, 'descrever() usa a categoria de cada subclasse');

$pedido = new Pedido(1, 2);
$pedido->adicionarItem($prato, 1);
$pedido->adicionarItem($bebida, 6);
$pedido->adicionarItem($sobremesa, 3);
Teste::aproximado(138.00, $pedido->getSubtotal(), 'Pedido::getSubtotal() = 60 + 54 + 24 = 138,00');
Teste::aproximado(13.80, $pedido->getTaxaServico(), 'taxa de servico de 10% = 13,80');
Teste::aproximado(151.80, $pedido->getTotal(), 'total da conta = 151,80');
Teste::igual(35, $pedido->estimarTempoPreparoMinutos(), 'tempo estimado = maior tempo entre os itens (35)');
Teste::igual(['Cozinha', 'Bar', 'Confeitaria'], $pedido->getSetoresEnvolvidos(), 'setores envolvidos no pedido');

$equipe = new Equipe();
$garcom = $equipe->contratar(new Garcom('Garcom Teste', 1800.00));
$cozinheiro = $equipe->contratar(new Cozinheiro('Cozinheiro Teste', 2400.00, 'Grelhados'));
$gerente = $equipe->contratar(new Gerente('Gerente Teste', 4500.00, 500.00));
Teste::aproximado(1800.00, $garcom->calcularSalario(), 'Garcom sem vendas recebe so o salario base');
Teste::aproximado(4500.00, $gerente->calcularSalario(), 'Gerente sem atingir a meta recebe so o salario base');
$cozinheiro->registrarHorasExtras(10.0);
$equipe->registrarVenda(800.00, $garcom->getId());
Teste::aproximado(1840.00, $garcom->calcularSalario(), 'Garcom: base + 5% de 800 = 1840,00');
Teste::aproximado(2563.64, $cozinheiro->calcularSalario(), 'Cozinheiro: base + 10 h extras com 50% = 2563,64');
Teste::aproximado(5400.00, $gerente->calcularSalario(), 'Gerente: meta atingida, base + 20% = 5400,00');
$folha = $equipe->calcularFolha();
Teste::igual(['Garçom', 'Cozinheiro', 'Gerente'], array_column($folha, 'cargo'), 'calcularFolha() devolve o cargo de cada subclasse');
Teste::aproximado(9803.64, $equipe->totalFolha(), 'total da folha = 9803,64');
Teste::verdadeiro(
    strpos($garcom->getDetalhes(), 'mesa') !== false
    && strpos($cozinheiro->getDetalhes(), 'especialidade') !== false
    && strpos($gerente->getDetalhes(), 'meta') !== false,
    'cada cargo descreve a sua propria situacao em getDetalhes()'
);
$equipe->registrarVenda(100.00, 999);
Teste::aproximado(1840.00, $garcom->calcularSalario(), 'garcom so comissiona vendas dos proprios pedidos');
$equipe->iniciarNovoMes();
Teste::aproximado(1800.00, $garcom->calcularSalario(), 'iniciarNovoMes() zera a comissao do garcom');
Teste::aproximado(2400.00, $cozinheiro->calcularSalario(), 'iniciarNovoMes() zera as horas extras do cozinheiro');
Teste::aproximado(4500.00, $gerente->calcularSalario(), 'iniciarNovoMes() zera o faturamento do gerente');

$conta = 200.00;
Teste::aproximado(200.00, (new PagamentoDinheiro())->calcularValorCobrado($conta), 'Dinheiro cobra o valor integral');
Teste::aproximado(190.00, (new PagamentoPix())->calcularValorCobrado($conta), 'Pix cobra com 5% de desconto');
Teste::aproximado(200.00, (new PagamentoCartao('debito'))->calcularValorCobrado($conta), 'Cartao de debito cobra a vista');
Teste::aproximado(200.00, (new PagamentoCartao('credito', 1))->calcularValorCobrado($conta), 'Credito 1x cobra a vista');
Teste::aproximado(204.00, (new PagamentoCartao('credito', 2))->calcularValorCobrado($conta), 'Credito 2x cobra 2% de juros');
Teste::aproximado(208.00, (new PagamentoCartao('credito', 3))->calcularValorCobrado($conta), 'Credito 3x cobra 4% de juros');
Teste::aproximado(69.33, (new PagamentoCartao('credito', 3))->calcularValorParcela($conta), 'parcela do credito 3x = 69,33');
Teste::igual('3x de R$ 69,33 (total R$ 208,00)', (new PagamentoCartao('credito', 3))->descreverCobranca($conta), 'Cartao descreve parcelas na simulacao');
Teste::igual('R$ 190,00', (new PagamentoPix())->descreverCobranca($conta), 'Pix descreve so o valor na simulacao');

Teste::grupo('4. Encapsulamento (private/protected, getters e setters)');
$ofensores = [];
$classesAnalisadas = 0;
$arquivosDeClasses = array_merge(
    glob($raiz . '/src/*/*.php') ?: [],
    [$raiz . '/tests/Teste.php', $raiz . '/tests/ClienteHttp.php', $raiz . '/scripts/AnalisadorAutoria.php']
);
foreach ($arquivosDeClasses as $arquivo) {
    $nomeDaClasse = basename($arquivo, '.php');
    if (!class_exists($nomeDaClasse) && !interface_exists($nomeDaClasse)) {
        require_once $arquivo;
    }
    if (!class_exists($nomeDaClasse) && !interface_exists($nomeDaClasse)) {
        continue;
    }
    $classesAnalisadas++;
    foreach ((new ReflectionClass($nomeDaClasse))->getProperties() as $propriedade) {
        if ($propriedade->isPublic()) {
            $ofensores[] = $nomeDaClasse . '::$' . $propriedade->getName();
        }
    }
}
Teste::verdadeiro($classesAnalisadas >= 35, "reflexao analisou $classesAnalisadas classes/interfaces");
Teste::igual([], $ofensores, 'nenhuma classe tem atributo public');

Teste::lanca(Error::class, static function () use ($prato): void {
    $valor = $prato->preco;
}, 'ler atributo protected de fora da classe e proibido');
Teste::lanca(Error::class, static function () use ($prato): void {
    $prato->nome = 'x';
}, 'gravar atributo protected de fora da classe e proibido');
Teste::lanca(DadosInvalidosException::class, static function () use ($prato): void {
    $prato->setPreco(-1.0);
}, 'setPreco() rejeita valor invalido');
Teste::lanca(DadosInvalidosException::class, static function () use ($prato): void {
    $prato->setNome('   ');
}, 'setNome() rejeita nome em branco');
Teste::lanca(DadosInvalidosException::class, static function () use ($prato): void {
    $prato->definirId(77);
}, 'o codigo de um item so pode ser definido uma vez');
Teste::lanca(DadosInvalidosException::class, static function (): void {
    new Garcom('Fulano', 100.00);
}, 'Funcionario rejeita salario abaixo do minimo');
Teste::lanca(DadosInvalidosException::class, static function (): void {
    new Mesa(1, Mesa::CAPACIDADE_MAXIMA + 1);
}, 'Mesa rejeita capacidade acima do maximo');
Teste::lanca(DadosInvalidosException::class, static function (): void {
    new Gerente('Fulano', 3000.00, 0.0);
}, 'Gerente rejeita meta mensal zero');
$preco = new Prato('Teste de preco', 10.0);
$preco->setPreco(12.345);
Teste::aproximado(12.35, $preco->getPreco(), 'getPreco() devolve o valor ja arredondado em centavos');

Teste::grupo('5. Excecoes customizadas lancadas em cenarios reais');
$restaurante = novoRestaurante($arquivoTemporario);

$e = capturar(static function () use ($restaurante): void {
    $restaurante->getCardapio()->buscarPorId(404);
});
Teste::verdadeiro($e instanceof ItemNaoEncontradoException, 'buscar item inexistente lanca ItemNaoEncontradoException');
Teste::verdadeiro($e instanceof RestauranteException && $e instanceof Exception, 'a excecao e uma RestauranteException e uma Exception');
Teste::igual('Não existe item no cardápio com o código #404.', $e === null ? '' : $e->getMessage(), 'mensagem da excecao e clara');
Teste::igual('Item não encontrado', $e instanceof RestauranteException ? $e->getTitulo() : '', 'cada excecao tem o seu titulo');
Teste::lanca(ItemNaoEncontradoException::class, static function () use ($restaurante): void {
    $restaurante->getCardapio()->remover(404);
}, 'remover item inexistente lanca ItemNaoEncontradoException');
Teste::lanca(FuncionarioNaoEncontradoException::class, static function () use ($restaurante): void {
    $restaurante->getEquipe()->buscarPorId(404);
}, 'buscar funcionario inexistente lanca FuncionarioNaoEncontradoException');
Teste::lanca(FuncionarioNaoEncontradoException::class, static function () use ($restaurante): void {
    $restaurante->getEquipe()->demitir(404);
}, 'demitir funcionario inexistente lanca FuncionarioNaoEncontradoException');
Teste::lanca(FuncionarioNaoEncontradoException::class, static function () use ($restaurante): void {
    $restaurante->getEquipe()->buscarGarcom(3);
}, 'escolher cozinheiro como garcom lanca FuncionarioNaoEncontradoException');
Teste::lanca(MesaIndisponivelException::class, static function () use ($restaurante): void {
    $restaurante->buscarMesa(99);
}, 'mesa inexistente lanca MesaIndisponivelException');
Teste::lanca(MesaIndisponivelException::class, static function () use ($restaurante): void {
    $restaurante->abrirPedido(4, 2, 1);
}, 'abrir mesa ocupada lanca MesaIndisponivelException');
Teste::lanca(MesaIndisponivelException::class, static function () use ($restaurante): void {
    $restaurante->abrirPedido(1, 3, 1);
}, 'grupo maior que a mesa lanca MesaIndisponivelException');
Teste::lanca(MesaIndisponivelException::class, static function () use ($restaurante): void {
    $restaurante->liberarMesa(4);
}, 'liberar mesa com pedido aberto lanca MesaIndisponivelException');
Teste::lanca(MesaIndisponivelException::class, static function () use ($restaurante): void {
    $restaurante->reservarMesa(6, 'Outro nome');
}, 'reservar mesa que nao esta livre lanca MesaIndisponivelException');
Teste::lanca(PedidoInvalidoException::class, static function () use ($restaurante): void {
    $restaurante->buscarPedido(404);
}, 'buscar pedido inexistente lanca PedidoInvalidoException');
Teste::lanca(PedidoInvalidoException::class, static function () use ($restaurante): void {
    $restaurante->pagarPedido(3, new PagamentoPix());
}, 'pagar pedido que nao foi entregue lanca PedidoInvalidoException');
Teste::lanca(PedidoInvalidoException::class, static function () use ($restaurante): void {
    $restaurante->pagarPedido(1, new PagamentoPix());
}, 'pagar pedido ja pago lanca PedidoInvalidoException');
Teste::lanca(PedidoInvalidoException::class, static function () use ($restaurante): void {
    $restaurante->buscarPedido(1)->adicionarItem($restaurante->getCardapio()->buscarPorId(1), 1);
}, 'adicionar item a pedido pago lanca PedidoInvalidoException');
Teste::lanca(PedidoInvalidoException::class, static function () use ($restaurante): void {
    $restaurante->buscarPedido(4)->adicionarItem($restaurante->getCardapio()->buscarPorId(1), 21);
}, 'quantidade acima do limite lanca PedidoInvalidoException');
Teste::lanca(PedidoInvalidoException::class, static function () use ($restaurante): void {
    $restaurante->buscarPedido(3)->removerItem(0);
}, 'remover item de pedido na cozinha lanca PedidoInvalidoException');
Teste::lanca(PedidoInvalidoException::class, static function () use ($restaurante): void {
    $restaurante->buscarPedido(1)->cancelar();
}, 'cancelar pedido pago lanca PedidoInvalidoException');
Teste::lanca(PedidoInvalidoException::class, static function (): void {
    (new Pedido(1, 2))->avancarStatus();
}, 'enviar pedido vazio para a cozinha lanca PedidoInvalidoException');
Teste::lanca(PagamentoInvalidoException::class, static function (): void {
    (new PagamentoDinheiro(10.00))->processar(100.00, 1);
}, 'dinheiro insuficiente lanca PagamentoInvalidoException');
Teste::lanca(PagamentoInvalidoException::class, static function (): void {
    new PagamentoCartao('debito', 2);
}, 'debito parcelado lanca PagamentoInvalidoException');
Teste::lanca(PagamentoInvalidoException::class, static function (): void {
    new PagamentoCartao('credito', 4);
}, 'parcelas acima do maximo lancam PagamentoInvalidoException');
Teste::lanca(PagamentoInvalidoException::class, static function (): void {
    new PagamentoCartao('vale', 1);
}, 'modalidade desconhecida lanca PagamentoInvalidoException');
Teste::lanca(PagamentoInvalidoException::class, static function (): void {
    (new PagamentoCartao('credito', 3))->processar(40.00, 1);
}, 'parcela abaixo do minimo lanca PagamentoInvalidoException');
Teste::lanca(PagamentoInvalidoException::class, static function (): void {
    $pagamento = new PagamentoPix();
    $pagamento->processar(50.00, 1);
    $pagamento->processar(50.00, 1);
}, 'processar o mesmo pagamento duas vezes lanca PagamentoInvalidoException');
Teste::lanca(PagamentoInvalidoException::class, static function (): void {
    (new PagamentoPix())->processar(0.0, 1);
}, 'conta de valor zero lanca PagamentoInvalidoException');
Teste::lanca(DadosInvalidosException::class, static function (): void {
    new Prato('', 10.0);
}, 'item sem nome lanca DadosInvalidosException');
Teste::lanca(DadosInvalidosException::class, static function (): void {
    ItemCardapioFactory::criar('prato', ['nome' => 'Prato', 'preco' => 'abc']);
}, 'preco que nao e numero lanca DadosInvalidosException');
$restaurante->salvar();

$pedidoEmPreparo = $restaurante->buscarPedido(3);
$pedidoEmPreparo->avancarStatus();
$pedidoEmPreparo->avancarStatus();
$statusAntes = $pedidoEmPreparo->getStatus();
Teste::lanca(PagamentoInvalidoException::class, static function () use ($restaurante): void {
    $restaurante->pagarPedido(3, new PagamentoDinheiro(1.00));
}, 'pagamento recusado (dinheiro insuficiente) no pedido entregue');
Teste::igual($statusAntes, $pedidoEmPreparo->getStatus(), 'pagamento recusado nao altera o status do pedido');
Teste::igual(Mesa::OCUPADA, $restaurante->buscarMesa(4)->getStatus(), 'pagamento recusado nao libera a mesa');
Teste::verdadeiro($pedidoEmPreparo->getPagamento() === null, 'pagamento recusado nao fica gravado no pedido');

Teste::grupo('6. Membros estaticos e constantes com proposito real');
Teste::igual(0.10, Pedido::TAXA_SERVICO, 'Pedido::TAXA_SERVICO = 10%');
Teste::igual(20, Pedido::MAX_ITENS, 'Pedido::MAX_ITENS = 20');
$muitos = new Cardapio();
for ($i = 1; $i <= 21; $i++) {
    $muitos->adicionar(new Prato('Prato numero ' . $i, 10.00));
}
$pedidoCheio = new Pedido(1, 2);
for ($i = 1; $i <= 20; $i++) {
    $pedidoCheio->adicionarItem($muitos->buscarPorId($i), 1);
}
Teste::igual(20, count($pedidoCheio->getItens()), 'pedido aceita ate MAX_ITENS itens diferentes');
Teste::lanca(PedidoInvalidoException::class, static function () use ($pedidoCheio, $muitos): void {
    $pedidoCheio->adicionarItem($muitos->buscarPorId(21), 1);
}, 'o 21o item diferente e recusado (limite maximo)');
$pedidoCheio->adicionarItem($muitos->buscarPorId(1), 5);
Teste::igual(20, count($pedidoCheio->getItens()), 'repetir um item soma na mesma linha (nao conta como item novo)');
Teste::igual(6, $pedidoCheio->getItens()[0]->getQuantidade(), 'a quantidade da linha foi somada (1 + 5)');
Teste::lanca(PedidoInvalidoException::class, static function () use ($pedidoCheio, $muitos): void {
    $pedidoCheio->adicionarItem($muitos->buscarPorId(1), 15);
}, 'somar alem de MAX_QUANTIDADE_POR_ITEM e recusado');
$semCodigo = new Pedido(1, 2);
$semCodigo->adicionarItem(new Prato('Sem codigo A', 10.0), 1);
$semCodigo->adicionarItem(new Prato('Sem codigo B', 10.0), 1);
Teste::igual(2, count($semCodigo->getItens()), 'itens sem codigo nunca sao fundidos na mesma linha');

$antesItens = ItemCardapio::getTotalInstancias();
new Prato('Contador A', 5.00);
new Bebida('Contador B', 5.00);
Teste::igual($antesItens + 2, ItemCardapio::getTotalInstancias(), 'ItemCardapio::$totalInstancias conta as instancias criadas');
capturar(static function (): void {
    new Prato('', 5.00);
});
Teste::igual($antesItens + 2, ItemCardapio::getTotalInstancias(), 'construcao invalida nao incrementa o contador');
$antesFuncionarios = Funcionario::getTotalFuncionarios();
new Garcom('Contador C', 1800.00);
new Gerente('Contador D', 4500.00);
Teste::igual($antesFuncionarios + 2, Funcionario::getTotalFuncionarios(), 'Funcionario::$totalFuncionarios conta as instancias criadas');

$proximo = Pedido::getProximoNumero();
$primeiro = new Pedido(1, 1);
$segundo = new Pedido(1, 1);
Teste::igual($proximo, $primeiro->getNumero(), 'Pedido recebe o proximo numero da sequencia');
Teste::igual($proximo + 1, $segundo->getNumero(), 'o numero seguinte e incrementado');
new Pedido(1, 1, null, $proximo + 500);
Teste::igual($proximo + 501, Pedido::getProximoNumero(), 'a sequencia continua depois do maior numero carregado');
Pedido::reiniciarSequencia();
Teste::igual(1, Pedido::getProximoNumero(), 'reiniciarSequencia() volta para 1');

Teste::igual(12, Mesa::CAPACIDADE_MAXIMA, 'Mesa::CAPACIDADE_MAXIMA = 12');
$equipeCheia = new Equipe();
for ($i = 1; $i <= Equipe::LIMITE_FUNCIONARIOS; $i++) {
    $equipeCheia->contratar(new Garcom('Garcom ' . $i, 1800.00));
}
Teste::lanca(DadosInvalidosException::class, static function () use ($equipeCheia): void {
    $equipeCheia->contratar(new Garcom('Garcom extra', 1800.00));
}, 'Equipe::LIMITE_FUNCIONARIOS impede contratar alem do limite');
Teste::lanca(DadosInvalidosException::class, static function () use ($cozinheiro): void {
    $cozinheiro->registrarHorasExtras(Cozinheiro::LIMITE_HORAS_EXTRAS + 1);
}, 'Cozinheiro::LIMITE_HORAS_EXTRAS impede horas extras demais');

Teste::grupo('7. Padroes de projeto: Singleton e Factory');
$restaurante = novoRestaurante($arquivoTemporario);
Teste::verdadeiro(Restaurante::getInstance() === Restaurante::getInstance(), 'Restaurante::getInstance() devolve sempre o mesmo objeto');
Teste::verdadeiro(Restaurante::getInstance() === $restaurante, 'a instancia e a mesma criada no inicio');
Teste::lanca(Error::class, static function (): void {
    $classe = 'Restaurante';
    new $classe(new ArmazenamentoJson('x.json'));
}, 'new Restaurante() e bloqueado (construtor privado)');
Teste::lanca(Error::class, static function () use ($restaurante): void {
    $copia = clone $restaurante;
}, 'clone do Restaurante e bloqueado');
Teste::lanca(RestauranteException::class, static function (): void {
    unserialize('O:11:"Restaurante":0:{}');
}, 'desserializar o Restaurante e bloqueado');

Teste::verdadeiro(ItemCardapioFactory::criar('prato', ['nome' => 'P', 'preco' => '10']) instanceof Prato, 'ItemCardapioFactory cria Prato');
Teste::verdadeiro(ItemCardapioFactory::criar('BEBIDA', ['nome' => 'B', 'preco' => '10']) instanceof Bebida, 'ItemCardapioFactory cria Bebida (ignora maiusculas)');
Teste::verdadeiro(ItemCardapioFactory::criar('sobremesa', ['nome' => 'S', 'preco' => '10']) instanceof Sobremesa, 'ItemCardapioFactory cria Sobremesa');
Teste::verdadeiro(FuncionarioFactory::criar('garcom', ['nome' => 'G', 'salario_base' => '1.800,00']) instanceof Garcom, 'FuncionarioFactory cria Garcom');
Teste::verdadeiro(FuncionarioFactory::criar('cozinheiro', ['nome' => 'C', 'salario_base' => '2400']) instanceof Cozinheiro, 'FuncionarioFactory cria Cozinheiro');
Teste::verdadeiro(FuncionarioFactory::criar('gerente', ['nome' => 'G', 'salario_base' => '4500', 'meta_mensal' => '1.000,00']) instanceof Gerente, 'FuncionarioFactory cria Gerente');
Teste::verdadeiro(PagamentoFactory::criar('dinheiro', ['valor_recebido' => '50']) instanceof PagamentoDinheiro, 'PagamentoFactory cria PagamentoDinheiro');
Teste::verdadeiro(PagamentoFactory::criar('pix') instanceof PagamentoPix, 'PagamentoFactory cria PagamentoPix');
Teste::verdadeiro(PagamentoFactory::criar('cartao', ['modalidade' => 'credito', 'parcelas' => '2']) instanceof PagamentoCartao, 'PagamentoFactory cria PagamentoCartao');
Teste::lanca(DadosInvalidosException::class, static function (): void {
    ItemCardapioFactory::criar('sushi', []);
}, 'ItemCardapioFactory recusa tipo desconhecido');
Teste::lanca(DadosInvalidosException::class, static function (): void {
    FuncionarioFactory::criar('chef', []);
}, 'FuncionarioFactory recusa cargo desconhecido');
Teste::lanca(PagamentoInvalidoException::class, static function (): void {
    PagamentoFactory::criar('cheque');
}, 'PagamentoFactory recusa forma desconhecida');
Teste::verdadeiro((new ReflectionMethod('Pagamento', 'processar'))->isFinal(), 'Pagamento::processar() e um Template Method (final)');

$suco = ItemCardapioFactory::criar('bebida', ['nome' => 'Suco', 'preco' => '10,50', 'volume_ml' => '300', 'gelada' => '0']);
Teste::aproximado(10.50, $suco->getPreco(), 'a Factory aceita preco com virgula');
Teste::verdadeiro($suco instanceof Bebida && !$suco->isGelada() && !$suco->isAlcoolica(), 'a Factory le checkbox desmarcado (gelada=0) e ausente (alcoolica)');

Teste::grupo('Persistencia em JSON');
$arquivoA = $arquivoTemporario;
$restaurante = novoRestaurante($arquivoA);
Teste::verdadeiro(is_file($arquivoA), 'o arquivo de dados e criado na primeira execucao');
$resumoInicial = $restaurante->resumo();
Teste::igual(13, $resumoInicial['itens_cardapio'], 'dados de exemplo: 13 itens no cardapio');
Teste::igual(5, $resumoInicial['funcionarios'], 'dados de exemplo: 5 funcionarios');
Teste::igual(8, $resumoInicial['mesas_total'], 'dados de exemplo: 8 mesas');
Teste::igual(2, $resumoInicial['mesas_ocupadas'], 'dados de exemplo: 2 mesas ocupadas');
Teste::igual(1, $resumoInicial['mesas_reservadas'], 'dados de exemplo: 1 mesa reservada');
Teste::igual(2, $resumoInicial['pedidos_andamento'], 'dados de exemplo: 2 pedidos em andamento');
Teste::aproximado(331.00, $resumoInicial['faturamento_dia'], 'dados de exemplo: faturamento do dia = 116,94 (Pix) + 214,06 (dinheiro)');
Teste::aproximado(13115.33, $resumoInicial['folha_estimada'], 'dados de exemplo: folha de pagamento = 13115,33');

$restaurante->salvar();
$primeiraLeitura = json_decode((string) file_get_contents($arquivoA), true);
Restaurante::reiniciarInstancia();
$recarregado = Restaurante::getInstance(new ArmazenamentoJson($arquivoA));
Teste::igual($resumoInicial, $recarregado->resumo(), 'recarregar do JSON reproduz o mesmo resumo');
$recarregado->salvar();
$segundaLeitura = json_decode((string) file_get_contents($arquivoA), true);
unset($primeiraLeitura['salvo_em'], $segundaLeitura['salvo_em']);
Teste::igual($primeiraLeitura, $segundaLeitura, 'gravar -> carregar -> gravar produz o mesmo JSON (ida e volta estavel)');

$pedidoPago = $recarregado->buscarPedido(1);
Teste::verdadeiro($pedidoPago->getPagamento() instanceof PagamentoPix, 'o pagamento recarregado volta como PagamentoPix (Factory)');
Teste::igual(StatusPedido::PAGO, $pedidoPago->getStatus(), 'o status do pedido pago e preservado');
Teste::aproximado(116.94, $pedidoPago->getTotalCobrado(), 'o valor cobrado do pedido pago e preservado');
Teste::igual('PG00001', $pedidoPago->getPagamento() === null ? '' : (string) $pedidoPago->getPagamento()->getCodigo(), 'o codigo do pagamento e preservado');
$pagamentoDinheiro = $recarregado->buscarPedido(2)->getPagamento();
Teste::verdadeiro($pagamentoDinheiro instanceof PagamentoDinheiro && abs($pagamentoDinheiro->getTroco() - 35.94) < 0.005, 'o troco do pagamento em dinheiro e preservado (35,94)');
Teste::verdadeiro($recarregado->getCardapio()->buscarPorId(9) instanceof Bebida, 'os itens recarregados mantem a subclasse (Bebida)');
Teste::verdadeiro($recarregado->getEquipe()->buscarPorId(5) instanceof Gerente, 'os funcionarios recarregados mantem a subclasse (Gerente)');
Teste::igual(5, Pedido::getProximoNumero(), 'a sequencia de pedidos continua depois dos pedidos carregados');

$corrompido = $arquivoTemporario . '.corrompido';
file_put_contents($corrompido, '{ isto nao e json');
Teste::lanca(ArmazenamentoException::class, static function () use ($corrompido): void {
    (new ArmazenamentoJson($corrompido))->carregar();
}, 'JSON corrompido lanca ArmazenamentoException');
file_put_contents($corrompido, "\xEF\xBB\xBF" . '{"a": 1}');
Teste::igual(['a' => 1], (new ArmazenamentoJson($corrompido))->carregar(), 'JSON com BOM (editor do Windows) e lido normalmente');
file_put_contents($corrompido, '');
Teste::igual([], (new ArmazenamentoJson($corrompido))->carregar(), 'arquivo vazio equivale a nenhum dado');
Teste::igual([], (new ArmazenamentoJson($arquivoTemporario . '.inexistente'))->carregar(), 'arquivo inexistente equivale a nenhum dado');
Teste::lanca(ArmazenamentoException::class, static function () use ($corrompido): void {
    (new ArmazenamentoJson($corrompido . DIRECTORY_SEPARATOR . 'subpasta' . DIRECTORY_SEPARATOR . 'dados.json'))->salvar(['a' => 1]);
}, 'gravar onde nao e possivel lanca ArmazenamentoException');
$restauranteResetado = $recarregado;
$restauranteResetado->buscarPedido(3)->cancelar();
$restauranteResetado->resetarParaDadosExemplo();
Teste::igual(2, $restauranteResetado->resumo()['pedidos_andamento'], 'resetarParaDadosExemplo() restaura os dados originais');
Teste::igual(1, $restauranteResetado->getPedidos()[0]->getNumero(), 'depois do reset a numeracao dos pedidos recomeca em 1');

Teste::grupo('Fluxo completo: abrir mesa, pedir, cozinha, pagar');
$restaurante = novoRestaurante($arquivoTemporario);
$cardapio = $restaurante->getCardapio();

$pedido = $restaurante->abrirPedido(7, 2, 2);
Teste::igual(5, $pedido->getNumero(), 'o novo pedido recebe o numero 5');
Teste::igual(Mesa::OCUPADA, $restaurante->buscarMesa(7)->getStatus(), 'a mesa 7 fica ocupada');
Teste::igual(5, $restaurante->buscarMesa(7)->getPedidoAtual(), 'a mesa guarda o pedido atual');
Teste::igual(StatusPedido::ABERTO, $pedido->getStatus(), 'o pedido comeca aberto');

$pedido->adicionarItem($cardapio->buscarPorId(1), 2);
$pedido->adicionarItem($cardapio->buscarPorId(6), 6);
$pedido->adicionarItem($cardapio->buscarPorId(12), 3, 'sem calda');
Teste::aproximado(208.60, $pedido->getSubtotal(), 'subtotal = 119,80 + 64,80 + 24,00');
$pedido->adicionarItem($cardapio->buscarPorId(1), 1);
Teste::igual(3, count($pedido->getItens()), 'item repetido soma na mesma linha');
Teste::aproximado(268.50, $pedido->getSubtotal(), 'subtotal depois de somar a 3a feijoada = 268,50');
Teste::aproximado(26.85, $pedido->getTaxaServico(), 'taxa de servico = 26,85');
Teste::aproximado(295.35, $pedido->getTotal(), 'total da conta = 295,35');

$pedido->removerItem(2);
Teste::igual(2, count($pedido->getItens()), 'removerItem() tira a linha (pedido aberto)');
$pedido->adicionarItem($cardapio->buscarPorId(12), 3, 'sem calda');
Teste::igual(3, count($pedido->getItens()), 'o item removido pode ser lancado de novo');

Teste::igual(StatusPedido::EM_PREPARO, $pedido->avancarStatus(), 'aberto -> em preparo');
Teste::igual(StatusPedido::PRONTO, $pedido->avancarStatus(), 'em preparo -> pronto');
$pedido->adicionarItem($cardapio->buscarPorId(8), 1);
Teste::igual(StatusPedido::EM_PREPARO, $pedido->getStatus(), 'item novo em pedido pronto volta para a cozinha');
$pedido->avancarStatus();
Teste::igual(StatusPedido::ENTREGUE, $pedido->avancarStatus(), 'pronto -> entregue');
Teste::lanca(PedidoInvalidoException::class, static function () use ($pedido): void {
    $pedido->avancarStatus();
}, 'entregue nao avanca: precisa ser pago');
Teste::aproximado(273.50, $pedido->getSubtotal(), 'subtotal com a agua = 273,50');
$totalDaConta = $pedido->getTotal();

$tentativa = capturar(static function () use ($restaurante): void {
    $restaurante->pagarPedido(5, new PagamentoCartao('credito', 3));
});
Teste::verdadeiro($tentativa === null, 'pagamento no credito 3x e aprovado');
$pedido = $restaurante->buscarPedido(5);
Teste::igual(StatusPedido::PAGO, $pedido->getStatus(), 'o pedido fica pago');
Teste::aproximado(Util::arredondar($totalDaConta * 1.04), $pedido->getTotalCobrado(), 'cobrado = total + 4% de juros do credito 3x');
Teste::igual('PG00005', (string) ($pedido->getPagamento() === null ? '' : $pedido->getPagamento()->getCodigo()), 'codigo do pagamento = PG + numero do pedido');
Teste::igual(Mesa::LIVRE, $restaurante->buscarMesa(7)->getStatus(), 'pagar libera a mesa');
Teste::verdadeiro($restaurante->buscarMesa(7)->getPedidoAtual() === null, 'a mesa nao guarda mais o pedido');
$comprovante = $pedido->getPagamento() === null ? [] : $pedido->getPagamento()->getComprovante();
Teste::verdadeiro(isset($comprovante['Parcelas']) && strpos($comprovante['Parcelas'], '3x') === 0, 'o comprovante do cartao informa as parcelas');

$mariana = $restaurante->getEquipe()->buscarPorId(2);
Teste::verdadeiro($mariana instanceof Garcom && abs($mariana->getVendasNoMes() - (194.60 + 273.50)) < 0.005, 'o garcom do pedido acumula as vendas (194,60 + 273,50)');
Teste::verdadeiro($mariana instanceof Garcom && $mariana->getMesasAtendidas() === 2, 'o garcom acumula as mesas atendidas');
$carlos = $restaurante->getEquipe()->buscarPorId(1);
Teste::verdadeiro($carlos instanceof Garcom && abs($carlos->getVendasNoMes() - 111.90) < 0.005, 'o outro garcom nao ganha com a venda alheia');
$roberto = $restaurante->getEquipe()->buscarPorId(5);
Teste::verdadeiro($roberto instanceof Gerente && abs($roberto->getFaturamentoEquipe() - (111.90 + 194.60 + 273.50)) < 0.005, 'o gerente acumula o faturamento de todos os pedidos pagos');

Teste::aproximado(331.00 + Util::arredondar($totalDaConta * 1.04), $restaurante->faturamentoDoDia(), 'faturamento do dia soma o novo pedido pago');
$porForma = $restaurante->faturamentoPorForma();
Teste::igual(1, $porForma['cartao']['quantidade'], 'faturamento por forma: 1 pagamento no cartao');
Teste::igual(1, $porForma['pix']['quantidade'], 'faturamento por forma: 1 pagamento no Pix');
Teste::igual(1, $porForma['dinheiro']['quantidade'], 'faturamento por forma: 1 pagamento em dinheiro');
Teste::aproximado(116.94, $porForma['pix']['total'], 'faturamento por forma: Pix = 116,94');

Teste::lanca(PedidoInvalidoException::class, static function () use ($restaurante): void {
    $restaurante->pagarPedido(5, new PagamentoPix());
}, 'nao e possivel pagar o mesmo pedido duas vezes');

$cancelavel = $restaurante->abrirPedido(8, 3, 1);
$cancelavel->adicionarItem($cardapio->buscarPorId(3), 1);
$faturamentoAntes = $restaurante->faturamentoDoDia();
$restaurante->cancelarPedido($cancelavel->getNumero());
Teste::igual(StatusPedido::CANCELADO, $cancelavel->getStatus(), 'cancelar muda o status para cancelado');
Teste::igual(Mesa::LIVRE, $restaurante->buscarMesa(8)->getStatus(), 'cancelar libera a mesa');
Teste::aproximado($faturamentoAntes, $restaurante->faturamentoDoDia(), 'pedido cancelado nao entra no faturamento');
Teste::lanca(PedidoInvalidoException::class, static function () use ($cancelavel, $cardapio): void {
    $cancelavel->adicionarItem($cardapio->buscarPorId(1), 1);
}, 'pedido cancelado nao aceita itens');

$restaurante->abrirPedido(6, 4, 1);
Teste::igual(Mesa::OCUPADA, $restaurante->buscarMesa(6)->getStatus(), 'mesa reservada pode ser aberta pelo grupo');
Teste::verdadeiro($restaurante->buscarMesa(6)->getReservadaPara() === null, 'a reserva some quando a mesa e aberta');
$restaurante->reservarMesa(2, 'Maria');
Teste::igual(Mesa::RESERVADA, $restaurante->buscarMesa(2)->getStatus(), 'mesa livre pode ser reservada');
$restaurante->liberarMesa(2);
Teste::igual(Mesa::LIVRE, $restaurante->buscarMesa(2)->getStatus(), 'cancelar a reserva libera a mesa');

$restaurante->salvar();
$resumoAntes = $restaurante->resumo();
Restaurante::reiniciarInstancia();
$restaurante = Restaurante::getInstance(new ArmazenamentoJson($arquivoTemporario));
Teste::igual($resumoAntes, $restaurante->resumo(), 'depois do fluxo, gravar e recarregar preserva tudo');
$pedidoRecarregado = $restaurante->buscarPedido(5);
Teste::verdadeiro($pedidoRecarregado->getPagamento() instanceof PagamentoCartao, 'o pagamento no cartao volta como PagamentoCartao');
Teste::igual(3, $pedidoRecarregado->getPagamento() instanceof PagamentoCartao ? $pedidoRecarregado->getPagamento()->getParcelas() : 0, 'as parcelas do cartao foram preservadas');
Teste::aproximado(273.50, $pedidoRecarregado->getSubtotal(), 'os itens do pedido pago foram preservados (subtotal)');

Teste::grupo('Utilitarios de conversao e formatacao');
Teste::igual('R$ 1.234,50', Util::moeda(1234.5), 'Util::moeda() formata em reais');
Teste::igual('10%', Util::percentual(0.10), 'Util::percentual(0.10) = 10%');
Teste::igual('12,5%', Util::percentual(0.125), 'Util::percentual(0.125) = 12,5%');
Teste::igual(59.9, Util::paraFloat('59,90'), 'paraFloat aceita virgula decimal');
Teste::igual(1234.56, Util::paraFloat('1.234,56'), 'paraFloat aceita ponto de milhar com virgula decimal');
Teste::igual(59.9, Util::paraFloat('59.9'), 'paraFloat aceita ponto decimal');
Teste::igual(12.5, Util::paraFloat('R$ 12,50'), 'paraFloat ignora o simbolo R$');
Teste::lanca(DadosInvalidosException::class, static function (): void {
    Util::paraFloat('abc');
}, 'paraFloat recusa texto');
Teste::lanca(DadosInvalidosException::class, static function (): void {
    Util::paraFloat('');
}, 'paraFloat recusa vazio');
Teste::lanca(DadosInvalidosException::class, static function (): void {
    Util::paraFloat(['x']);
}, 'paraFloat recusa array');
Teste::igual(12, Util::paraInt('12'), 'paraInt converte texto numerico');
Teste::igual(7, Util::paraInt(' 7 '), 'paraInt ignora espacos');
Teste::lanca(DadosInvalidosException::class, static function (): void {
    Util::paraInt('1.5');
}, 'paraInt recusa decimal');
Teste::lanca(DadosInvalidosException::class, static function (): void {
    Util::paraInt('abc');
}, 'paraInt recusa texto');
Teste::verdadeiro(Util::paraBool('1') && Util::paraBool('on') && Util::paraBool(true) && !Util::paraBool('0') && !Util::paraBool(''), 'paraBool interpreta checkbox');
Teste::igual('&lt;b&gt; &amp; &quot;x&quot;', Util::escapar('<b> & "x"'), 'Util::escapar() protege o HTML');
Teste::igual('a b', Util::limparTexto("  a   \n b  "), 'limparTexto() remove espacos repetidos');

Teste::grupo('Autoria: cabecalho em todo arquivo de codigo (exigido pelo enunciado)');
$analisador = new AnalisadorAutoria($raiz);
$analise = $analisador->analisar();
Teste::igual([], $analise['sem_cabecalho'], 'todo arquivo php/js/css tem cabecalho Classe|Arquivo / Responsavel / Descricao');
Teste::verdadeiro(count($analise['arquivos']) >= 45, 'o analisador leu ' . count($analise['arquivos']) . ' arquivos com cabecalho');

$divergentes = [];
foreach ($analise['arquivos'] as $caminho => $cabecalho) {
    if ($cabecalho['tipo'] === 'Classe' && $cabecalho['nome'] !== basename($caminho, '.php')) {
        $divergentes[] = $caminho;
    }
}
Teste::igual([], $divergentes, 'o nome em "Classe:" e o mesmo do arquivo');

$integrantes = array_keys($analisador->agruparPorIntegrante());
$esperados = ['Danilo Sales Coutinho', 'Erik Carvalho Nogueira', 'Felipe Ferreira Rosas'];
sort($integrantes);
Teste::igual($esperados, $integrantes, 'os 3 integrantes do grupo (e so eles) aparecem como responsaveis');
foreach ($analisador->agruparPorIntegrante() as $nome => $dados) {
    Teste::verdadeiro(count($dados['arquivos']) >= 8, "$nome e responsavel por " . count($dados['arquivos']) . ' arquivos');
}

$exigemClasse = [];
foreach (glob($raiz . '/src/*/*.php') ?: [] as $arquivo) {
    $caminho = 'src/' . basename(dirname($arquivo)) . '/' . basename($arquivo);
    if (!isset($analise['arquivos'][$caminho]) || $analise['arquivos'][$caminho]['tipo'] !== 'Classe') {
        $exigemClasse[] = $caminho;
    }
}
Teste::igual([], $exigemClasse, 'todo arquivo de src/ comeca com o cabecalho "Classe:" do enunciado');

$pendentes = $analisador->integrantesComRaPendente();
if (count($pendentes) > 0) {
    Teste::aviso('RA ainda provisorio (' . AnalisadorAutoria::RA_PENDENTE . ') para: ' . implode(', ', $pendentes)
        . '. Defina com: php scripts/autoria.php --definir-ra "Nome Completo=SEU_RA"');
}

exit(Teste::resumo());
