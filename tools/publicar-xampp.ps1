# =====================================================================
# Publica o projecto em C:\xampp\htdocs\farmagest.
#
# O projecto e desenvolvido na pasta do OneDrive, mas o Apache do XAMPP
# so serve o que estiver dentro de htdocs. Sem esta sincronizacao ficam
# duas copias a divergir - foi o que aconteceu com o ficheiro .env, em
# que a copia publicada apontava para um porto de MySQL diferente.
#
#     powershell -File tools\publicar-xampp.ps1
#
# A pasta docs/ (relatorio e imagens) nao e publicada: nao faz parte da
# aplicacao e ocupa varios megabytes.
# =====================================================================

param(
    [string]$Destino = "C:\xampp\htdocs\farmagest"
)

$origem = Split-Path -Parent $PSScriptRoot

if (-not (Test-Path (Join-Path $origem "public\index.php"))) {
    Write-Error "A pasta de origem nao parece ser o projecto FarmaGest: $origem"
    exit 1
}

$excluirPastas = @("docs", ".claude", ".git", "node_modules")

Write-Output "Origem : $origem"
Write-Output "Destino: $Destino"
Write-Output ""

if (-not (Test-Path $Destino)) {
    New-Item -ItemType Directory -Path $Destino -Force | Out-Null
}

$copiados = 0
$iguais = 0

$ficheiros = Get-ChildItem $origem -Recurse -File -Force | Where-Object {
    $relativo = $_.FullName.Substring($origem.Length + 1)
    $primeiro = ($relativo -split '\\')[0]
    ($excluirPastas -notcontains $primeiro) -and ($relativo -notlike "storage\logs\*")
}

foreach ($f in $ficheiros) {
    $relativo = $f.FullName.Substring($origem.Length + 1)
    $alvo = Join-Path $Destino $relativo

    if (Test-Path $alvo) {
        if ((Get-FileHash $f.FullName).Hash -eq (Get-FileHash $alvo).Hash) {
            $iguais++
            continue
        }
    }

    $pasta = Split-Path -Parent $alvo
    if (-not (Test-Path $pasta)) { New-Item -ItemType Directory -Path $pasta -Force | Out-Null }

    Copy-Item $f.FullName $alvo -Force
    Write-Output ("  [ACTUALIZADO] {0}" -f $relativo)
    $copiados++
}

# A pasta de registos tem de existir e ser gravavel pelo Apache.
$logs = Join-Path $Destino "storage\logs"
if (-not (Test-Path $logs)) { New-Item -ItemType Directory -Path $logs -Force | Out-Null }

Write-Output ""
Write-Output ("Ficheiros actualizados: {0} | ja iguais: {1}" -f $copiados, $iguais)
Write-Output "Aplicacao disponivel em http://localhost/farmagest/"
