<?php
/**
 * Classe: Pedido
 * Responsável: Erik Carvalho Nogueira (RA 2026106090008)
 * Descrição: a comanda de uma mesa; soma os itens (polimorfismo), aplica a taxa de serviço, controla o fluxo de status e recebe o pagamento.
 */
declare(strict_types=1);

class Pedido implements Persistivel
{
    public const TAXA_SERVICO = 0.10;
    public const MAX_ITENS = 20;
    public const MAX_QUANTIDADE_POR_ITEM = 20;

    private static int $proximoNumero = 1;

    private int $numero;
    private int $numeroMesa;
    private int $pessoas;
    private ?int $idGarcom;
    private string $status = StatusPedido::ABERTO;
    private string $criadoEm;
    private ?string $pagoEm = null;
    private array $itens = [];
    private ?Pagamento $pagamento = null;

    public function __construct(
        int $numeroMesa,
        int $pessoas,
        ?int $idGarcom = null,
        ?int $numero = null,
        ?string $criadoEm = null
    ) {
        if ($numeroMesa < 1) {
            throw DadosInvalidosException::campo('mesa', 'deve ser um número positivo');
        }
        if ($pessoas < 1) {
            throw DadosInvalidosException::campo('quantidade de pessoas', 'deve ser pelo menos 1');
        }

        $this->numeroMesa = $numeroMesa;
        $this->pessoas = $pessoas;
        $this->idGarcom = $idGarcom;
        $this->criadoEm = $criadoEm ?? Util::agora();

        if ($numero === null) {
            $this->numero = self::$proximoNumero++;
        } else {
            $this->numero = $numero;
            if ($numero >= self::$proximoNumero) {
                self::$proximoNumero = $numero + 1;
            }
        }
    }

    public function adicionarItem(ItemCardapio $item, int $quantidade, string $observacao = ''): void
    {
        $this->garantirNaoFinalizado();
        if (!$item->isDisponivel()) {
            throw PedidoInvalidoException::itemIndisponivel($item->getNome());
        }
        if ($quantidade < 1 || $quantidade > self::MAX_QUANTIDADE_POR_ITEM) {
            throw PedidoInvalidoException::quantidadeInvalida($quantidade, self::MAX_QUANTIDADE_POR_ITEM);
        }
        $observacao = Util::limparTexto($observacao);

        foreach ($this->itens as $linha) {
            $mesmoItem = $item->getId() !== null && $linha->getItem()->getId() === $item->getId();
            if ($mesmoItem && $linha->getObservacao() === $observacao) {
                $linha->setQuantidade($linha->getQuantidade() + $quantidade);
                $this->voltarParaCozinhaSeNecessario();
                return;
            }
        }

        if (count($this->itens) >= self::MAX_ITENS) {
            throw PedidoInvalidoException::limiteDeItens(self::MAX_ITENS);
        }
        $this->itens[] = new ItemPedido($item, $quantidade, $observacao);
        $this->voltarParaCozinhaSeNecessario();
    }

    public function removerItem(int $posicao): void
    {
        if ($this->status !== StatusPedido::ABERTO) {
            if (StatusPedido::estaFinalizado($this->status)) {
                throw PedidoInvalidoException::statusFinal($this->numero, $this->status);
            }
            throw PedidoInvalidoException::itensJaEnviados();
        }
        if (!isset($this->itens[$posicao])) {
            throw PedidoInvalidoException::itemInexistente($posicao);
        }
        array_splice($this->itens, $posicao, 1);
    }

    private function voltarParaCozinhaSeNecessario(): void
    {
        if ($this->status === StatusPedido::PRONTO || $this->status === StatusPedido::ENTREGUE) {
            $this->status = StatusPedido::EM_PREPARO;
        }
    }

    private function garantirNaoFinalizado(): void
    {
        if (StatusPedido::estaFinalizado($this->status)) {
            throw PedidoInvalidoException::statusFinal($this->numero, $this->status);
        }
    }

    public function getSubtotal(): float
    {
        $subtotal = 0.0;
        foreach ($this->itens as $linha) {
            $subtotal += $linha->getSubtotal();
        }
        return Util::arredondar($subtotal);
    }

    public function getTaxaServico(): float
    {
        return Util::arredondar($this->getSubtotal() * self::TAXA_SERVICO);
    }

    public function getTotal(): float
    {
        return Util::arredondar($this->getSubtotal() + $this->getTaxaServico());
    }

    public function getTotalCobrado(): float
    {
        return $this->pagamento !== null ? $this->pagamento->getValorCobrado() : $this->getTotal();
    }

