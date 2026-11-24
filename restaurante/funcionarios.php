<?php
/**
 * Arquivo: funcionarios.php
 * Responsável: Felipe Ferreira Rosas (RA 2026106090004)
 * Descrição: página do módulo Equipe — lista funcionários, mostra a folha de pagamento (polimorfismo), contrata, desliga e registra horas extras.
 */
require __DIR__ . '/includes/bootstrap.php';

$restaurante = restaurante();
$equipe = $restaurante->getEquipe();

if (ehPost()) {
    switch (post('acao')) {
        case 'contratar':
            executarAcao(static function () use ($equipe): void {
                $equipe->contratar(FuncionarioFactory::criar(post('tipo'), $_POST));
            }, 'Funcionário contratado.');
            break;

        case 'demitir':
            executarAcao(static function () use ($equipe): void {
                $equipe->demitir(Util::paraInt(post('id'), 'código'));
            }, 'Funcionário desligado da equipe.');
            break;

        case 'horas_extras':
            executarAcao(static function () use ($equipe): void {
                $funcionario = $equipe->buscarPorId(Util::paraInt(post('id'), 'código'));
                if (!$funcionario instanceof Cozinheiro) {
                    throw DadosInvalidosException::campo('funcionário', 'não é cozinheiro: só cozinheiros registram horas extras');
                }
                $funcionario->registrarHorasExtras(Util::paraFloat(post('horas'), 'horas extras'));
            }, 'Horas extras registradas.');
            break;

        case 'novo_mes':
            executarAcao(static function () use ($equipe): void {
                $equipe->iniciarNovoMes();
            }, 'Novo mês iniciado: vendas, horas extras e faturamento foram zerados.');
            break;
    }
    redirecionar('funcionarios.php');
}

$folha = $equipe->calcularFolha();
$cargos = FuncionarioFactory::tiposDisponiveis();

