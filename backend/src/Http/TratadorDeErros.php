<?php

declare(strict_types=1);

namespace LavaGest\Http;

use InvalidArgumentException;
use JsonException;
use LavaGest\Excecoes\ConflitoException;
use LavaGest\Excecoes\NaoAutorizadoException;
use LavaGest\Excecoes\RecursoNaoEncontradoException;
use Throwable;

final class TratadorDeErros
{
    public function executar(callable $operacao): RespostaJson
    {
        try {
            return $operacao();
        } catch (JsonException $erro) {
            return new RespostaJson(['erro' => 'JSON inválido.'], 400);
        } catch (InvalidArgumentException $erro) {
            return new RespostaJson(['erro' => $erro->getMessage()], 422);
        } catch (NaoAutorizadoException $erro) {
            return new RespostaJson(['erro' => $erro->getMessage()], 401);
        } catch (ConflitoException $erro) {
            return new RespostaJson(['erro' => $erro->getMessage()], 409);
        } catch (RecursoNaoEncontradoException $erro) {
            return new RespostaJson(['erro' => $erro->getMessage()], 404);
        } catch (Throwable $erro) {
            error_log((string) $erro);

            return new RespostaJson(['erro' => 'Erro interno do servidor.'], 500);
        }
    }
}
