<?php
/**
 * Arquivo: layout.php
 * Responsável: Erik Carvalho Nogueira (RA 2026106090008)
 * Descrição: estrutura HTML comum das páginas (cabeçalho, menu, avisos, rodapé) e a página de erro amigável.
 */
declare(strict_types=1);

function filtroDeCarimbo(): string
{
    return '<svg width="0" height="0" style="position:absolute" aria-hidden="true" focusable="false">'
        . '<filter id="rugoso" x="-8%" y="-8%" width="116%" height="116%">'
        . '<feTurbulence type="fractalNoise" baseFrequency="0.045 0.07" numOctaves="2" seed="4" result="ruido"/>'
        . '<feDisplacementMap in="SourceGraphic" in2="ruido" scale="3.2"/></filter></svg>';
}

function topoDoBoteco(array $menu = [], string $paginaAtiva = ''): void
{
    ?>
<header class="topo">
    <div class="faixa-madeira">
        <div class="container topo-conteudo">
            <a class="marca" href="index.php"><b><?= e(Config::NOME_RESTAURANTE) ?></b><small>PF · petiscos · cerveja gelada</small></a>
            <?php if (count($menu) > 0): ?>
                <nav class="menu" aria-label="Principal">
                    <?php foreach ($menu as $arquivo => $rotulo): ?>
                        <a href="<?= e($arquivo) ?>"<?= $arquivo === $paginaAtiva ? ' class="ativo" aria-current="page"' : '' ?>><?= e($rotulo) ?></a>
                    <?php endforeach; ?>
                </nav>
            <?php endif; ?>
        </div>
    </div>
    <div class="azulejos" aria-hidden="true"></div>
</header>
<?php
}

function cabecalho(string $titulo, string $paginaAtiva = ''): void
{
    $menu = [
        'index.php' => 'Balcão',
        'mesas.php' => 'Salão',
        'pedidos.php' => 'Comandas',
        'cardapio.php' => 'Cardápio',
        'funcionarios.php' => 'Turma',
        'demo.php' => 'Receitas',
    ];

    if (!headers_sent()) {
        header('Content-Type: text/html; charset=UTF-8');
    }
    ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($titulo) ?> — <?= e(Config::NOME_RESTAURANTE) ?></title>
    <link rel="stylesheet" href="<?= e(urlDoAsset('assets/style.css')) ?>">
</head>
<body class="pagina-<?= e(basename($paginaAtiva, '.php')) ?>">
<?= filtroDeCarimbo() ?>
<a class="pular" href="#conteudo">Pular para o conteúdo</a>
<?php topoDoBoteco($menu, $paginaAtiva); ?>
<main class="container" id="conteudo">
    <?php foreach (consumirAvisos() as $aviso): ?>
        <div class="aviso aviso-<?= e($aviso['tipo']) ?>" role="status"><?= e($aviso['mensagem']) ?></div>
    <?php endforeach; ?>
<?php
}

function rodape(): void
{
    ?>
</main>
<footer class="rodape">
    <div class="container">
        Sistema de Gerenciamento de Restaurante · Programação Orientada a Objetos · IFRO
    </div>
</footer>
<script src="<?= e(urlDoAsset('assets/app.js')) ?>"></script>
</body>
</html>
<?php
}

function paginaDeErro(string $titulo, string $mensagem, ?Throwable $detalhe = null): void
{
    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: text/html; charset=UTF-8');
    }
    ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Erro — <?= e(Config::NOME_RESTAURANTE) ?></title>
    <link rel="stylesheet" href="<?= e(urlDoAsset('assets/style.css')) ?>">
</head>
<body class="pagina-erro-geral">
<?= filtroDeCarimbo() ?>
<?php topoDoBoteco(); ?>
<main class="container pagina-erro">
    <div class="cartao">
        <h1><?= e($titulo) ?></h1>
        <p><?= e($mensagem) ?></p>
        <?php if ($detalhe !== null): ?>
            <details>
                <summary>Detalhes técnicos</summary>
                <pre class="codigo"><?= e(get_class($detalhe) . ' em ' . basename($detalhe->getFile()) . ':' . $detalhe->getLine()) ?></pre>
            </details>
        <?php endif; ?>
        <p><a class="botao" href="index.php">Voltar ao balcão</a></p>
    </div>
</main>
</body>
</html>
<?php
    exit;
}
