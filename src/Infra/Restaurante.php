<?php
/**
 * Classe: Restaurante
 * Responsável: Erik Carvalho Nogueira (RA 2026106090008)
 * Descrição: padrão Singleton — fachada única do sistema; reúne cardápio, equipe, mesas e pedidos e cuida de carregar e salvar os dados.
 */
declare(strict_types=1);

final class Restaurante
{
    private static ?Restaurante $instancia = null;

    private ArmazenamentoJson $armazenamento;
    private Cardapio $cardapio;
    private Equipe $equipe;
    private array $mesas = [];
    private array $pedidos = [];

    private function __construct(ArmazenamentoJson $armazenamento)
    {
        $this->armazenamento = $armazenamento;
        $this->cardapio = new Cardapio();
        $this->equipe = new Equipe();
        $this->carregar();
    }

    private function __clone()
    {
    }

    public function __unserialize(array $dados): void
    {
        throw new RestauranteException('O Restaurante é um Singleton e não pode ser desserializado.');
    }

    public static function getInstance(?ArmazenamentoJson $armazenamento = null): Restaurante
    {
        if (self::$instancia === null) {
            self::$instancia = new self($armazenamento ?? new ArmazenamentoJson(Config::caminhoDados()));
        }
        return self::$instancia;
    }

    public static function reiniciarInstancia(): void
    {
        self::$instancia = null;
    }

    public function getNome(): string
    {
        return Config::NOME_RESTAURANTE;
    }

    public function getCardapio(): Cardapio
    {
        return $this->cardapio;
    }

    public function getEquipe(): Equipe
    {
        return $this->equipe;
    }

    public function getMesas(): array
    {
        ksort($this->mesas);
        return array_values($this->mesas);
    }

    public function buscarMesa(int $numero): Mesa
    {
        if (!isset($this->mesas[$numero])) {
            throw MesaIndisponivelException::inexistente($numero);
        }
        return $this->mesas[$numero];
    }

    public function adicionarMesa(Mesa $mesa): void
    {
        if (isset($this->mesas[$mesa->getNumero()])) {
            throw DadosInvalidosException::campo(
                'número da mesa',
                sprintf('%d já existe', $mesa->getNumero())
            );
        }
        $this->mesas[$mesa->getNumero()] = $mesa;
    }

    public function getPedidos(): array
    {
        ksort($this->pedidos);
        return array_values($this->pedidos);
    }

    public function buscarPedido(int $numero): Pedido
    {
        if (!isset($this->pedidos[$numero])) {
            throw PedidoInvalidoException::naoEncontrado($numero);
        }
        return $this->pedidos[$numero];
    }

    public function pedidosEmAndamento(): array
    {
        return array_values(array_filter(
            $this->getPedidos(),
            static function (Pedido $pedido): bool {
                return !$pedido->estaFinalizado();
            }
        ));
    }

    public function reservarMesa(int $numeroMesa, string $nome): void
    {
        $this->buscarMesa($numeroMesa)->reservar($nome);
    }

    public function liberarMesa(int $numeroMesa): void
    {
        $this->buscarMesa($numeroMesa)->liberar();
    }

    public function abrirPedido(int $numeroMesa, int $pessoas, ?int $idGarcom = null): Pedido
    {
        $mesa = $this->buscarMesa($numeroMesa);
        if ($idGarcom !== null) {
            $this->equipe->buscarGarcom($idGarcom);
        }

        $mesa->ocupar($pessoas);
        $pedido = new Pedido($numeroMesa, $pessoas, $idGarcom);
        $mesa->vincularPedido($pedido->getNumero());
        $this->pedidos[$pedido->getNumero()] = $pedido;

        return $pedido;
    }

    public function cancelarPedido(int $numeroPedido): void
    {
        $pedido = $this->buscarPedido($numeroPedido);
        $mesa = $this->buscarMesa($pedido->getNumeroMesa());

        $pedido->cancelar();
        $mesa->encerrarAtendimento();
    }

    public function pagarPedido(int $numeroPedido, Pagamento $pagamento): Pedido
    {
        $pedido = $this->buscarPedido($numeroPedido);
        $mesa = $this->buscarMesa($pedido->getNumeroMesa());

        $pedido->pagar($pagamento);
        $mesa->encerrarAtendimento();
        $this->equipe->registrarVenda($pedido->getSubtotal(), $pedido->getIdGarcom());

        return $pedido;
    }