cabecalho('Turma', 'funcionarios.php');
?>
    <h1>A turma do Zé</h1>
    <p class="subtitulo"><?= e($equipe->quantidade()) ?> funcionários · folha do mês: <strong><?= e(moeda($equipe->totalFolha())) ?></strong></p>

    <?php if (count($folha) === 0): ?>
        <div class="cartao vazio"><?= ilustracaoVazia('A turma tá de folga') ?>
            <p>Nenhum funcionário cadastrado.</p>
        </div>
    <?php else: ?>
        <div class="tabela-rolagem">
            <table class="tabela">
                <thead>
                    <tr><th>Cód.</th><th>Funcionário</th><th class="num">Salário base</th><th class="num">Salário do mês</th><th>Ações</th></tr>
                </thead>
                <tbody>
                <?php foreach ($folha as $linha): $funcionario = $linha['funcionario']; ?>
                    <tr>
                        <td>#<?= e($funcionario->getId()) ?></td>
                        <td>
                            <?= iconeDoCargo($funcionario) ?> <strong><?= e($funcionario->getNome()) ?></strong> <span class="selo"><?= e($linha['cargo']) ?></span>
                            <span class="detalhe">admitido em <?= e(date('d/m/Y', (int) strtotime($funcionario->getDataAdmissao()))) ?></span>
                            <span class="detalhe"><?= e($funcionario->getDetalhes()) ?></span>
                        </td>
                        <td class="num"><?= e(moeda($funcionario->getSalarioBase())) ?></td>
                        <td class="num"><strong><?= e(moeda($linha['salario'])) ?></strong></td>
                        <td>
                            <div class="acoes">
                                <?php if ($funcionario instanceof Cozinheiro): ?>
                                    <form method="post" action="funcionarios.php" class="linha">
                                        <input type="hidden" name="acao" value="horas_extras">
                                        <input type="hidden" name="id" value="<?= e($funcionario->getId()) ?>">
                                        <div class="campo campo-inline">
                                            <input type="text" name="horas" inputmode="decimal" size="4" placeholder="h extras" aria-label="Horas extras de <?= e($funcionario->getNome()) ?>">
                                        </div>
                                        <button type="submit" class="botao-claro botao-pequeno">+ horas</button>
                                    </form>
                                <?php endif; ?>
                                <form method="post" action="funcionarios.php" data-confirmar="Desligar <?= e($funcionario->getNome()) ?> da equipe?">
                                    <input type="hidden" name="acao" value="demitir">
                                    <input type="hidden" name="id" value="<?= e($funcionario->getId()) ?>">
                                    <button type="submit" class="botao-perigo botao-pequeno">Desligar</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr><td colspan="3">Total da folha</td><td class="num"><?= e(moeda($equipe->totalFolha())) ?></td><td></td></tr>
                </tfoot>
            </table>
        </div>
    <?php endif; ?>

    <div class="grade grade-2">
        <section>
            <h2>Como cada cargo calcula o salário</h2>
            <div class="cartao">
                <ul class="limpa">
                    <li><strong>Garçom:</strong> salário base + <?= e(Util::percentual(Garcom::PERCENTUAL_COMISSAO)) ?> das vendas das mesas que atendeu.</li>
                    <li><strong>Cozinheiro:</strong> salário base + horas extras pagas com <?= e(Util::percentual(Cozinheiro::FATOR_HORA_EXTRA - 1)) ?> de adicional (máximo de <?= e((int) Cozinheiro::LIMITE_HORAS_EXTRAS) ?> h por mês).</li>
                    <li><strong>Gerente:</strong> salário base + <?= e(Util::percentual(Gerente::PERCENTUAL_BONUS)) ?> de bônus quando a meta mensal de faturamento é atingida.</li>
                </ul>
                <p class="pequeno mt">Quando uma conta é paga, <code class="codigo">Equipe::registrarVenda()</code> avisa todos os funcionários e cada cargo reage de um jeito.</p>
                <form method="post" action="funcionarios.php" data-confirmar="Zerar vendas, horas extras e faturamento do mês?">
                    <input type="hidden" name="acao" value="novo_mes">
                    <button type="submit" class="botao-claro">Iniciar novo mês</button>
                </form>
            </div>
        </section>

        <section>
            <h2>Contratar funcionário</h2>
            <div class="cartao">
                <form method="post" action="funcionarios.php">
                    <input type="hidden" name="acao" value="contratar">
                    <div class="campo">
                        <label for="tipo">Cargo</label>
                        <select id="tipo" name="tipo" data-alternar="cargo">
                            <?php foreach ($cargos as $valor => $rotulo): ?>
                                <option value="<?= e($valor) ?>"><?= e($rotulo) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="campo">
                        <label for="nome">Nome completo</label>
                        <input type="text" id="nome" name="nome" maxlength="<?= e(Funcionario::TAMANHO_MAXIMO_NOME) ?>" required>
                    </div>
                    <div class="form-grade">
                        <div class="campo">
                            <label for="salario_base">Salário base (R$)</label>
                            <input type="text" id="salario_base" name="salario_base" inputmode="decimal" placeholder="ex.: 1.800,00" required>
                        </div>
                        <div class="campo">
                            <label for="admissao">Admissão</label>
                            <input type="date" id="admissao" name="admissao" value="<?= e(date('Y-m-d')) ?>">
                        </div>
                    </div>
                    <div class="campo" data-grupo="cargo" data-valor="cozinheiro">
                        <label for="especialidade">Especialidade</label>
                        <input type="text" id="especialidade" name="especialidade" maxlength="<?= e(Cozinheiro::TAMANHO_MAXIMO_ESPECIALIDADE) ?>" placeholder="ex.: Grelhados">
                    </div>
                    <div class="campo" data-grupo="cargo" data-valor="gerente">
                        <label for="meta_mensal">Meta mensal de faturamento (R$)</label>
                        <input type="text" id="meta_mensal" name="meta_mensal" inputmode="decimal" placeholder="ex.: 30.000,00">
                    </div>
                    <button type="submit">Contratar</button>
                </form>
            </div>
        </section>
    </div>
<?php rodape(); ?>
