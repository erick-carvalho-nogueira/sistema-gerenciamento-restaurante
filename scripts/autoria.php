<?php
/**
 * Arquivo: autoria.php
 * Responsável: Erik Carvalho Nogueira (RA 2026106090008)
 * Descrição: ferramenta de linha de comando que lista a autoria por integrante, confere os cabeçalhos, define os RAs e atualiza o README.
 * Uso: php scripts/autoria.php [--relatorio | --git | --verificar | --atualizar-readme | --definir-ra "Nome Completo=RA" ...]
 */
declare(strict_types=1);

require __DIR__ . '/AnalisadorAutoria.php';

$analisador = new AnalisadorAutoria(dirname(__DIR__));
$argumentos = array_slice($argv, 1);
$comando = $argumentos[0] ?? '--relatorio';

function semAcentos(string $texto): string
{
    return strtr($texto, [
        'á' => 'a', 'à' => 'a', 'ã' => 'a', 'â' => 'a', 'é' => 'e', 'ê' => 'e', 'í' => 'i', 'ó' => 'o', 'ô' => 'o',
        'õ' => 'o', 'ú' => 'u', 'ç' => 'c', 'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U', 'Ç' => 'C',
    ]);
}

switch ($comando) {
    case '--relatorio':
        echo semAcentos($analisador->montarBlocoDeAutoria()) . PHP_EOL;
        $pendentes = $analisador->integrantesComRaPendente();
        if (count($pendentes) > 0) {
            echo PHP_EOL . 'ATENCAO: ainda ha RA provisorio (' . AnalisadorAutoria::RA_PENDENTE . ') para: ' . implode(', ', $pendentes) . '.' . PHP_EOL;
            echo 'Defina com: php scripts/autoria.php --definir-ra "Nome Completo=SEU_RA"' . PHP_EOL;
        }
        exit(0);

    case '--git':
        foreach ($analisador->agruparPorIntegrante() as $nome => $dados) {
            echo '# ' . $nome . PHP_EOL;
            echo 'git add ' . implode(' ', $dados['arquivos']) . PHP_EOL . PHP_EOL;
        }
        exit(0);

    case '--verificar':
        $resultado = $analisador->analisar();
        $problemas = 0;
        foreach ($resultado['sem_cabecalho'] as $arquivo) {
            echo 'SEM CABECALHO DE AUTORIA: ' . $arquivo . PHP_EOL;
            $problemas++;
        }
        foreach ($analisador->integrantesComRaPendente() as $nome) {
            echo 'RA PENDENTE: ' . $nome . PHP_EOL;
            $problemas++;
        }
        echo $problemas === 0 ? 'OK: todos os arquivos tem cabecalho e todos os RAs estao preenchidos.' . PHP_EOL : $problemas . ' problema(s) encontrado(s).' . PHP_EOL;
        exit($problemas === 0 ? 0 : 1);

    case '--atualizar-readme':
        $atualizados = $analisador->atualizarTextosComAutoria();
        if (count($atualizados) > 0) {
            echo 'Autoria atualizada em: ' . implode(', ', $atualizados) . PHP_EOL;
            exit(0);
        }
        fwrite(STDERR, 'Nenhum arquivo .md com as marcas de autoria foi encontrado (o README.md precisa delas).' . PHP_EOL);
        exit(1);

    case '--definir-ra':
        $pares = array_slice($argumentos, 1);
        if (count($pares) === 0) {
            fwrite(STDERR, 'Informe pelo menos um par "Nome Completo=RA".' . PHP_EOL);
            exit(1);
        }
        foreach ($pares as $par) {
            $posicao = strrpos($par, '=');
            if ($posicao === false) {
                fwrite(STDERR, 'Formato invalido: ' . $par . ' (use "Nome Completo=RA").' . PHP_EOL);
                exit(1);
            }
            $nome = trim(substr($par, 0, $posicao));
            $ra = trim(substr($par, $posicao + 1));
            try {
                $alterados = $analisador->definirRa($nome, $ra);
            } catch (InvalidArgumentException $erro) {
                fwrite(STDERR, semAcentos($erro->getMessage()) . PHP_EOL);
                exit(1);
            }
            echo sprintf('%s: RA %s gravado em %d arquivo(s).', $nome, $ra, $alterados) . PHP_EOL;
        }
        $atualizados = $analisador->atualizarTextosComAutoria();
        if (count($atualizados) > 0) {
            echo 'Autoria atualizada em: ' . implode(', ', $atualizados) . PHP_EOL;
        }
        echo 'Execute "php scripts/autoria.php --verificar" para conferir.' . PHP_EOL;
        exit(0);

    default:
        echo 'Uso: php scripts/autoria.php [--relatorio | --git | --verificar | --atualizar-readme | --definir-ra "Nome Completo=RA" ...]' . PHP_EOL;
        exit($comando === '--ajuda' || $comando === '-h' ? 0 : 1);
}
