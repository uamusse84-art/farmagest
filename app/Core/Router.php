<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Encaminhador de pedidos. Alem de mapear metodo+caminho para controlador,
 * aplica os filtros transversais: autenticacao, perfis e verificacao CSRF.
 */
final class Router
{
    /** @var array<int, array{metodo: string, padrao: string, acao: array, opcoes: array}> */
    private array $rotas = [];

    public function get(string $caminho, array $acao, array $opcoes = []): self
    {
        return $this->adicionar('GET', $caminho, $acao, $opcoes);
    }

    public function post(string $caminho, array $acao, array $opcoes = []): self
    {
        return $this->adicionar('POST', $caminho, $acao, $opcoes);
    }

    private function adicionar(string $metodo, string $caminho, array $acao, array $opcoes): self
    {
        $this->rotas[] = [
            'metodo' => $metodo,
            'padrao' => $this->compilar($caminho),
            'acao'   => $acao,
            'opcoes' => $opcoes + ['auth' => true, 'perfis' => []],
        ];
        return $this;
    }

    private function compilar(string $caminho): string
    {
        $padrao = preg_replace('/\{(\w+)\}/', '(?P<$1>[^/]+)', $caminho);
        return '#^' . $padrao . '$#';
    }

    public function despachar(Request $pedido): void
    {
        $caminhosConhecidos = false;

        foreach ($this->rotas as $rota) {
            if (!preg_match($rota['padrao'], $pedido->caminho(), $encontrados)) {
                continue;
            }

            $caminhosConhecidos = true;
            if ($rota['metodo'] !== $pedido->metodo()) {
                continue;
            }

            $parametros = array_filter($encontrados, 'is_string', ARRAY_FILTER_USE_KEY);
            $pedido->definirParametros($parametros);

            $this->aplicarFiltros($pedido, $rota['opcoes']);

            [$classe, $metodo] = $rota['acao'];
            (new $classe())->{$metodo}($pedido);
            return;
        }

        if ($caminhosConhecidos) {
            throw new HttpException(405, 'O metodo utilizado nao e permitido nesta rota.');
        }

        throw HttpException::naoEncontrado('A pagina "' . $pedido->caminho() . '" nao existe.');
    }

    private function aplicarFiltros(Request $pedido, array $opcoes): void
    {
        if ($opcoes['auth'] && !Auth::autenticado()) {
            Session::flash('aviso', 'Inicie sessao para continuar.');
            Session::definir('_destino_pos_login', $pedido->caminho());
            header('Location: ' . Url::para('/login'));
            exit;
        }

        if ($opcoes['perfis'] !== [] && !Auth::temPerfil(...$opcoes['perfis'])) {
            throw HttpException::proibido(
                'O seu perfil (' . Auth::perfil() . ') nao tem permissao para aceder a esta funcionalidade.'
            );
        }

        if ($pedido->metodo() !== 'GET' && !Csrf::valido((string) $pedido->entrada(Csrf::nomeCampo(), ''))) {
            throw new HttpException(419, 'O formulario expirou ou e invalido. Por favor, tente novamente.');
        }
    }
}
