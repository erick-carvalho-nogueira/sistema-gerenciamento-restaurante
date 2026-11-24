<?php
/**
 * Classe: Cardapio
 * Responsável: Erik Carvalho Nogueira (RA 2026106090008)
 * Descrição: coleção dos itens do restaurante; cadastra, remove e busca itens (lança ItemNaoEncontradoException quando o código não existe).
 */
declare(strict_types=1);

class Cardapio implements Persistivel
{
    private array $itens = [];
    private int $proximoId = 1;

    public function adicionar(ItemCardapio $item): ItemCardapio
    {
        if ($item->getId() === null) {
            $item->definirId($this->proximoId);
        } elseif (isset($this->itens[$item->getId()])) {
            throw DadosInvalidosException::campo(
                'código',
                sprintf('#%d já existe no cardápio', $item->getId())
            );
        }

        $this->itens[$item->getId()] = $item;
        $this->proximoId = max($this->proximoId, $item->getId() + 1);
        return $item;
    }

    public function buscarPorId(int $id): ItemCardapio
    {
        if (!isset($this->itens[$id])) {
            throw ItemNaoEncontradoException::porId($id);
        }
        return $this->itens[$id];
    }

    public function remover(int $id): void
    {
        $this->buscarPorId($id);
        unset($this->itens[$id]);
    }

    public function buscarPorNome(string $termo): array
    {
        $termo = Util::limparTexto($termo);
        if ($termo === '') {
            return [];
        }
        return array_values(array_filter(
            $this->itens,
            static function (ItemCardapio $item) use ($termo): bool {
                return stripos($item->getNome(), $termo) !== false;
            }
        ));
    }

    public function todos(): array
    {
        return array_values($this->itens);
    }

    public function disponiveis(): array
    {
        return array_values(array_filter(
            $this->itens,
            static function (ItemCardapio $item): bool {
                return $item->isDisponivel();
            }
        ));
    }

    public function agruparPorTipo(): array
    {
        $grupos = [];
        foreach (array_keys(ItemCardapioFactory::tiposDisponiveis()) as $tipo) {
            $grupos[$tipo] = [];
        }
        foreach ($this->itens as $item) {
            $grupos[$item->getTipo()][] = $item;
        }
        return $grupos;
    }

    public function descricoes(): array
    {
        $linhas = [];
        foreach ($this->itens as $item) {
            $linhas[] = $item->descrever();
        }
        return $linhas;
    }

    public function quantidade(): int
    {
        return count($this->itens);
    }

    public function toArray(): array
    {
        return [
            'proximo_id' => $this->proximoId,
            'itens' => array_map(
                static function (ItemCardapio $item): array {
                    return $item->toArray();
                },
                array_values($this->itens)
            ),
        ];
    }

    public static function deArray(array $dados): self
    {
        $cardapio = new self();
        foreach ((array) ($dados['itens'] ?? []) as $dadosItem) {
            if (!is_array($dadosItem)) {
                continue;
            }
            $cardapio->adicionar(ItemCardapioFactory::criar((string) ($dadosItem['tipo'] ?? ''), $dadosItem));
        }
        if (isset($dados['proximo_id'])) {
            $cardapio->proximoId = max($cardapio->proximoId, Util::paraInt($dados['proximo_id'], 'próximo código'));
        }
        return $cardapio;
    }
}
