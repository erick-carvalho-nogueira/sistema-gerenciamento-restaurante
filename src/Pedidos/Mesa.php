<?php
/**
 * Classe: Mesa
 * Responsável: Danilo Sales Coutinho (RA 2026106090020)
 * Descrição: mesa do salão (livre, ocupada ou reservada); controla lotação e lança MesaIndisponivelException quando não pode ser usada.
 */
declare(strict_types=1);

class Mesa implements Persistivel
{
    public const CAPACIDADE_MAXIMA = 12;
    public const TAMANHO_MAXIMO_RESERVA = 40;

    public const LIVRE = 'livre';
    public const OCUPADA = 'ocupada';
    public const RESERVADA = 'reservada';

    private const ROTULOS = [
        self::LIVRE => 'Livre',
        self::OCUPADA => 'Ocupada',
        self::RESERVADA => 'Reservada',
    ];

    private int $numero;
    private int $capacidade;
    private string $status = self::LIVRE;
    private int $pessoas = 0;
    private ?string $reservadaPara = null;
    private ?int $pedidoAtual = null;

    public function __construct(int $numero, int $capacidade)
    {
        if ($numero < 1) {
            throw DadosInvalidosException::campo('número da mesa', 'deve ser positivo');
        }
        if ($capacidade < 1 || $capacidade > self::CAPACIDADE_MAXIMA) {
            throw DadosInvalidosException::campo(
                'capacidade',
                sprintf('deve estar entre 1 e %d lugares', self::CAPACIDADE_MAXIMA)
            );
        }
        $this->numero = $numero;
        $this->capacidade = $capacidade;
    }

    public function ocupar(int $pessoas): void
    {
        if ($this->status === self::OCUPADA) {
            throw MesaIndisponivelException::ocupada($this->numero);
        }
        if ($pessoas < 1) {
            throw DadosInvalidosException::campo('quantidade de pessoas', 'deve ser pelo menos 1');
        }
        if ($pessoas > $this->capacidade) {
            throw MesaIndisponivelException::capacidadeExcedida($this->numero, $this->capacidade, $pessoas);
        }
        $this->status = self::OCUPADA;
        $this->pessoas = $pessoas;
        $this->reservadaPara = null;
    }

    public function reservar(string $nome): void
    {
        if ($this->status !== self::LIVRE) {
            throw MesaIndisponivelException::naoEstaLivre($this->numero, strtolower($this->getRotuloStatus()));
        }
        $nome = Util::limparTexto($nome);
        if ($nome === '') {
            throw DadosInvalidosException::campo('nome da reserva', 'é obrigatório');
        }
        if (Util::tamanho($nome) > self::TAMANHO_MAXIMO_RESERVA) {
            throw DadosInvalidosException::campo(
                'nome da reserva',
                sprintf('deve ter no máximo %d caracteres', self::TAMANHO_MAXIMO_RESERVA)
            );
        }
        $this->status = self::RESERVADA;
        $this->reservadaPara = $nome;
    }

    public function vincularPedido(int $numeroPedido): void
    {
        if ($this->status !== self::OCUPADA) {
            throw DadosInvalidosException::campo('mesa', 'precisa estar ocupada para receber um pedido');
        }
        $this->pedidoAtual = $numeroPedido;
    }

    public function liberar(): void
    {
        if ($this->pedidoAtual !== null) {
            throw MesaIndisponivelException::comPedidoAberto($this->numero);
        }
        $this->limpar();
    }

    public function encerrarAtendimento(): void
    {
        $this->pedidoAtual = null;
        $this->limpar();
    }

    private function limpar(): void
    {
        $this->status = self::LIVRE;
        $this->pessoas = 0;
        $this->reservadaPara = null;
    }

    public function getNumero(): int
    {
        return $this->numero;
    }

    public function getCapacidade(): int
    {
        return $this->capacidade;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getRotuloStatus(): string
    {
        return self::ROTULOS[$this->status];
    }

    public function getPessoas(): int
    {
        return $this->pessoas;
    }

    public function getReservadaPara(): ?string
    {
        return $this->reservadaPara;
    }

    public function getPedidoAtual(): ?int
    {
        return $this->pedidoAtual;
    }

    public function estaLivre(): bool
    {
        return $this->status === self::LIVRE;
    }

    public function toArray(): array
    {
        return [
            'numero' => $this->numero,
            'capacidade' => $this->capacidade,
            'status' => $this->status,
            'pessoas' => $this->pessoas,
            'reservada_para' => $this->reservadaPara,
            'pedido_atual' => $this->pedidoAtual,
        ];
    }

    public static function deArray(array $dados): self
    {
        $mesa = new self(
            Util::paraInt($dados['numero'] ?? '', 'número da mesa'),
            Util::paraInt($dados['capacidade'] ?? '', 'capacidade')
        );

        $status = Util::limparTexto($dados['status'] ?? self::LIVRE);
        if (!isset(self::ROTULOS[$status])) {
            throw DadosInvalidosException::campo('situação da mesa', sprintf('"%s" é inválida', $status));
        }
        $mesa->status = $status;
        $mesa->pessoas = max(0, Util::paraInt($dados['pessoas'] ?? 0, 'pessoas'));

        $reserva = Util::limparTexto($dados['reservada_para'] ?? '');
        $mesa->reservadaPara = $reserva === '' ? null : $reserva;

        $pedido = $dados['pedido_atual'] ?? null;
        $mesa->pedidoAtual = ($pedido === null || $pedido === '') ? null : Util::paraInt($pedido, 'pedido atual');

        return $mesa;
    }
}
