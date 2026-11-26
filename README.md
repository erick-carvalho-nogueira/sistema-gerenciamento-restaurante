# Sistema de Gerenciamento de Restaurante

Projeto final de **Programação Orientada a Objetos** — 2º ADS 2026-2 (IFRO).

**Integrantes:** Erik Carvalho Nogueira · Felipe Ferreira Rosas · Danilo Sales Coutinho

## O que o sistema faz

- O **Canto do Zé** é um sistema web de gerenciamento de restaurante, feito em PHP com POO pura (sem framework e sem banco de dados) para rodar no XAMPP, com visual de boteco próprio (CSS, ícones desenhados e fontes locais, sem depender da internet).
- **Cardápio:** cadastra pratos, bebidas e sobremesas; cada tipo calcula o preço do seu jeito (preço cheio, desconto por volume, "leve 3, pague 2") e informa setor e tempo de preparo.
- **Salão:** controla as mesas (livre, ocupada, reservada) e abre o pedido de cada mesa com o garçom responsável.
- **Pedidos:** lança os itens, soma o subtotal, aplica 10% de taxa de serviço e acompanha o pedido na cozinha (aberto → em preparo → pronto → entregue → pago; na tela, com a voz do Zé: anotado → na chapa → no balcão → na mesa → fechou).
- **Pagamento:** dinheiro (com troco), Pix (5% de desconto) ou cartão (débito, ou crédito em até 3x com juros); a mesa é liberada e a venda entra no faturamento do dia.
- **Equipe:** garçom, cozinheiro e gerente têm, cada um, a sua regra de salário (comissão, horas extras, bônus por meta) e a folha é calculada a partir delas.
- **Painel e Laboratório POO:** o painel (o "balcão") mostra os números do dia e o Laboratório (o "caderno de receitas") executa ao vivo cada requisito do trabalho.
- Os dados ficam em um arquivo JSON, criado sozinho (já com dados de exemplo) na primeira execução.

## Como executar no XAMPP

