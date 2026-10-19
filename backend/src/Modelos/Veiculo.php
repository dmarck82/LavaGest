<?php

declare(strict_types=1);

namespace LavaGest\Modelos;

use InvalidArgumentException;

class Veiculo
{
    private ?int $id;

    private int $cliente_id;

    private string $placa;

    private ?string $marca;

    private string $modelo;

    private ?string $cor;

    private ?int $ano_fabricacao;

    private string $categoria;

    private ?string $observacoes;

    private ?string $criado_em;

    public function __construct(
        int $cliente_id,
        string $placa,
        string $modelo,
        string $categoria,
        ?string $marca = null,
        ?string $cor = null,
        ?int $ano_fabricacao = null,
        ?string $observacoes = null,
        ?int $id = null,
        ?string $criado_em = null
    ) {
        $this->id = $id;
        $this->atualizar(
            $cliente_id,
            $placa,
            $modelo,
            $categoria,
            $marca,
            $cor,
            $ano_fabricacao,
            $observacoes
        );
        $this->criado_em = $criado_em;
    }

    public function atualizar(
        int $cliente_id,
        string $placa,
        string $modelo,
        string $categoria,
        ?string $marca = null,
        ?string $cor = null,
        ?int $ano_fabricacao = null,
        ?string $observacoes = null
    ): void {
        // Remove a pontuação e usa letras maiúsculas: abc-1234 vira ABC1234.
        $placaSemPontuacao = preg_replace('/[^A-Za-z0-9]/', '', $placa);
        $placa = strtoupper($placaSemPontuacao);

        if ($cliente_id < 1) {
            throw new InvalidArgumentException('Cliente inválido.');
        }

        if (!preg_match('/^[A-Z]{3}[0-9][A-Z0-9][0-9]{2}$/', $placa)) {
            throw new InvalidArgumentException('Placa inválida.');
        }

        $modelo = trim($modelo);
        if ($modelo === '') {
            throw new InvalidArgumentException('Modelo é obrigatório.');
        }

        $categoria = strtolower(trim($categoria));
        $categoriasPermitidas = ['moto', 'compacto', 'hatch', 'sedan', 'suv', 'caminhonete', 'van'];

        if (!in_array($categoria, $categoriasPermitidas, true)) {
            throw new InvalidArgumentException('Categoria de veículo inválida.');
        }

        if ($ano_fabricacao !== null) {
            $maiorAnoPermitido = (int) date('Y') + 1;

            if ($ano_fabricacao < 1886 || $ano_fabricacao > $maiorAnoPermitido) {
                throw new InvalidArgumentException('Ano de fabricação inválido.');
            }
        }

        $this->cliente_id = $cliente_id;
        $this->placa = $placa;
        $this->modelo = $modelo;
        $this->categoria = $categoria;
        $this->marca = $this->opcional($marca);
        $this->cor = $this->opcional($cor);
        $this->ano_fabricacao = $ano_fabricacao;
        $this->observacoes = $this->opcional($observacoes);
    }

    private function opcional(?string $valor): ?string
    {
        if ($valor === null) {
            return null;
        }

        $valor = trim($valor);

        if ($valor === '') {
            return null;
        }

        return $valor;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getClienteId(): int
    {
        return $this->cliente_id;
    }

    public function getPlaca(): string
    {
        return $this->placa;
    }

    public function getMarca(): ?string
    {
        return $this->marca;
    }

    public function getModelo(): string
    {
        return $this->modelo;
    }

    public function getCor(): ?string
    {
        return $this->cor;
    }

    public function getAnoFabricacao(): ?int
    {
        return $this->ano_fabricacao;
    }

    public function getCategoria(): string
    {
        return $this->categoria;
    }

    public function getObservacoes(): ?string
    {
        return $this->observacoes;
    }

    public function paraArray(): array
    {
        return [
            'id' => $this->id,
            'cliente_id' => $this->cliente_id,
            'placa' => $this->placa,
            'marca' => $this->marca,
            'modelo' => $this->modelo,
            'cor' => $this->cor,
            'ano_fabricacao' => $this->ano_fabricacao,
            'categoria' => $this->categoria,
            'observacoes' => $this->observacoes,
            'criado_em' => $this->criado_em
        ];
    }
}