    public function estimarTempoPreparoMinutos(): int
    {
        $maior = 0;
        foreach ($this->itens as $linha) {
            $maior = max($maior, $linha->getItem()->getTempoPreparoMinutos());
        }
        return $maior;
    }

    public function getSetoresEnvolvidos(): array
    {
        $setores = [];
        foreach ($this->itens as $linha) {
            $setores[$linha->getItem()->getSetorPreparo()] = true;
        }
        return array_keys($setores);
    }

    public function getQuantidadeDeUnidades(): int
    {
        $total = 0;
        foreach ($this->itens as $linha) {
            $total += $linha->getQuantidade();
        }
        return $total;
    }

    public function avancarStatus(): string
    {
        $proximo = StatusPedido::proximo($this->status);
        if ($proximo === null) {
            throw PedidoInvalidoException::semProximaEtapa($this->status);
        }
        if ($this->status === StatusPedido::ABERTO && count($this->itens) === 0) {
            throw PedidoInvalidoException::semItens();
        }
        $this->status = $proximo;
        return $this->status;
    }

    public function cancelar(): void
    {
        $this->garantirNaoFinalizado();
        $this->status = StatusPedido::CANCELADO;
    }

    public function pagar(Pagamento $pagamento): void
    {
        if ($this->status !== StatusPedido::ENTREGUE) {
            throw PedidoInvalidoException::naoPodePagar($this->numero, $this->status);
        }
        if (count($this->itens) === 0) {
            throw PedidoInvalidoException::semItens();
        }

        $pagamento->processar($this->getTotal(), $this->numero);

        $this->pagamento = $pagamento;
        $this->status = StatusPedido::PAGO;
        $this->pagoEm = Util::agora();
    }

    public function getNumero(): int
    {
        return $this->numero;
    }

    public function getNumeroMesa(): int
    {
        return $this->numeroMesa;
    }

    public function getPessoas(): int
    {
        return $this->pessoas;
    }

    public function getIdGarcom(): ?int
    {
        return $this->idGarcom;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getCriadoEm(): string
    {
        return $this->criadoEm;
    }

    public function getPagoEm(): ?string
    {
        return $this->pagoEm;
    }

    public function getItens(): array
    {
        return $this->itens;
    }

    public function getPagamento(): ?Pagamento
    {
        return $this->pagamento;
    }

    public function estaFinalizado(): bool
    {
        return StatusPedido::estaFinalizado($this->status);
    }

    public static function getProximoNumero(): int
    {
        return self::$proximoNumero;
    }

    public static function reiniciarSequencia(): void
    {
        self::$proximoNumero = 1;
    }

    public function toArray(): array
    {
        return [
            'numero' => $this->numero,
            'mesa' => $this->numeroMesa,
            'pessoas' => $this->pessoas,
            'garcom' => $this->idGarcom,
            'status' => $this->status,
            'criado_em' => $this->criadoEm,
            'pago_em' => $this->pagoEm,
            'itens' => array_map(
                static function (ItemPedido $linha): array {
                    return $linha->toArray();
                },
                $this->itens
            ),
            'pagamento' => $this->pagamento !== null ? $this->pagamento->toArray() : null,
        ];
    }

    public static function deArray(array $dados): self
    {
        $garcom = $dados['garcom'] ?? null;
        $criadoEm = Util::limparTexto($dados['criado_em'] ?? '');

        $pedido = new self(
            Util::paraInt($dados['mesa'] ?? '', 'mesa'),
            Util::paraInt($dados['pessoas'] ?? '', 'pessoas'),
            ($garcom === null || $garcom === '') ? null : Util::paraInt($garcom, 'garçom'),
            Util::paraInt($dados['numero'] ?? '', 'número do pedido'),
            $criadoEm === '' ? null : $criadoEm
        );

        $status = Util::limparTexto($dados['status'] ?? StatusPedido::ABERTO);
        if (!StatusPedido::existe($status)) {
            throw DadosInvalidosException::campo('situação do pedido', sprintf('"%s" é inválida', $status));
        }
        $pedido->status = $status;

        $pagoEm = Util::limparTexto($dados['pago_em'] ?? '');
        $pedido->pagoEm = $pagoEm === '' ? null : $pagoEm;

        foreach ((array) ($dados['itens'] ?? []) as $dadosItem) {
            if (is_array($dadosItem)) {
                $pedido->itens[] = ItemPedido::deArray($dadosItem);
            }
        }

        if (isset($dados['pagamento']) && is_array($dados['pagamento'])) {
            $pedido->pagamento = PagamentoFactory::restaurar($dados['pagamento']);
        }

        return $pedido;
    }
}
