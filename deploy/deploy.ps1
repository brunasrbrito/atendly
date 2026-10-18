<#
.SYNOPSIS
    Publica o Atendly no alwaysdata a partir do seu computador (Windows).

.DESCRIPTION
    1. Compila CSS/JS localmente (npm run build).
    2. Baixa/atualiza o código no servidor a partir do GitHub (git clone / git pull).
    3. Envia public/build para o servidor (scp).
    4. Roda deploy/server.sh no servidor (composer, .env, migrations, cache).

    Envie antes seus commits para o GitHub (git push): o servidor baixa o código de lá.
    A senha do SSH é pedida a cada conexão; na instalação, a senha do banco também.

.EXAMPLE
    powershell -ExecutionPolicy Bypass -File .\deploy\deploy.ps1 -Conta minhaconta -Install
    powershell -ExecutionPolicy Bypass -File .\deploy\deploy.ps1 -Conta minhaconta
#>
param(
    [Parameter(Mandatory = $true)][string]$Conta,
    [switch]$Install
)

$ErrorActionPreference = 'Stop'
$ssh = "$Conta@ssh-$Conta.alwaysdata.net"
$repo = 'https://github.com/brunasrbrito/atendly.git'
$root = Split-Path -Parent $PSScriptRoot
Set-Location $root

function Step([string]$text) { Write-Host "`n==> $text" -ForegroundColor Cyan }

# Comandos externos (ssh, scp, npm) não disparam erro sozinhos: confere o código de saída.
function Check([string]$what) {
    if ($LASTEXITCODE -ne 0) {
        Write-Host "`nFalhou: $what. Veja a mensagem acima. Nada foi dado como publicado." -ForegroundColor Red
        exit 1
    }
}

if (git status --porcelain) {
    Write-Warning 'Há alterações não commitadas: elas NÃO vão para o servidor (ele baixa do GitHub).'
}
git fetch --quiet 2>$null
$ahead = git rev-list --count '@{u}..HEAD' 2>$null
if ($ahead -and [int]$ahead -gt 0) {
    Write-Warning "$ahead commit(s) ainda não enviados ao GitHub. Rode 'git push' antes, ou o servidor publicará a versão anterior."
}

Step 'Compilando CSS/JS'
npm run build
Check 'npm run build'

$mode = if ($Install) { 'install' } else { 'update' }

Step 'Baixando o código mais recente no servidor'
ssh $ssh "if [ -d ~/atendly/.git ]; then git -C ~/atendly pull --ff-only; else git clone $repo ~/atendly; fi"
Check 'atualizar o código no servidor (git)'

Step 'Enviando public/build'
ssh $ssh "rm -rf ~/atendly/public/build"
Check 'limpar public/build no servidor'
scp -r public/build "${ssh}:~/atendly/public/build"
Check 'enviar public/build (scp)'

Step "Rodando deploy no servidor ($mode)"
# -t abre um terminal interativo: necessário para digitar a senha do banco na instalação.
ssh -t $ssh "bash ~/atendly/deploy/server.sh $mode"
Check "deploy no servidor ($mode)"

Write-Host "`nPublicado em https://$Conta.alwaysdata.net" -ForegroundColor Green
