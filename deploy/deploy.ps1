<#
.SYNOPSIS
    Publica o Atendly no alwaysdata a partir do seu computador (Windows).

.DESCRIPTION
    1. Compila CSS/JS localmente (npm run build).
    2. Envia public/build para o servidor (scp).
    3. Roda deploy/server.sh no servidor (git pull, composer, migrations, cache).

    Envie antes seus commits para o GitHub (git push): o servidor baixa o código de lá.

.EXAMPLE
    .\deploy\deploy.ps1 -Conta minhaconta            # atualização
    .\deploy\deploy.ps1 -Conta minhaconta -Install   # primeira instalação
#>
param(
    [Parameter(Mandatory = $true)][string]$Conta,
    [switch]$Install
)

$ErrorActionPreference = 'Stop'
$ssh = "$Conta@ssh-$Conta.alwaysdata.net"
$root = Split-Path -Parent $PSScriptRoot
Set-Location $root

if (git status --porcelain) {
    Write-Warning 'Há alterações não commitadas: elas NÃO vão para o servidor (ele baixa do GitHub).'
}
$ahead = git rev-list --count '@{u}..HEAD' 2>$null
if ($ahead -and [int]$ahead -gt 0) {
    Write-Warning "$ahead commit(s) ainda não enviados ao GitHub. Rode 'git push' antes, ou o servidor publicará a versão anterior."
}

Write-Host "`n==> Compilando CSS/JS" -ForegroundColor Cyan
npm run build
if ($LASTEXITCODE -ne 0) { throw 'Falha no npm run build.' }

$mode = if ($Install) { 'install' } else { 'update' }

if ($Install) {
    Write-Host "`n==> Baixando o código no servidor" -ForegroundColor Cyan
    ssh $ssh "test -d ~/atendly/.git || git clone https://github.com/brunasrbrito/atendly.git ~/atendly"
}

Write-Host "`n==> Enviando public/build" -ForegroundColor Cyan
ssh $ssh "rm -rf ~/atendly/public/build && mkdir -p ~/atendly/public"
scp -r public/build "${ssh}:~/atendly/public/build"

Write-Host "`n==> Rodando deploy no servidor ($mode)" -ForegroundColor Cyan
ssh $ssh "bash ~/atendly/deploy/server.sh $mode"

Write-Host "`nPublicado em https://$Conta.alwaysdata.net" -ForegroundColor Green
