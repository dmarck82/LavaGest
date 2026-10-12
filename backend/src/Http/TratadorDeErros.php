<?php

declare(strict_types=1);

namespace LavaGest\Http;

use LavaGest\Excecoes\CredenciaisInvalidasException;
use LavaGest\Excecoes\EmailJaCadastradoException;
use LavaGest\Excecoes\TokenInvalidoException;
use InvalidArgumentException;
use JsonException;
use RuntimeException;
use Throwable;

class TratadorDeErros
{
    public function executar(callable $operacao): void
    {
        try {
            $operacao();
        } catch (JsonException $erro) {
            $this->responder(400, 'JSON inválido.');
        } catch (InvalidArgumentException $erro) {
            $this->responder(422, $erro->getMessage());
        } catch (CredenciaisInvalidasException $erro) {
            $this->responder(401, $erro->getMessage());
        } catch (TokenInvalidoException $erro) {
            $this->responder(401, $erro->getMessage());
        } catch (EmailJaCadastradoException $erro) {
            $this->responder(409, $erro->getMessage());
        } catch (RuntimeException $erro) {
            $this->responder(404, $erro->getMessage());
        } catch (Throwable $erro) {
            error_log($erro->getMessage());

            $this->responder(
                500,
                'Erro interno do servidor.'
            );
        }
    }

    private function responder(
        int $status,
        string $mensagem
    ): void {
        http_response_code($status);

        echo json_encode([
            'erro' => $mensagem
        ]);
    }
}
