# =====================================================================
# FarmaGest - Verificacao de rotas (smoke test HTTP)
#
# Autentica-se como administrador e percorre todas as rotas de leitura,
# assinalando codigos diferentes de 200 ou vestigios de erro no HTML.
#
# Utilizacao (com o servidor embutido do PHP a correr em :8000):
#     powershell -File tests\crawl.ps1
#
# Ou contra a instalacao publicada no XAMPP:
#     powershell -File tests\crawl.ps1 -Base http://localhost/farmagest
# =====================================================================

param(
    [string]$Base = "http://localhost:8000"
)

$ErrorActionPreference = "Continue"
$base = $Base.TrimEnd('/')
$s = New-Object Microsoft.PowerShell.Commands.WebRequestSession

$r = Invoke-WebRequest -Uri "$base/login" -WebSession $s -UseBasicParsing
$null = $r.Content -match 'name="_csrf_token"\s+value="([^"]+)"'
$body = @{ "_csrf_token" = $matches[1]; email = "admin@farmagest.co.mz"; palavra_passe = "Admin@123" }
$null = Invoke-WebRequest -Uri "$base/login" -Method Post -Body $body -WebSession $s -UseBasicParsing -MaximumRedirection 0 -ErrorAction SilentlyContinue

# Primeira venda existente, para as rotas de detalhe e recibo nao darem 404
# apenas por o auto-incremento nao comecar em 1.
$primeiraVenda = 1
try {
    $lista = Invoke-WebRequest -Uri "$base/vendas" -WebSession $s -UseBasicParsing
    if ($lista.Content -match '/vendas/(\d+)') { $primeiraVenda = $matches[1] }
} catch { }

$rotas = @(
    "/painel",
    "/medicamentos", "/medicamentos?pesquisa=para", "/medicamentos?stock=baixo", "/medicamentos?stock=esgotado",
    "/medicamentos?pesquisa=a&stock=baixo", "/medicamentos/criar", "/medicamentos/1", "/medicamentos/1/editar",
    "/categorias", "/categorias?pesquisa=anal", "/categorias/criar", "/categorias/1/editar",
    "/fornecedores", "/fornecedores?pesquisa=a", "/fornecedores/criar", "/fornecedores/1/editar",
    "/clientes", "/clientes?pesquisa=a", "/clientes/criar", "/clientes/1", "/clientes/1/editar",
    "/lotes", "/lotes?pesquisa=LT", "/lotes?filtro=expirados", "/lotes?filtro=a_expirar",
    "/lotes?filtro=esgotados", "/lotes?filtro=validos",
    "/lotes/criar", "/lotes/criar?medicamento=1", "/lotes/1", "/lotes/1/editar",
    "/vendas", "/vendas?pesquisa=VD", "/vendas?estado=concluida", "/vendas?estado=anulada", "/vendas/criar",
    "/vendas/$primeiraVenda", "/vendas/$primeiraVenda/recibo",
    "/utilizadores", "/utilizadores?pesquisa=admin", "/utilizadores?perfil=caixa",
    "/utilizadores/criar", "/utilizadores/1/editar",
    "/relatorios", "/relatorios/vendas", "/relatorios/stock",
    "/relatorios/movimentos", "/relatorios/movimentos?tipo=entrada", "/relatorios/movimentos?tipo=saida"
)

$falhas = 0
foreach ($rota in $rotas) {
    try {
        $resposta = Invoke-WebRequest -Uri "$base$rota" -WebSession $s -UseBasicParsing -MaximumRedirection 0
        $codigo = $resposta.StatusCode
        $conteudo = $resposta.Content
    } catch {
        $codigo = if ($_.Exception.Response) { [int]$_.Exception.Response.StatusCode } else { 0 }
        $conteudo = ""
    }

    $problema = ""
    if ($codigo -ne 200) { $problema = "HTTP $codigo" }
    elseif ($conteudo -match "Fatal error|Warning:|Notice:|Undefined |Erro interno do servidor") { $problema = "erro no HTML" }

    if ($problema -ne "") {
        $falhas++
        Write-Output "  [FALHA] $rota -> $problema"
        if ($conteudo -match '<p class="mb-4">(.*?)</p>') { Write-Output "          $($matches[1])" }
    } else {
        Write-Output "  [OK]    $rota"
    }
}

Write-Output ""
Write-Output "Rotas testadas: $($rotas.Count) | Falhas: $falhas"
exit $falhas