    public function faturamentoDoDia(?string $dia = null): float
    {
        $dia = $dia ?? Util::hoje();
        $total = 0.0;
        foreach ($this->pedidos as $pedido) {
            if ($this->foiPagoNoDia($pedido, $dia)) {
                $total += $pedido->getTotalCobrado();
            }
        }
        return Util::arredondar($total);
    }

    public function faturamentoPorForma(?string $dia = null): array
    {
        $dia = $dia ?? Util::hoje();
        $totais = [];
        foreach (PagamentoFactory::tiposDisponiveis() as $tipo => $nome) {
            $totais[$tipo] = ['nome' => $nome, 'total' => 0.0, 'quantidade' => 0];
        }

        foreach ($this->pedidos as $pedido) {
            $pagamento = $pedido->getPagamento();
            if ($pagamento === null || !$this->foiPagoNoDia($pedido, $dia) || !isset($totais[$pagamento->getTipo()])) {
                continue;
            }
            $totais[$pagamento->getTipo()]['total'] = Util::arredondar(
                $totais[$pagamento->getTipo()]['total'] + $pagamento->getValorCobrado()
            );
            $totais[$pagamento->getTipo()]['quantidade']++;
        }
        return $totais;
    }

    private function foiPagoNoDia(Pedido $pedido, string $dia): bool
    {
        return $pedido->getStatus() === StatusPedido::PAGO
            && substr((string) $pedido->getPagoEm(), 0, 10) === $dia;
    }

    public function resumo(): array
    {
        $ocupadas = 0;
        $reservadas = 0;
        $livres = 0;
        foreach ($this->mesas as $mesa) {
            if ($mesa->getStatus() === Mesa::OCUPADA) {
                $ocupadas++;
            } elseif ($mesa->getStatus() === Mesa::RESERVADA) {
                $reservadas++;
            } else {
                $livres++;
            }
        }

        return [
            'faturamento_dia' => $this->faturamentoDoDia(),
            'pedidos_andamento' => count($this->pedidosEmAndamento()),
            'mesas_total' => count($this->mesas),
            'mesas_ocupadas' => $ocupadas,
            'mesas_reservadas' => $reservadas,
            'mesas_livres' => $livres,
            'itens_cardapio' => $this->cardapio->quantidade(),
            'funcionarios' => $this->equipe->quantidade(),
            'folha_estimada' => $this->equipe->totalFolha(),
        ];
    }

    public function salvar(): void
    {
        $this->armazenamento->salvar([
            'versao' => Config::VERSAO_DADOS,
            'salvo_em' => Util::agora(),
            'cardapio' => $this->cardapio->toArray(),
            'equipe' => $this->equipe->toArray(),
            'mesas' => array_map(
                static function (Mesa $mesa): array {
                    return $mesa->toArray();
                },
                $this->getMesas()
            ),
            'pedidos' => array_map(
                static function (Pedido $pedido): array {
                    return $pedido->toArray();
                },
                $this->getPedidos()
            ),
        ]);
    }

    public function resetarParaDadosExemplo(): void
    {
        $this->cardapio = new Cardapio();
        $this->equipe = new Equipe();
        $this->mesas = [];
        $this->pedidos = [];
        $this->popularDadosExemplo();
    }

    private function carregar(): void
    {
        $dados = $this->armazenamento->carregar();
        if ($dados === []) {
            $this->popularDadosExemplo();
            return;
        }

        $this->cardapio = Cardapio::deArray((array) ($dados['cardapio'] ?? []));
        $this->equipe = Equipe::deArray((array) ($dados['equipe'] ?? []));

        $this->mesas = [];
        foreach ((array) ($dados['mesas'] ?? []) as $dadosMesa) {
            if (is_array($dadosMesa)) {
                $mesa = Mesa::deArray($dadosMesa);
                $this->mesas[$mesa->getNumero()] = $mesa;
            }
        }

        $this->pedidos = [];
        foreach ((array) ($dados['pedidos'] ?? []) as $dadosPedido) {
            if (is_array($dadosPedido)) {
                $pedido = Pedido::deArray($dadosPedido);
                $this->pedidos[$pedido->getNumero()] = $pedido;
            }
        }
    }

    private function popularDadosExemplo(): void
    {
        Pedido::reiniciarSequencia();
        DadosExemplo::popular($this);
        $this->salvar();
    }
}
