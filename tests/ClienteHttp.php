<?php
/**
 * Classe: ClienteHttp
 * Responsável: Erik Carvalho Nogueira (RA 2026106090008)
 * Descrição: cliente HTTP mínimo para os testes (GET/POST com cookie de sessão, sem seguir redirecionamentos automaticamente).
 */
declare(strict_types=1);

final class ClienteHttp
{
    private string $base;
    private array $cookies = [];

    public function __construct(string $base)
    {
        $this->base = rtrim($base, '/');
    }

    public function get(string $caminho): array
    {
        return $this->requisitar('GET', $caminho, null);
    }

    public function post(string $caminho, array $campos): array
    {
        return $this->requisitar('POST', $caminho, $campos);
    }

    private function requisitar(string $metodo, string $caminho, ?array $campos): array
    {
        $cabecalhos = ['Accept: text/html'];
        if ($this->cookies !== []) {
            $pares = [];
            foreach ($this->cookies as $nome => $valor) {
                $pares[] = $nome . '=' . $valor;
            }
            $cabecalhos[] = 'Cookie: ' . implode('; ', $pares);
        }

        $opcoes = [
            'method' => $metodo,
            'ignore_errors' => true,
            'follow_location' => 0,
            'max_redirects' => 0,
            'timeout' => 20,
        ];
        if ($campos !== null) {
            $cabecalhos[] = 'Content-Type: application/x-www-form-urlencoded';
            $opcoes['content'] = http_build_query($campos);
        }
        $opcoes['header'] = implode("\r\n", $cabecalhos);

        $corpo = @file_get_contents($this->base . '/' . ltrim($caminho, '/'), false, stream_context_create(['http' => $opcoes]));
        if (function_exists('http_get_last_response_headers')) {
            $cabecalhosDaResposta = http_get_last_response_headers() ?? [];
        } else {
            $cabecalhosDaResposta = $http_response_header ?? [];
        }

        $status = 0;
        $local = null;
        foreach ($cabecalhosDaResposta as $linha) {
            if (preg_match('#^HTTP/\S+\s+(\d{3})#', $linha, $partes) === 1) {
                $status = (int) $partes[1];
            } elseif (stripos($linha, 'Location:') === 0) {
                $local = trim(substr($linha, 9));
            } elseif (stripos($linha, 'Set-Cookie:') === 0 && preg_match('/^Set-Cookie:\s*([^=;\s]+)=([^;]*)/i', $linha, $cookie) === 1) {
                $this->cookies[$cookie[1]] = $cookie[2];
            }
        }

        return ['status' => $status, 'corpo' => $corpo === false ? '' : $corpo, 'local' => $local];
    }
}
