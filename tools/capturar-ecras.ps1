# =====================================================================
# Captura os ecras da aplicacao para ilustrar o relatorio.
#
# Autentica-se como administrador, guarda o HTML de cada pagina dentro de
# public/ (a mesma origem do servidor, para os tipos de letra dos icones
# nao serem bloqueados por CORS) e fotografa-o com o Chrome ou o Edge em
# modo headless. O ficheiro temporario e removido no fim.
#
# Requer o servidor a correr:
#     php -S localhost:8000 -t public public/router.php
#     powershell -File tools\capturar-ecras.ps1
# =====================================================================

param(
    [string]$Base = "http://localhost:8000",
    # Pasta servida pelo servidor indicado em -Base, onde e escrito o ficheiro
    # temporario da captura. Por omissao e a pasta public/ deste projecto.
    [string]$RaizPublica = ""
)

$raiz   = Split-Path -Parent $PSScriptRoot
$destino = Join-Path $raiz "docs\imagens\ecras"
$base   = $Base.TrimEnd('/')
$publica = if ($RaizPublica -ne "") { $RaizPublica } else { Join-Path $raiz "public" }

if (-not (Test-Path $destino)) { New-Item -ItemType Directory -Path $destino | Out-Null }

$candidatos = @(
    "C:\Program Files\Google\Chrome\Application\chrome.exe",
    "C:\Program Files (x86)\Google\Chrome\Application\chrome.exe",
    "C:\Program Files (x86)\Microsoft\Edge\Application\msedge.exe",
    "C:\Program Files\Microsoft\Edge\Application\msedge.exe"
)
$navegador = $candidatos | Where-Object { Test-Path $_ } | Select-Object -First 1
if (-not $navegador) { Write-Error "Nao foi encontrado o Chrome nem o Edge."; exit 1 }

