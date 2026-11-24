<?php
/**
 * Classe: Persistivel
 * Responsável: Erik Carvalho Nogueira (RA 2026106090008)
 * Descrição: interface (contrato) de todo objeto que sabe se converter em array para ser gravado em JSON.
 */
declare(strict_types=1);

interface Persistivel
{
    public function toArray(): array;
}
