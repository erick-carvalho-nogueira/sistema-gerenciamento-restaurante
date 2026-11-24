<?php
/**
 * Arquivo: bootstrap.php
 * Responsável: Erik Carvalho Nogueira (RA 2026106090008)
 * Descrição: inicialização comum de todas as páginas (autoload, ambiente, sessão e tratamento de erros não previstos).
 */
declare(strict_types=1);

require_once __DIR__ . '/../src/autoload.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/visual.php';
require_once __DIR__ . '/layout.php';

Config::aplicarAmbiente();
iniciarSessao();

set_exception_handler(static function (Throwable $erro): void {
    paginaDeErro(
        'Ocorreu um erro inesperado',
        $erro->getMessage(),
        Config::MODO_DEBUG ? $erro : null
    );
});
