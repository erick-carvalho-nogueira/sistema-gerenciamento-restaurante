<?php
/**
 * Arquivo: smoke_http.php
 * Responsável: Erik Carvalho Nogueira (RA 2026106090008)
 * Descrição: teste de fumaça HTTP — visita todas as páginas e percorre o fluxo completo de um pedido (mesa, itens, cozinha, pagamento).
 * Uso: php tests/smoke_http.php                          sobe o servidor embutido do PHP com dados temporários (não altera data/)
 *      php tests/smoke_http.php --url=http://localhost/restaurante/   testa um Apache já em execução (ex.: o XAMPP)
 * Atenção: com --url o teste restaura os dados de exemplo do sistema testado antes de começar e ao terminar.
 */
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

require __DIR__ . '/Teste.php';
require __DIR__ . '/ClienteHttp.php';

$urlExterna = null;
foreach (array_slice($argv, 1) as $argumento) {
    if (strpos($argumento, '--url=') === 0) {
        $urlExterna = substr($argumento, 6);
    }
}

$raiz = dirname(__DIR__);
$dadosReaisExistiam = is_file($raiz . '/data/restaurante.json');
$arquivoDeDados = null;

if ($urlExterna === null) {
    $prefixo = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'restaurante_http_' . getmypid();
    $arquivoDeDados = $prefixo . '.json';
    $arquivoDeLog = $prefixo . '.log';
    @unlink($arquivoDeDados);

    $soquete = @stream_socket_server('tcp://127.0.0.1:0', $codigoDoErro, $mensagemDoErro);
    if ($soquete === false) {
        fwrite(STDERR, 'Nao foi possivel reservar uma porta: ' . $mensagemDoErro . PHP_EOL);
        exit(2);
    }
    $porta = (int) substr((string) strrchr((string) stream_socket_get_name($soquete, false), ':'), 1);
    fclose($soquete);

    $ambiente = ['RESTAURANTE_DADOS' => $arquivoDeDados];
    foreach (getenv() as $nome => $valor) {
        if ($nome !== '' && $nome[0] !== '=') {
            $ambiente[$nome] = $valor;
        }
    }

    $servidor = proc_open(
        [PHP_BINARY, '-S', '127.0.0.1:' . $porta, '-t', $raiz],
        [0 => ['pipe', 'r'], 1 => ['file', $arquivoDeLog, 'w'], 2 => ['file', $arquivoDeLog, 'a']],
        $canais,
        $raiz,
        $ambiente
    );
    if (!is_resource($servidor)) {
        fwrite(STDERR, 'Nao foi possivel iniciar o servidor embutido do PHP.' . PHP_EOL);
        exit(2);
    }
    register_shutdown_function(static function () use (&$servidor, $arquivoDeDados, $arquivoDeLog): void {
        if (is_resource($servidor)) {
            proc_terminate($servidor);
            proc_close($servidor);
        }
        @unlink($arquivoDeDados);
        @unlink($arquivoDeLog);
    });

    $pronto = false;
    for ($tentativa = 0; $tentativa < 80 && !$pronto; $tentativa++) {
        $conexao = @fsockopen('127.0.0.1', $porta, $codigo, $mensagem, 0.25);
        if ($conexao !== false) {
            fclose($conexao);
            $pronto = true;
        } else {
            usleep(100000);
        }
    }
    if (!$pronto) {
        fwrite(STDERR, 'O servidor nao respondeu a tempo.' . PHP_EOL);
        exit(2);
    }
    $base = 'http://127.0.0.1:' . $porta;
    echo 'Servidor embutido do PHP em ' . $base . ' (dados temporarios)' . PHP_EOL;
} else {
    $base = rtrim($urlExterna, '/');
    echo 'Testando o servidor em ' . $base . PHP_EOL;
    echo 'ATENCAO: os dados de exemplo desse sistema serao restaurados antes e depois do teste.' . PHP_EOL;
}

$cliente = new ClienteHttp($base);

if ($urlExterna !== null) {
    $restauracao = $cliente->post('index.php', ['acao' => 'resetar']);
    Teste::igual(302, $restauracao['status'], 'dados de exemplo restaurados no sistema testado (estado inicial conhecido)');
}

