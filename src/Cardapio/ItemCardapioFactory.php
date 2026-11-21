<?php
/**
 * Classe: ItemCardapioFactory
 * Responsável: Erik Carvalho Nogueira (RA 2026106090008)
 * Descrição: padrão Factory — transforma o texto do tipo ("prato", "bebida", "sobremesa") no objeto da subclasse correta.
 */
declare(strict_types=1);

final class ItemCardapioFactory
{
    /** Classe utilitária: só tem métodos estáticos, por isso não pode ser instanciada. */
    private function __construct()
    {
    }

    /** @return array<string, string> tipo => nome legível, na ordem em que aparecem no cardápio */
    public static function tiposDisponiveis(): array
    {
        return [
            'prato' => 'Pratos',
            'bebida' => 'Bebidas',
            'sobremesa' => 'Sobremesas',
        ];
    }

    /**
     * Cria o item do tipo pedido a partir de um array de dados (campos do formulário ou do JSON).
     *
     * @throws DadosInvalidosException quando o tipo é desconhecido ou algum dado é inválido
     */
    public static function criar(string $tipo, array $dados): ItemCardapio
    {
        switch (strtolower(trim($tipo))) {
            case 'prato':
                return Prato::deArray($dados);
            case 'bebida':
                return Bebida::deArray($dados);
            case 'sobremesa':
                return Sobremesa::deArray($dados);
            default:
                throw DadosInvalidosException::campo(
                    'tipo',
                    sprintf('não aceita "%s" (use prato, bebida ou sobremesa)', $tipo)
                );
        }
    }
}
