# =====================================================================
# Converte os diagramas SVG de docs/imagens em PNG, usando o Chrome ou o
# Edge ja instalados em modo headless (nao e preciso instalar nada).
#
#     powershell -File tools\render-diagramas.ps1
# =====================================================================

$raiz = Split-Path -Parent $PSScriptRoot
$pasta = Join-Path $raiz "docs\imagens"

$candidatos = @(
    "C:\Program Files\Google\Chrome\Application\chrome.exe",
    "C:\Program Files (x86)\Google\Chrome\Application\chrome.exe",
    "C:\Program Files (x86)\Microsoft\Edge\Application\msedge.exe",
    "C:\Program Files\Microsoft\Edge\Application\msedge.exe"
)
$navegador = $candidatos | Where-Object { Test-Path $_ } | Select-Object -First 1
if (-not $navegador) { Write-Error "Nao foi encontrado o Chrome nem o Edge."; exit 1 }

foreach ($svg in Get-ChildItem -Path $pasta -Filter *.svg | Sort-Object Name) {
    $conteudo = Get-Content $svg.FullName -Raw

    # O SVG e XML: um erro de sintaxe faz o browser desenhar so metade do
    # diagrama, por isso vale a pena validar antes de gerar o PNG.
    try { $null = [xml]$conteudo }
    catch { Write-Output ("  [XML]   {0} -> {1}" -f $svg.Name, $_.Exception.Message); continue }

    $null = $conteudo -match 'width="(\d+)"\s+height="(\d+)"'
    $largura = [int]$matches[1]
    $altura  = [int]$matches[2]

    $png = [IO.Path]::ChangeExtension($svg.FullName, ".png")
    if (Test-Path $png) { Remove-Item $png -Force }

    $uri = "file:///" + ($svg.FullName -replace '\\', '/')
    & $navegador --headless --disable-gpu --hide-scrollbars --force-device-scale-factor=1.5 `
                 --window-size="$largura,$altura" --screenshot="$png" $uri

    # O Chrome escreve o ficheiro de forma assincrona; espera ate estar pronto.
    for ($i = 0; $i -lt 40 -and -not (Test-Path $png); $i++) { Start-Sleep -Milliseconds 250 }

    if (Test-Path $png) {
        $kb = [math]::Round((Get-Item $png).Length / 1KB)
        Write-Output ("  [OK]    {0}  ({1}x{2}, {3} KB)" -f $svg.Name, $largura, $altura, $kb)
    } else {
        Write-Output ("  [FALHA] {0}" -f $svg.Name)
    }
}