function conferirPagina(array $resposta, string $nome, string $trecho = ''): void
{
    Teste::igual(200, $resposta['status'], "$nome responde 200");
    Teste::verdadeiro(
        preg_match('/(Fatal error|Warning:|Notice:|Deprecated:|Parse error|Uncaught|Stack trace)/i', $resposta['corpo']) !== 1,
        "$nome sem erros ou avisos do PHP"
    );
    if ($trecho !== '') {
        Teste::verdadeiro(strpos($resposta['corpo'], $trecho) !== false, "$nome mostra o conteudo esperado (\"" . $trecho . '")');
    }
}

function seguirRedirecionamento(ClienteHttp $cliente, array $resposta, string $destinoEsperado, string $descricao): array
{
    Teste::igual(302, $resposta['status'], "$descricao redireciona (302)");
    Teste::igual($destinoEsperado, $resposta['local'], "$descricao vai para $destinoEsperado");
    return $cliente->get((string) $resposta['local']);
}

Teste::grupo('Paginas (GET) com os dados de exemplo');
conferirPagina($cliente->get('index.php'), 'index.php', 'Painel do dia');
if ($arquivoDeDados !== null) {
    Teste::verdadeiro(is_file($arquivoDeDados), 'o arquivo de dados foi criado sozinho na primeira visita');
}
conferirPagina($cliente->get('mesas.php'), 'mesas.php', 'Mesas do salão');
conferirPagina($cliente->get('pedidos.php'), 'pedidos.php', 'Em andamento');
conferirPagina($cliente->get('pedidos.php?status=todos'), 'pedidos.php?status=todos', 'Fechou');
conferirPagina($cliente->get('pedidos.php?status=invalido'), 'pedidos.php com filtro invalido', 'Comandas');
conferirPagina($cliente->get('cardapio.php'), 'cardapio.php', 'Feijoada Completa');
conferirPagina($cliente->get('cardapio.php?codigo=1'), 'cardapio.php?codigo=1', 'Feijoada Completa');
conferirPagina($cliente->get('cardapio.php?codigo=9999'), 'busca de item inexistente (ItemNaoEncontradoException tratada)', 'Item não encontrado');
conferirPagina($cliente->get('cardapio.php?codigo=abc'), 'busca com codigo invalido (DadosInvalidosException tratada)', 'Dados inválidos');
conferirPagina($cliente->get('funcionarios.php'), 'funcionarios.php', 'Total da folha');
conferirPagina($cliente->get('pedido.php?numero=3'), 'pedido.php?numero=3', 'Comanda nº 3');
conferirPagina($cliente->get('pedido.php?numero=1'), 'pedido pago (comprovante)', 'Comprovante de pagamento');
conferirPagina($cliente->get('demo.php'), 'demo.php (Laboratorio POO)', 'Singleton');
Teste::verdadeiro(strpos($cliente->get('demo.php')['corpo'], '0 atributos públicos') !== false, 'demo.php confirma 0 atributos publicos por reflexao');
Teste::igual(200, $cliente->get('assets/style.css')['status'], 'assets/style.css e servido');
Teste::igual(200, $cliente->get('assets/app.js')['status'], 'assets/app.js e servido');
Teste::igual(404, $cliente->get('nao-existe.php')['status'], 'pagina inexistente responde 404');

$naoExiste = $cliente->get('pedido.php?numero=999');
Teste::igual(302, $naoExiste['status'], 'pedido inexistente redireciona para a lista');
conferirPagina($cliente->get('pedidos.php'), 'lista apos pedido inexistente', 'Não existe o pedido nº 999');

$pagamentoDePago = $cliente->get('pagamento.php?pedido=1');
Teste::igual(302, $pagamentoDePago['status'], 'tela de pagamento de pedido ja pago redireciona');
conferirPagina($cliente->get('pedido.php?numero=1'), 'pedido pago apos redirecionamento', 'só é possível pagar pedidos já entregues');

