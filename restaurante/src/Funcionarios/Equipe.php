<?php
/**
 * Classe: Equipe
 * Responsável: Felipe Ferreira Rosas (RA 2026106090004)
 * Descrição: coleção de funcionários; calcula a folha de pagamento chamando o mesmo método em cada cargo (polimorfismo).
 */
declare(strict_types=1);

class Equipe implements Persistivel
{
    public const LIMITE_FUNCIONARIOS = 30;

    private array $funcionarios = [];
    private int $proximoId = 1;

    public function contratar(Funcionario $funcionario): Funcionario
    {
        if (count($this->funcionarios) >= self::LIMITE_FUNCIONARIOS) {
            throw DadosInvalidosException::campo(
                'equipe',
                sprintf('já atingiu o limite de %d funcionários', self::LIMITE_FUNCIONARIOS)
            );
        }

        if ($funcionario->getId() === null) {
            $funcionario->definirId($this->proximoId);
        } elseif (isset($this->funcionarios[$funcionario->getId()])) {
            throw DadosInvalidosException::campo(
                'código',
                sprintf('#%d já pertence a outro funcionário', $funcionario->getId())
            );
        }

        $this->funcionarios[$funcionario->getId()] = $funcionario;
        $this->proximoId = max($this->proximoId, $funcionario->getId() + 1);
        return $funcionario;
    }

    public function demitir(int $id): void
    {
        $this->buscarPorId($id);
        unset($this->funcionarios[$id]);
    }

    public function buscarPorId(int $id): Funcionario
    {
        if (!isset($this->funcionarios[$id])) {
            throw FuncionarioNaoEncontradoException::porId($id);
        }
        return $this->funcionarios[$id];
    }

    public function buscarGarcom(int $id): Garcom
    {
        $funcionario = $this->buscarPorId($id);
        if (!$funcionario instanceof Garcom) {
            throw FuncionarioNaoEncontradoException::naoEGarcom($id);
        }
        return $funcionario;
    }

    public function todos(): array
    {
        return array_values($this->funcionarios);
    }

    public function garcons(): array
    {
        return array_values(array_filter(
            $this->funcionarios,
            static function (Funcionario $funcionario): bool {
                return $funcionario instanceof Garcom;
            }
        ));
    }

    public function quantidade(): int
    {
        return count($this->funcionarios);
    }

    public function calcularFolha(): array
    {
        $folha = [];
        foreach ($this->funcionarios as $funcionario) {
            $folha[] = [
                'funcionario' => $funcionario,
                'cargo' => $funcionario->getCargo(),
                'salario' => $funcionario->calcularSalario(),
            ];
        }
        return $folha;
    }

    public function totalFolha(): float
    {
        $total = 0.0;
        foreach ($this->calcularFolha() as $linha) {
            $total += $linha['salario'];
        }
        return Util::arredondar($total);
    }

    public function registrarVenda(float $valorVenda, ?int $idGarcom): void
    {
        foreach ($this->funcionarios as $funcionario) {
            $funcionario->aoFecharConta($valorVenda, $idGarcom);
        }
    }

    public function iniciarNovoMes(): void
    {
        foreach ($this->funcionarios as $funcionario) {
            $funcionario->iniciarNovoMes();
        }
    }

    public function toArray(): array
    {
        return [
            'proximo_id' => $this->proximoId,
            'funcionarios' => array_map(
                static function (Funcionario $funcionario): array {
                    return $funcionario->toArray();
                },
                array_values($this->funcionarios)
            ),
        ];
    }

    public static function deArray(array $dados): self
    {
        $equipe = new self();
        foreach ((array) ($dados['funcionarios'] ?? []) as $dadosFuncionario) {
            if (!is_array($dadosFuncionario)) {
                continue;
            }
            $equipe->contratar(FuncionarioFactory::criar((string) ($dadosFuncionario['tipo'] ?? ''), $dadosFuncionario));
        }
        if (isset($dados['proximo_id'])) {
            $equipe->proximoId = max($equipe->proximoId, Util::paraInt($dados['proximo_id'], 'próximo código'));
        }
        return $equipe;
    }
}
