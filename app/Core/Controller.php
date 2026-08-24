<?php

declare(strict_types=1);

namespace App\Core;

abstract class Controller
{
    /**
     * Renderiza uma vista dentro do layout principal.
     * O nome usa pontos: 'medicamentos.formulario' -> app/Views/medicamentos/formulario.php
     */
    protected function ver(string $vista, array $dados = [], string $layout = 'principal'): void
    {
        $caminho = BASE_PATH . '/app/Views/' . str_replace('.', '/', $vista) . '.php';

        if (!is_file($caminho)) {
            throw new HttpException(500, "A vista '{$vista}' nao foi encontrada.");
        }

        $dados['erros']   = $dados['erros']   ?? Session::consumirErros();
        $dados['antigos'] = $dados['antigos'] ?? Session::consumirAntigos();

        extract($dados, EXTR_SKIP);

        ob_start();
        require $caminho;
        $conteudo = ob_get_clean();

        require BASE_PATH . '/app/Views/layouts/' . $layout . '.php';
    }

    protected function redirecionar(string $caminho): never
    {
        header('Location: ' . Url::para($caminho));
        exit;
    }

    protected function json(array $dados, int $estado = 200): never
    {
        http_response_code($estado);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($dados, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        exit;
    }

    /**
     * Valida os dados; em caso de falha guarda erros + valores submetidos
     * e devolve o utilizador ao formulario.
     */
    protected function validar(Request $pedido, array $regras, array $rotulos, string $rotaFalha): array
    {
        $validador = new Validator($pedido->todos(), $regras, $rotulos);

        if ($validador->falha()) {
            $antigos = $pedido->todos();
            unset($antigos['palavra_passe'], $antigos['palavra_passe_confirmacao'], $antigos[Csrf::nomeCampo()]);

            Session::guardarErros($validador->erros(), $antigos);
            Session::flash('erro', 'Corrija os campos assinalados e submeta novamente.');
            $this->redirecionar($rotaFalha);
        }

        return $pedido->todos();
    }

    /** Devolve o registo ou lanca 404. */
    protected function ouFalhar(?array $registo, string $mensagem = 'Registo nao encontrado.'): array
    {
        if ($registo === null) {
            throw HttpException::naoEncontrado($mensagem);
        }
        return $registo;
    }
}
