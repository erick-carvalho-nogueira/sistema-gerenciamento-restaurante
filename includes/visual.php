<?php
/**
 * Arquivo: visual.php
 * Responsável: Erik Carvalho Nogueira (RA 2026106090008)
 * Descrição: peças do visual do boteco desenhadas em HTML (ícones, mesa com cadeiras, trilho de comandas, cupom da conta e ilustração de tela vazia).
 */
declare(strict_types=1);

function icone(string $nome, string $classe = ''): string
{
    return '<svg class="ico' . ($classe !== '' ? ' ' . e($classe) : '') . '" viewBox="0 0 48 48" aria-hidden="true" focusable="false">'
        . '<use href="' . e(urlDoAsset('assets/ilustracoes.svg')) . '#' . e($nome) . '"></use></svg>';
}

function ilustracaoVazia(string $frase): string
{
    return '<span>' . icone('cadeira', 'ico-grande') . icone('copo', 'ico-grande') . '</span>'
        . '<span class="nota-mao">' . e($frase) . '</span>';
}

function iconeDoCargo(Funcionario $funcionario): string
{
    if ($funcionario instanceof Cozinheiro) {
        return icone('chapeu');
    }
    return icone($funcionario instanceof Gerente ? 'sino' : 'prato');
}

function desenharMesa(Mesa $mesa, ?Pedido $pedido = null): string
{
    $lugares = $mesa->getCapacidade();
    $largura = min(230, [2 => 84, 4 => 132, 6 => 172, 8 => 208][$lugares] ?? 60 + 18 * $lugares);
    $emCima = (int) ceil($lugares / 2);
    $embaixo = $lugares - $emCima;

    $extra = '';
    if ($mesa->getStatus() === Mesa::OCUPADA && $pedido !== null) {
        $extra = '<span class="ticket">#' . e($pedido->getNumero()) . '</span>';
    } elseif ($mesa->getStatus() === Mesa::RESERVADA) {
        $extra = '<span class="tenda"><b>RESERVADO</b><i>' . e((string) $mesa->getReservadaPara()) . '</i></span>';
    }

    return '<div class="mesa-palco" aria-hidden="true">'
        . '<div class="cadeiras">' . str_repeat('<span class="cadeira"></span>', $emCima) . '</div>'
        . '<div class="tampo" style="--larg:' . $largura . 'px">' . $extra . '<span class="numero">' . e($mesa->getNumero()) . '</span></div>'
        . '<div class="cadeiras baixo">' . str_repeat('<span class="cadeira"></span>', $embaixo) . '</div>'
        . '</div>';
}

function trilhoDeComandas(array $pedidos, Restaurante $restaurante): string
{
    $inclinacoes = [-1.5, 1.3, -0.8, 1.8];
    $html = '<div class="trilho"><div class="barra-trilho"></div><div class="tickets">';
    foreach (array_values($pedidos) as $posicao => $pedido) {
        $itens = $pedido->getItens();
        $linhas = '';
        foreach (array_slice($itens, 0, 3) as $linha) {
            $linhas .= '<span class="tk-item">' . e($linha->getQuantidade()) . ' ' . e($linha->getItem()->getNome()) . '</span>';
        }
        if (count($itens) > 3) {
            $linhas .= '<span class="tk-item">…</span>';
        } elseif (count($itens) === 0) {
            $linhas = '<span class="tk-item">(sem itens ainda)</span>';
        }
        $garcom = explode(' ', nomeDoGarcom($restaurante, $pedido->getIdGarcom()))[0];

        $html .= '<a class="tk" style="--r:' . $inclinacoes[$posicao % count($inclinacoes)] . 'deg" href="pedido.php?numero=' . e($pedido->getNumero()) . '"'
            . ' title="Abrir a comanda nº ' . e($pedido->getNumero()) . '">'
            . '<b>Nº ' . e($pedido->getNumero()) . '</b> · Mesa ' . e($pedido->getNumeroMesa())
            . $linhas
            . '<span class="tk-total">' . e($garcom) . ' · ' . e(moeda($pedido->getTotal())) . '</span>'
            . seloStatus($pedido->getStatus())
            . '</a>';
    }
    return $html . '</div></div>';
}

function cupomDaConta(Pedido $pedido, ?Pagamento $pagamento = null, bool $comItens = true): string
{
    $html = '<article class="cupom" aria-label="Cupom da conta">'
        . '<div class="cupom-marca">' . e(Config::NOME_RESTAURANTE) . '</div>'
        . '<div class="cupom-sub">Cupom não fiscal</div><hr>'
        . '<div class="cupom-linha"><span>Comanda nº ' . e($pedido->getNumero()) . '</span><span>Mesa ' . e($pedido->getNumeroMesa()) . '</span></div>'
        . '<div class="cupom-sub" style="text-align:left">Aberta em ' . e(Util::formatarDataHora($pedido->getCriadoEm())) . '</div><hr>';

    if ($comItens) {
        foreach ($pedido->getItens() as $linha) {
            $html .= '<div class="cupom-linha"><span>' . e($linha->getQuantidade()) . ' × ' . e($linha->getItem()->getNome()) . '</span>'
                . '<span>' . e(moeda($linha->getSubtotal())) . '</span></div>';
            if ($linha->getObservacao() !== '') {
                $html .= '<div class="cupom-detalhe">Obs.: ' . e($linha->getObservacao()) . '</div>';
            }
        }
        $html .= '<hr>';
    }

    $html .= '<div class="cupom-linha"><span>Subtotal</span><span>' . e(moeda($pedido->getSubtotal())) . '</span></div>'
        . '<div class="cupom-linha"><span>Serviço (' . e(Util::percentual(Pedido::TAXA_SERVICO)) . ')</span><span>' . e(moeda($pedido->getTaxaServico())) . '</span></div>'
        . '<div class="cupom-linha cupom-total"><span>TOTAL</span><span>' . e(moeda($pedido->getTotal())) . '</span></div><hr>';

    if ($pagamento !== null) {
        $html .= '<h2>Comprovante de pagamento</h2>'
            . '<div class="cupom-linha"><span>Forma de pagamento</span><span><strong>' . e($pagamento->getNome()) . '</strong></span></div>'
            . '<div class="cupom-linha"><span>Código</span><span>' . e($pagamento->getCodigo()) . '</span></div>'
            . '<div class="cupom-linha"><span>Pago em</span><span>' . e(Util::formatarDataHora($pagamento->getProcessadoEm())) . '</span></div>'
            . '<div class="cupom-linha"><span>Valor da conta</span><span>' . e(moeda($pagamento->getValorConta())) . '</span></div>'
            . '<div class="cupom-linha"><span>Valor cobrado</span><span><strong>' . e(moeda($pagamento->getValorCobrado())) . '</strong></span></div>';
        foreach ($pagamento->getComprovante() as $rotulo => $valor) {
            $html .= '<div class="cupom-linha"><span>' . e($rotulo) . '</span><span><strong>' . e($valor) . '</strong></span></div>';
        }
        $html .= '<span class="cupom-carimbo">Fechou</span>';
    } else {
        $html .= '<div class="cupom-sub">Conta aguardando pagamento</div>';
    }

    return $html . '<div class="cupom-barras" aria-hidden="true"></div><div class="cupom-fim">Volte sempre! — Zé</div></article>';
}