1. Copie esta pasta para `C:\xampp\htdocs\` (por exemplo, `C:\xampp\htdocs\restaurante`).
2. No **XAMPP Control Panel**, clique em **Start** no **Apache** (o MySQL não é necessário).
3. Acesse **http://localhost/restaurante/** (use o nome da pasta que você escolheu).

Na primeira visita o sistema cria `data/restaurante.json` com dados de exemplo (13 itens no cardápio, 5 funcionários, 8 mesas e 4 pedidos). Não há nada para configurar.

- **Requisitos:** PHP 7.4 ou superior (testado no Apache 2.4.58 + PHP 8.2.12 do XAMPP). A pasta `data/` precisa aceitar escrita, o que já é o padrão no XAMPP.
- **Sem Apache:** na raiz do projeto, rode `php -S localhost:8000` e abra http://localhost:8000/.
- **Apareceu "Falha no armazenamento":** confira a permissão de escrita da pasta `data/` ou apague `data/restaurante.json` (o sistema o recria).
- **Voltar aos dados de exemplo:** botão "Restaurar dados de exemplo" no Painel.

## Onde cada técnica obrigatória foi implementada

| # | Técnica exigida | Arquivo → classe / método | Como foi usada no restaurante |
|---|---|---|---|
| 1 | **Classe abstrata ou interface** (o "contrato") | `src/Cardapio/ItemCardapio.php` → `abstract class ItemCardapio`<br>`src/Funcionarios/Funcionario.php` → `abstract class Funcionario`<br>`src/Pagamentos/Pagamento.php` → `abstract class Pagamento`<br>`src/Contratos/Persistivel.php` → `interface Persistivel` | As três abstratas definem o que todo item, funcionário e forma de pagamento precisa saber fazer (métodos abstratos). A interface `Persistivel` (`toArray()`) é o contrato de tudo que é gravado em JSON. |
| 2 | **Pelo menos 2 subclasses**, cada uma com comportamento próprio | `src/Cardapio/` → `Prato`, `Bebida`, `Sobremesa`<br>`src/Funcionarios/` → `Garcom`, `Cozinheiro`, `Gerente`<br>`src/Pagamentos/` → `PagamentoDinheiro`, `PagamentoPix`, `PagamentoCartao` | 9 subclasses em 3 hierarquias. Ex.: `Bebida` dá 10% de desconto a partir de 6 unidades; `Sobremesa` aplica "leve 3, pague 2"; `Garcom` ganha comissão; `Gerente` ganha bônus por meta; `PagamentoPix` dá 5% de desconto. |
| 3 | **Polimorfismo real** (`foreach` ou função que recebe o tipo-base e chama um método que muda por subclasse) | `src/Pedidos/Pedido.php` → `getSubtotal()`, `estimarTempoPreparoMinutos()`, `getSetoresEnvolvidos()` (via `ItemPedido::getSubtotal()`)<br>`src/Funcionarios/Equipe.php` → `calcularFolha()`, `registrarVenda()`, `iniciarNovoMes()`<br>`src/Cardapio/Cardapio.php` → `descricoes()`<br>`src/Pagamentos/Pagamento.php` → `processar()` e `pagamento.php` (simulação) | Os `foreach` percorrem objetos do tipo-base (`ItemPedido`/`ItemCardapio`, `Funcionario`, `Pagamento`) e chamam o mesmo método (`calcularPreco()`, `calcularSalario()`, `calcularValorCobrado()`) sem saber a subclasse. Funções que recebem o tipo-base: `Pedido::adicionarItem(ItemCardapio $item, ...)`, `Pedido::pagar(Pagamento $pagamento)`, `Equipe::contratar(Funcionario $funcionario)`. **Ao vivo:** `demo.php`, seção 2. |
| 4 | **Atributos `private`/`protected`**, nunca `public`, com getters e setters | Todas as classes de `src/` (ex.: `ItemCardapio::setPreco()`, `Funcionario::setSalarioBase()`, `Mesa::ocupar()`) | Nenhum atributo é público (comprovado por reflexão em `tests/run_tests.php` e em `demo.php`, seção 3). Os setters validam e lançam exceção. `protected` só onde a subclasse precisa (`preco`, `salarioBase`). |
| 5 | **Exceção customizada** (`extends Exception`), lançada em cenário real e tratada com `try/catch` | `src/Excecoes/RestauranteException.php` → `class RestauranteException extends Exception` + 7 filhas<br>**Lançada em:** `Cardapio::buscarPorId()` (item que não existe), `Mesa::ocupar()` (mesa ocupada), `Pedido::adicionarItem()` e `Pedido::pagar()`, `PagamentoDinheiro::validar()` (dinheiro insuficiente)<br>**Tratada em:** `includes/helpers.php` (`executarAcao()`, `restaurante()`, `nomeDoGarcom()`), `cardapio.php`, `pedido.php`, `pagamento.php`, `mesas.php` | Buscar um item inexistente em `cardapio.php?codigo=9999`, abrir uma mesa ocupada ou pagar com dinheiro a menos mostram a mensagem na tela em vez de quebrar a página. **Ao vivo:** `demo.php`, seção 4. |
| 6 | **Membro estático ou constante** com propósito real | `Pedido::TAXA_SERVICO` (10%), `Pedido::MAX_ITENS`, `Pedido::$proximoNumero` *(static)*, `ItemCardapio::$totalInstancias` *(static)*, `Funcionario::$totalFuncionarios` *(static)*, `Mesa::CAPACIDADE_MAXIMA`, `Equipe::LIMITE_FUNCIONARIOS`, `PagamentoCartao::MAX_PARCELAS`, `Config` | Configuração fixa (taxa de serviço), limites máximos (itens por pedido, parcelas, lugares por mesa), contadores de instâncias e gerador do número do pedido. **Ao vivo:** `demo.php`, seção 5. |
| 7 | **Padrão de projeto** (extra) | **Singleton:** `src/Infra/Restaurante.php` → `Restaurante::getInstance()`<br>**Factory:** `ItemCardapioFactory`, `FuncionarioFactory`, `PagamentoFactory`<br>**Template Method:** `Pagamento::processar()` | O `Restaurante` é único (construtor e `__clone` privados). As Factories transformam o texto do formulário/JSON (`"prato"`, `"garcom"`, `"pix"`…) na subclasse certa. **Ao vivo:** `demo.php`, seção 6. |
| — | **Identificação dos autores** | Cabeçalho `Classe / Responsável (RA) / Descrição` no início de todos os arquivos | Conferido por `php scripts/autoria.php --verificar` e por `tests/run_tests.php`. |
| — | **Roda no XAMPP sem erro fatal** | `tests/run_tests.php`, `tests/smoke_http.php` | Testado no Apache 2.4.58 + PHP 8.2.12 do XAMPP (`php tests/smoke_http.php --url=...`). |

## Autoria por integrante

Cada arquivo começa com o cabeçalho de autoria exigido no enunciado. A lista abaixo é gerada a partir desses cabeçalhos (`php scripts/autoria.php --atualizar-readme`).

<!-- AUTORIA:INICIO -->
```
AUTORIA POR INTEGRANTE:
- Danilo Sales Coutinho - RA 2026106090020: responsável por mesas.php, pagamento.php, MesaIndisponivelException, PagamentoInvalidoException, Pagamento, PagamentoCartao, PagamentoDinheiro, PagamentoFactory, PagamentoPix, Mesa
- Erik Carvalho Nogueira - RA 2026106090008: responsável por assets/app.js, assets/style.css, cardapio.php, demo.php, includes/bootstrap.php, includes/helpers.php, includes/layout.php, includes/visual.php, index.php, pedido.php, pedidos.php, AnalisadorAutoria, scripts/autoria.php, Bebida, Cardapio, ItemCardapio, ItemCardapioFactory, Prato, Sobremesa, Persistivel, DadosInvalidosException, ItemNaoEncontradoException, PedidoInvalidoException, RestauranteException, Config, DadosExemplo, Restaurante, Util, ItemPedido, Pedido, StatusPedido, src/autoload.php, ClienteHttp, Teste, tests/run_tests.php, tests/smoke_http.php
- Felipe Ferreira Rosas - RA 2026106090004: responsável por funcionarios.php, ArmazenamentoException, FuncionarioNaoEncontradoException, Cozinheiro, Equipe, Funcionario, FuncionarioFactory, Garcom, Gerente, ArmazenamentoJson
```
<!-- AUTORIA:FIM -->

## Testes (rodar antes de subir para o GitHub)

```bash
php tests/run_tests.php                 # mais de 250 verificações: herança, polimorfismo, exceções, estáticos, padrões, JSON, fluxo completo
php tests/smoke_http.php                # mais de 140 verificações HTTP: sobe o servidor do PHP, visita todas as páginas e faz um pedido completo
php tests/smoke_http.php --url=http://localhost/restaurante/   # o mesmo teste no Apache do XAMPP
php scripts/autoria.php --verificar     # confere os cabeçalhos de autoria e se os RAs foram preenchidos
```

Se o `php` não for reconhecido no terminal, use o do XAMPP: `C:\xampp\php\php.exe tests\run_tests.php`.
Os testes usam arquivos temporários e **não** alteram `data/restaurante.json` (com `--url`, o teste restaura os dados de exemplo do sistema testado).

## Estrutura do projeto

```
index.php  mesas.php  pedidos.php  pedido.php  pagamento.php  cardapio.php  funcionarios.php  demo.php   ← páginas
includes/    bootstrap.php, helpers.php, layout.php (início comum das páginas), visual.php (peças do visual: mesa, comanda no trilho, cupom)
assets/      style.css, app.js, ilustracoes.svg (ícones), fontes/ (fontes locais, licença OFL)
src/
  Contratos/     Persistivel (interface)
  Excecoes/      RestauranteException e as 7 exceções específicas
  Cardapio/      ItemCardapio (abstrata), Prato, Bebida, Sobremesa, Cardapio, ItemCardapioFactory
  Funcionarios/  Funcionario (abstrata), Garcom, Cozinheiro, Gerente, Equipe, FuncionarioFactory
  Pedidos/       StatusPedido, Mesa, ItemPedido, Pedido
  Pagamentos/    Pagamento (abstrata), PagamentoDinheiro, PagamentoPix, PagamentoCartao, PagamentoFactory
  Infra/         Config, Util, ArmazenamentoJson, DadosExemplo, Restaurante (Singleton)
data/        restaurante.json (gerado na 1ª execução; não vai para o Git)
tests/       run_tests.php, smoke_http.php
scripts/     autoria.php (autoria por integrante, RAs e README)
docs/        PLANEJAMENTO.md
```

## Documentação

- [`docs/PLANEJAMENTO.md`](docs/PLANEJAMENTO.md) — planejamento feito antes de codar (classes, herança, polimorfismo, exceções, responsáveis, diagrama).

## Entrega

A versão entregue é a marcada pela tag `entrega-final`:

```bash
git tag entrega-final
git push --tags
```
