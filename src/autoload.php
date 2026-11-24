<?php
/**
 * Arquivo: autoload.php
 * Responsável: Erik Carvalho Nogueira (RA 2026106090008)
 * Descrição: carregador automático de classes (spl_autoload_register); dispensa o require manual de cada classe.
 */
declare(strict_types=1);

spl_autoload_register(static function (string $classe): void {
    static $pastas = ['Contratos', 'Excecoes', 'Cardapio', 'Funcionarios', 'Pedidos', 'Pagamentos', 'Infra'];

    if (preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $classe) !== 1) {
        return;
    }

    foreach ($pastas as $pasta) {
        $arquivo = __DIR__ . DIRECTORY_SEPARATOR . $pasta . DIRECTORY_SEPARATOR . $classe . '.php';
        if (is_file($arquivo)) {
            require_once $arquivo;
            return;
        }
    }
});