Teste::grupo('Fluxo completo pelo navegador: mesa -> pedido -> cozinha -> pagamento');
$pagina = seguirRedirecionamento(
    $cliente,
    $cliente->post('mesas.php', ['acao' => 'abrir', 'mesa' => '7', 'pessoas' => '2', 'garcom' => '2']),
    'pedido.php?numero=5',
    'abrir a mesa 7'
);
conferirPagina($pagina, 'pedido #5 recem-aberto', 'Mesa aberta!');

$pagina = seguirRedirecionamento(
    $cliente,
    $cliente->post('pedido.php?numero=5', ['acao' => 'adicionar_item', 'item' => '1', 'quantidade' => '2', 'observacao' => 'ao ponto']),
    'pedido.php?numero=5',
    'adicionar a feijoada'
);
conferirPagina($pagina, 'pedido #5 com a feijoada', 'Feijoada Completa');
Teste::verdadeiro(strpos($pagina['corpo'], 'ao ponto') !== false, 'a observacao do item aparece no pedido');
Teste::verdadeiro(strpos($pagina['corpo'], 'Item adicionado ao pedido.') !== false, 'o aviso de sucesso aparece apos o redirecionamento (sessao)');

$pagina = seguirRedirecionamento(
    $cliente,
    $cliente->post('pedido.php?numero=5', ['acao' => 'adicionar_item', 'item' => '9999', 'quantidade' => '1']),
    'pedido.php?numero=5',
    'adicionar item que nao existe'
);
Teste::verdadeiro(strpos($pagina['corpo'], 'Não existe item no cardápio com o código #9999') !== false, 'item inexistente mostra o erro (ItemNaoEncontradoException)');

$pagina = seguirRedirecionamento(
    $cliente,
    $cliente->post('pedido.php?numero=5', ['acao' => 'adicionar_item', 'item' => '6', 'quantidade' => '99']),
    'pedido.php?numero=5',
    'adicionar quantidade absurda'
);
Teste::verdadeiro(strpos($pagina['corpo'], 'Quantidade inválida') !== false, 'quantidade acima do limite mostra o erro (PedidoInvalidoException)');

$pagina = seguirRedirecionamento(
    $cliente,
    $cliente->post('pedido.php?numero=5', ['acao' => 'adicionar_item', 'item' => '6', 'quantidade' => '6']),
    'pedido.php?numero=5',
    'adicionar 6 sucos'
);
Teste::verdadeiro(strpos($pagina['corpo'], 'Suco de Cupuaçu') !== false, 'os sucos aparecem no pedido');

$pagina = seguirRedirecionamento(
    $cliente,
    $cliente->post('mesas.php', ['acao' => 'abrir', 'mesa' => '4', 'pessoas' => '2', 'garcom' => '1']),
    'mesas.php',
    'abrir mesa ja ocupada'
);
Teste::verdadeiro(strpos($pagina['corpo'], 'A mesa 4 já está ocupada.') !== false, 'mesa ocupada mostra o erro (MesaIndisponivelException)');

$cliente->post('pedido.php?numero=5', ['acao' => 'avancar']);
$cliente->post('pedido.php?numero=5', ['acao' => 'avancar']);
$pagina = seguirRedirecionamento(
    $cliente,
    $cliente->post('pedido.php?numero=5', ['acao' => 'avancar']),
    'pedido.php?numero=5',
    'avancar ate entregue'
);
conferirPagina($pagina, 'pedido #5 entregue', 'Fechar a conta');

conferirPagina($cliente->get('pagamento.php?pedido=5'), 'pagamento.php?pedido=5', 'Quanto fica em cada forma de pagamento');

$pagina = seguirRedirecionamento(
    $cliente,
    $cliente->post('pagamento.php?pedido=5', ['forma' => 'dinheiro', 'valor_recebido' => '10,00']),
    'pagamento.php?pedido=5',
    'pagar com dinheiro insuficiente'
);
Teste::verdadeiro(strpos($pagina['corpo'], 'é menor que o valor a pagar') !== false, 'dinheiro insuficiente mostra o erro (PagamentoInvalidoException)');