# ------------------------------ Autenticacao ------------------------------
$s = New-Object Microsoft.PowerShell.Commands.WebRequestSession
$r = Invoke-WebRequest -Uri "$base/login" -WebSession $s -UseBasicParsing
$null = $r.Content -match 'name="_csrf_token"\s+value="([^"]+)"'
$corpo = @{ "_csrf_token" = $matches[1]; email = "admin@farmagest.co.mz"; palavra_passe = "Admin@123" }
$null = Invoke-WebRequest -Uri "$base/login" -Method Post -Body $corpo -WebSession $s `
                          -UseBasicParsing -MaximumRedirection 0 -ErrorAction SilentlyContinue

# Primeira venda existente, para o recibo nao dar 404.
$venda = 1
try {
    $lista = Invoke-WebRequest -Uri "$base/vendas" -WebSession $s -UseBasicParsing
    if ($lista.Content -match '/vendas/(\d+)') { $venda = $matches[1] }
} catch { }

Add-Type -AssemblyName System.Drawing

# O Chrome captura exactamente o tamanho da janela, por isso as paginas curtas
# ficariam com uma faixa branca em baixo. Captura-se com folga e corta-se o
# excesso: as linhas do fundo sao todas iguais entre si, portanto o conteudo
# acaba na primeira linha (a contar de baixo) que difere da ultima.
function Recortar-Fundo([string]$ficheiro, [int]$margem = 24) {
    $original = [System.Drawing.Bitmap]::FromFile($ficheiro)
    try {
        $l = $original.Width
        $a = $original.Height
        $referencia = 0..([math]::Floor(($l - 1) / 8)) | ForEach-Object { $original.GetPixel($_ * 8, $a - 1).ToArgb() }

        $fim = $a - 1
        for ($y = $a - 2; $y -ge 0; $y--) {
            $diferente = $false
            for ($i = 0; $i -lt $referencia.Count; $i++) {
                if ($original.GetPixel($i * 8, $y).ToArgb() -ne $referencia[$i]) { $diferente = $true; break }
            }
            if ($diferente) { $fim = $y; break }
        }

        $novaAltura = [math]::Min($a, $fim + $margem)
        if ($novaAltura -ge $a - 4) { return $a }  # nada a cortar

        $recorte = New-Object System.Drawing.Bitmap($l, $novaAltura)
        $g = [System.Drawing.Graphics]::FromImage($recorte)
        $g.DrawImage($original, 0, 0, (New-Object System.Drawing.Rectangle(0, 0, $l, $novaAltura)), 'Pixel')
        $g.Dispose()
        $original.Dispose()
        $original = $null

        Remove-Item $ficheiro -Force
        $recorte.Save($ficheiro, [System.Drawing.Imaging.ImageFormat]::Png)
        $recorte.Dispose()
        return $novaAltura
    } finally {
        if ($original) { $original.Dispose() }
    }
}

# O carrinho da venda e construido no navegador, por isso o estado com itens
# so existe depois de o JavaScript correr. Simula-se a interaccao do operador.
$carrinho = @'
<script>
window.addEventListener("load", function () {
    setTimeout(function () {
        var botoes = document.querySelectorAll("[data-adicionar]");
        if (botoes[0]) { botoes[0].click(); botoes[0].click(); botoes[0].click(); }
        if (botoes[2]) { botoes[2].click(); botoes[2].click(); }
        if (botoes[5]) { botoes[5].click(); }
        var desconto = document.getElementById("desconto");
        if (desconto) {
            desconto.value = "50";
            desconto.dispatchEvent(new Event("input"));
        }
    }, 300);
});
</script>
'@

# nome | rota | largura | altura maxima | precisa de sessao | guiao a injectar
$paginas = @(
    @("01-login",          "/login",                   1440, 760,  $false, $null),
    @("02-painel",         "/painel",                  1440, 1400, $true,  $null),
    @("03-medicamentos",   "/medicamentos",            1440, 1400, $true,  $null),
    @("04-lotes-validade", "/lotes?filtro=a_expirar",  1440, 1400, $true,  $null),
    @("05-registar-venda", "/vendas/criar",            1440, 1400, $true,  $null),
    @("06-recibo",         "/vendas/$venda/recibo",    1000, 820,  $true,  $null),
    @("07-relatorio-vendas","/relatorios/vendas",      1440, 1010, $true,  $null),
    @("08-utilizadores",   "/utilizadores",            1440, 1400, $true,  $null),
    @("09-carrinho",       "/vendas/criar",            1440, 1400, $true,  $carrinho)
)

foreach ($p in $paginas) {
    $nome, $rota, $largura, $altura, $comSessao, $guiao = $p

    try {
        $html = if ($comSessao) {
            (Invoke-WebRequest -Uri "$base$rota" -WebSession $s -UseBasicParsing).Content
        } else {
            (Invoke-WebRequest -Uri "$base$rota" -UseBasicParsing).Content
        }
    } catch {
        Write-Output ("  [FALHA] {0} -> {1}" -f $nome, $_.Exception.Message)
        continue
    }

    # A pagina e servida pela propria origem: assim os tipos de letra dos
    # icones (Bootstrap Icons) nao sao bloqueados pela politica de CORS,
    # como aconteceria a partir de um ficheiro file:///.
    if ($guiao) { $html = $html -replace '</body>', ($guiao + '</body>') }

    $temp = Join-Path $publica "_captura.html"
    [IO.File]::WriteAllText($temp, $html, [Text.Encoding]::UTF8)

    $png = Join-Path $destino "$nome.png"
    if (Test-Path $png) { Remove-Item $png -Force }

    & $navegador --headless --disable-gpu --hide-scrollbars --force-device-scale-factor=1.5 `
                 --virtual-time-budget=4000 `
                 --window-size="$largura,$altura" --screenshot="$png" "$base/_captura.html" 2>$null

    for ($i = 0; $i -lt 40 -and -not (Test-Path $png); $i++) { Start-Sleep -Milliseconds 250 }
    Remove-Item $temp -Force -ErrorAction SilentlyContinue

    if (Test-Path $png) {
        $alturaFinal = Recortar-Fundo $png
        $kb = [math]::Round((Get-Item $png).Length / 1KB)
        Write-Output ("  [OK]    {0,-20} {1,-28} ({2}x{3}, {4} KB)" -f $nome, $rota, $largura, $alturaFinal, $kb)
    } else {
        Write-Output ("  [FALHA] {0}" -f $nome)
    }
}
