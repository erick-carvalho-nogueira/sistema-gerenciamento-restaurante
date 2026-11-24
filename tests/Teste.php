<?php
/**
 * Classe: Teste
 * Responsável: Erik Carvalho Nogueira (RA 2026106090008)
 * Descrição: mini-framework de testes sem dependências; agrupa verificações, conta acertos e falhas e define o código de saída.
 */
declare(strict_types=1);

final class Teste
{
    private static int $total = 0;
    private static int $falhas = 0;
    private static array $mensagensDeFalha = [];

    private function __construct()
    {
    }

    public static function grupo(string $titulo): void
    {
        echo PHP_EOL . '== ' . $titulo . ' ==' . PHP_EOL;
    }

    public static function verdadeiro(bool $condicao, string $descricao): void
    {
        self::$total++;
        if ($condicao) {
            echo '  [OK]    ' . $descricao . PHP_EOL;
            return;
        }
        self::$falhas++;
        self::$mensagensDeFalha[] = $descricao;
        echo '  [FALHA] ' . $descricao . PHP_EOL;
    }

    public static function igual($esperado, $atual, string $descricao): void
    {
        $ok = $esperado === $atual;
        self::verdadeiro(
            $ok,
            $ok ? $descricao : sprintf('%s (esperado %s, obtido %s)', $descricao, self::exportar($esperado), self::exportar($atual))
        );
    }

    public static function aproximado(float $esperado, float $atual, string $descricao, float $tolerancia = 0.005): void
    {
        $ok = abs($esperado - $atual) <= $tolerancia;
        self::verdadeiro(
            $ok,
            $ok ? $descricao : sprintf('%s (esperado %s, obtido %s)', $descricao, self::exportar($esperado), self::exportar($atual))
        );
    }

    public static function lanca(string $classeEsperada, callable $acao, string $descricao): void
    {
        try {
            $acao();
        } catch (Throwable $erro) {
            $ok = $erro instanceof $classeEsperada;
            self::verdadeiro(
                $ok,
                $ok ? $descricao : sprintf('%s (lancou %s: %s)', $descricao, get_class($erro), $erro->getMessage())
            );
            return;
        }
        self::verdadeiro(false, $descricao . ' (nada foi lancado)');
    }

    public static function aviso(string $mensagem): void
    {
        echo '  [AVISO] ' . $mensagem . PHP_EOL;
    }

    public static function resumo(): int
    {
        echo PHP_EOL . str_repeat('-', 60) . PHP_EOL;
        echo sprintf('%d verificacoes, %d falha(s).', self::$total, self::$falhas) . PHP_EOL;
        foreach (self::$mensagensDeFalha as $mensagem) {
            echo '  - ' . $mensagem . PHP_EOL;
        }
        echo self::$falhas === 0 ? 'RESULTADO: TUDO CERTO.' : 'RESULTADO: HA FALHAS.';
        echo PHP_EOL;
        return self::$falhas === 0 ? 0 : 1;
    }

    private static function exportar($valor): string
    {
        return var_export($valor, true);
    }
}
