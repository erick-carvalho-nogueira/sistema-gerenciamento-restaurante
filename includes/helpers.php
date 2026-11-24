<?php
/**
 * Arquivo: helpers.php
 * Responsável: Erik Carvalho Nogueira (RA 2026106090008)
 * Descrição: funções auxiliares das páginas (escape de HTML, leitura de formulários, avisos entre páginas e execução de ações com try/catch).
 */
declare(strict_types=1);

function e($valor): string
{
    return Util::escapar($valor);
}

function moeda(float $valor): string
{
    return Util::moeda($valor);
}

function ehPost(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function post(string $chave, string $padrao = ''): string
{
    return lerEntrada($_POST, $chave, $padrao);
}

function consulta(string $chave, string $padrao = ''): string
{
    return lerEntrada($_GET, $chave, $padrao);
}

function lerEntrada(array $origem, string $chave, string $padrao): string
{
    $valor = $origem[$chave] ?? $padrao;
    return is_scalar($valor) ? trim((string) $valor) : $padrao;
}

function redirecionar(string $destino): void
{
    header('Location: ' . $destino);
    exit;
}

function iniciarSessao(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        @session_start();
    }
}

function avisar(string $tipo, string $mensagem): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        $_SESSION['avisos'][] = ['tipo' => $tipo, 'mensagem' => $mensagem];
    }
}

function consumirAvisos(): array
{
    if (session_status() !== PHP_SESSION_ACTIVE || empty($_SESSION['avisos']) || !is_array($_SESSION['avisos'])) {
        return [];
    }
    $avisos = $_SESSION['avisos'];
    $_SESSION['avisos'] = [];
    return $avisos;
}

function executarAcao(callable $acao, string $mensagemDeSucesso): bool
{
    try {
        $acao();
        Restaurante::getInstance()->salvar();
        avisar('sucesso', $mensagemDeSucesso);
        return true;
    } catch (RestauranteException $excecao) {
        avisar('erro', $excecao->getTitulo() . ': ' . $excecao->getMessage());
        return false;
    }
}

function restaurante(): Restaurante
{
    try {
        return Restaurante::getInstance();
    } catch (RestauranteException $excecao) {
        paginaDeErro($excecao->getTitulo(), $excecao->getMessage());
    }
}

function rotuloDoStatus(string $status): string
{
    static $voz = [
        StatusPedido::ABERTO => 'Anotado',
        StatusPedido::EM_PREPARO => 'Na chapa',
        StatusPedido::PRONTO => 'No balcão',
        StatusPedido::ENTREGUE => 'Na mesa',
        StatusPedido::PAGO => 'Fechou',
        StatusPedido::CANCELADO => 'Furou',
    ];
    return $voz[$status] ?? StatusPedido::rotulo($status);
}

function seloStatus(string $status): string
{
    return '<span class="selo status-' . e($status) . '">' . e(rotuloDoStatus($status)) . '</span>';
}

function urlDoAsset(string $caminho): string
{
    static $urls = [];
    if (!isset($urls[$caminho])) {
        $arquivo = __DIR__ . '/../' . $caminho;
        $urls[$caminho] = is_file($arquivo) ? $caminho . '?v=' . filemtime($arquivo) : $caminho;
    }
    return $urls[$caminho];
}

function nomeDoGarcom(Restaurante $restaurante, ?int $idGarcom): string
{
    if ($idGarcom === null) {
        return '—';
    }
    try {
        return $restaurante->getEquipe()->buscarPorId($idGarcom)->getNome();
    } catch (FuncionarioNaoEncontradoException $excecao) {
        return 'ex-funcionário (#' . $idGarcom . ')';
    }
}
