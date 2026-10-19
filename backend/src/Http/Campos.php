<?php

declare(strict_types=1);

namespace LavaGest\Http;

use InvalidArgumentException;

// Verifica a estrutura dos campos. Regras como "placa válida" ficam nos modelos.
class Campos
{
    /**
     * @param array<string, string> $tipos
     * @param list<string> $obrigatorios
     */
    public static function validar(array $dados, array $tipos, array $obrigatorios = []): array
    {
        foreach ($obrigatorios as $campo) {
            if (!array_key_exists($campo, $dados)) {
                throw new InvalidArgumentException("O campo '{$campo}' é obrigatório.");
            }
        }

        $dadosValidados = [];

        foreach ($tipos as $campo => $tipo) {
            if (!array_key_exists($campo, $dados)) {
                continue;
            }

            $valor = $dados[$campo];

            // "?texto" e "?inteiro" também permitem null para limpar um campo opcional.
            $aceitaNulo = $tipo === '?texto' || $tipo === '?inteiro';

            if ($valor === null && $aceitaNulo) {
                $dadosValidados[$campo] = null;
                continue;
            }

            if ($tipo === 'texto' || $tipo === '?texto') {
                if (!is_string($valor)) {
                    throw new InvalidArgumentException("O campo '{$campo}' deve ser do tipo texto.");
                }
            } elseif ($tipo === 'inteiro' || $tipo === '?inteiro') {
                if (!is_int($valor)) {
                    throw new InvalidArgumentException("O campo '{$campo}' deve ser do tipo inteiro.");
                }
            } else {
                throw new InvalidArgumentException("Tipo de campo desconhecido: {$tipo}.");
            }

            // Somente os campos declarados pelo controlador entram no resultado.
            $dadosValidados[$campo] = $valor;
        }

        return $dadosValidados;
    }
}