$pagina = seguirRedirecionamento(
    $cliente,
    $cliente->post('pagamento.php?pedido=5', ['forma' => 'cartao', 'modalidade' => 'debito', 'parcelas' => '3']),
    'pagamento.php?pedido=5',
    'debito parcelado'
);
Teste::verdadeiro(strpos($pagina['corpo'], 'Cartão de débito só permite pagamento à vista') !== false, 'debito parcelado mostra o erro');

$pagina = seguirRedirecionamento(
    $cliente,
    $cliente->post('pagamento.php?pedido=5', ['forma' => 'pix']),
    'pedido.php?numero=5',
    'pagar com Pix'
);
conferirPagina($pagina, 'pedido #5 pago', 'Comprovante de pagamento');
Teste::verdadeiro(strpos($pagina['corpo'], 'Pagamento aprovado!') !== false, 'o aviso de pagamento aprovado aparece');
Teste::verdadeiro(strpos($pagina['corpo'], 'Desconto Pix') !== false, 'o comprovante mostra os detalhes da forma de pagamento (Pix)');
Teste::verdadeiro(strpos($pagina['corpo'], 'PG00005') !== false, 'o comprovante mostra o codigo PG00005');

$mesas = $cliente->get('mesas.php');
Teste::verdadeiro(strpos($mesas['corpo'], 'class="cartao mesa mesa-livre" id="mesa-7"') !== false, 'a mesa 7 voltou a ficar livre');

$pagina = seguirRedirecionamento(
    $cliente,
    $cliente->post('pagamento.php?pedido=5', ['forma' => 'pix']),
    'pedido.php?numero=5',
    'tentar pagar de novo'
);
Teste::verdadeiro(strpos($pagina['corpo'], 'só é possível pagar pedidos já entregues') !== false, 'pagar duas vezes e recusado');

Teste::grupo('Cardapio e equipe pelo navegador');
$pagina = seguirRedirecionamento(
    $cliente,
    $cliente->post('cardapio.php', ['acao' => 'adicionar', 'tipo' => 'bebida', 'nome' => 'Chá Gelado', 'preco' => '8,50', 'volume_ml' => '400', 'gelada' => '1']),
    'cardapio.php',
    'cadastrar bebida'
);
Teste::verdadeiro(strpos($pagina['corpo'], 'Chá Gelado') !== false, 'a bebida cadastrada aparece no cardapio (Factory criou uma Bebida)');
Teste::verdadeiro(strpos($pagina['corpo'], 'Item cadastrado no cardápio.') !== false, 'aviso de item cadastrado');

$pagina = seguirRedirecionamento(
    $cliente,
    $cliente->post('cardapio.php', ['acao' => 'adicionar', 'tipo' => 'prato', 'nome' => 'Prato Gratis', 'preco' => '0']),
    'cardapio.php',
    'cadastrar prato com preco invalido'
);
Teste::verdadeiro(strpos($pagina['corpo'], 'deve estar entre R$ 0,50') !== false, 'preco invalido mostra o erro (DadosInvalidosException)');

$pagina = seguirRedirecionamento(
    $cliente,
    $cliente->post('cardapio.php', ['acao' => 'remover', 'id' => '14']),
    'cardapio.php',
    'remover o item 14'
);
Teste::verdadeiro(strpos($pagina['corpo'], 'Item removido do cardápio.') !== false, 'aviso de item removido');
conferirPagina($cliente->get('cardapio.php?codigo=14'), 'item removido nao e mais encontrado', 'Item não encontrado');

$pagina = seguirRedirecionamento(
    $cliente,
    $cliente->post('funcionarios.php', ['acao' => 'contratar', 'tipo' => 'garcom', 'nome' => 'Teste Garçom', 'salario_base' => '1.800,00', 'admissao' => '2026-11-01']),
    'funcionarios.php',
    'contratar garcom'
);
Teste::verdadeiro(strpos($pagina['corpo'], 'Teste Garçom') !== false, 'o garcom contratado aparece na equipe (Factory criou um Garcom)');

$pagina = seguirRedirecionamento(
    $cliente,
    $cliente->post('funcionarios.php', ['acao' => 'horas_extras', 'id' => '3', 'horas' => '10']),
    'funcionarios.php',
    'registrar horas extras do cozinheiro'
);
Teste::verdadeiro(strpos($pagina['corpo'], 'Horas extras registradas.') !== false, 'horas extras registradas');

$pagina = seguirRedirecionamento(
    $cliente,
    $cliente->post('funcionarios.php', ['acao' => 'horas_extras', 'id' => '1', 'horas' => '5']),
    'funcionarios.php',
    'horas extras para quem nao e cozinheiro'
);
Teste::verdadeiro(strpos($pagina['corpo'], 'não é cozinheiro') !== false, 'horas extras para garcom mostram o erro');

$pagina = seguirRedirecionamento(
    $cliente,
    $cliente->post('funcionarios.php', ['acao' => 'demitir', 'id' => '6']),
    'funcionarios.php',
    'desligar o garcom contratado'
);
Teste::verdadeiro(strpos($pagina['corpo'], 'Funcionário desligado da equipe.') !== false, 'funcionario desligado');

$pagina = seguirRedirecionamento(
    $cliente,
    $cliente->post('funcionarios.php', ['acao' => 'demitir', 'id' => '777']),
    'funcionarios.php',
    'desligar funcionario inexistente'
);
Teste::verdadeiro(strpos($pagina['corpo'], 'Não existe funcionário com o código #777') !== false, 'funcionario inexistente mostra o erro (FuncionarioNaoEncontradoException)');

Teste::grupo('Reservas, cancelamento e restauracao dos dados');
$pagina = seguirRedirecionamento(
    $cliente,
    $cliente->post('mesas.php', ['acao' => 'reservar', 'mesa' => '2', 'nome' => 'Familia Silva']),
    'mesas.php',
    'reservar a mesa 2'
);
Teste::verdadeiro(strpos($pagina['corpo'], 'Familia Silva') !== false, 'a reserva aparece na mesa');

$pagina = seguirRedirecionamento(
    $cliente,
    $cliente->post('mesas.php', ['acao' => 'liberar', 'mesa' => '4']),
    'mesas.php',
    'liberar mesa com pedido em andamento'
);
Teste::verdadeiro(strpos($pagina['corpo'], 'pague ou cancele o pedido para liberá-la') !== false, 'mesa com pedido nao pode ser liberada');

$pagina = seguirRedirecionamento(
    $cliente,
    $cliente->post('pedido.php?numero=4', ['acao' => 'cancelar']),
    'pedido.php?numero=4',
    'cancelar o pedido 4'
);
Teste::verdadeiro(strpos($pagina['corpo'], 'Pedido cancelado e mesa liberada.') !== false, 'pedido cancelado');

$pagina = seguirRedirecionamento(
    $cliente,
    $cliente->post('index.php', ['acao' => 'resetar']),
    'index.php',
    'restaurar dados de exemplo'
);
conferirPagina($pagina, 'painel apos restaurar', 'Dados de exemplo restaurados.');
$lista = $cliente->get('pedidos.php?status=todos');
Teste::verdadeiro(strpos($lista['corpo'], '#5') === false, 'depois de restaurar nao existe mais o pedido 5');

if ($urlExterna === null) {
    Teste::igual($dadosReaisExistiam, is_file($raiz . '/data/restaurante.json'), 'o teste nao criou nem apagou data/restaurante.json');
} else {
    Teste::grupo('Protecao das pastas internas pelo .htaccess (Apache)');
    foreach (['data/', 'src/autoload.php', 'src/Infra/Config.php', 'includes/bootstrap.php', 'tests/run_tests.php', 'scripts/autoria.php'] as $caminho) {
        $resposta = $cliente->get($caminho);
        if ($resposta['status'] === 403) {
            Teste::verdadeiro(true, "$caminho esta bloqueado para o navegador (403)");
        } else {
            Teste::aviso("$caminho respondeu " . $resposta['status'] . ' (no Apache do XAMPP o esperado e 403; este servidor ignora o .htaccess?)');
        }
    }
}

exit(Teste::resumo());
