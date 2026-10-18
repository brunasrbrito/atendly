#!/usr/bin/env bash
# Roda NO SERVIDOR (alwaysdata), via SSH.
#
#   bash ~/atendly/deploy/server.sh install   -> primeira instalação (cria .env, banco e dados demo)
#   bash ~/atendly/deploy/server.sh update    -> atualizações seguintes (git pull + migrations)
#
# Os assets (CSS/JS) não são compilados aqui: o plano gratuito tem pouca RAM.
# Eles são gerados no seu computador e enviados pelo deploy/deploy.ps1.
set -euo pipefail

APP_DIR="${APP_DIR:-$HOME/atendly}"
REPO="${REPO:-https://github.com/brunasrbrito/atendly.git}"
MODE="${1:-update}"

say() { printf '\n\033[1;36m==> %s\033[0m\n' "$1"; }
fail() { printf '\n\033[1;31mERRO: %s\033[0m\n' "$1" >&2; exit 1; }

# PHP 8.4+ é obrigatório (definido em Environment > PHP no painel).
php -r 'exit(version_compare(PHP_VERSION, "8.4.1", ">=") ? 0 : 1);' \
    || fail "PHP $(php -r 'echo PHP_VERSION;') encontrado. Escolha PHP 8.4 ou superior em Environment > PHP."

if command -v composer >/dev/null 2>&1; then
    COMPOSER="composer"
elif [ -f "$HOME/composer.phar" ]; then
    COMPOSER="php $HOME/composer.phar"
else
    fail "Composer não encontrado. Instale em ~/composer.phar (https://getcomposer.org/download/)."
fi

if [ ! -d "$APP_DIR/.git" ]; then
    say "Clonando $REPO"
    git clone "$REPO" "$APP_DIR"
fi
cd "$APP_DIR"

if [ "$MODE" = "update" ]; then
    say "Atualizando código"
    git pull --ff-only
fi

say "Instalando dependências PHP (sem pacotes de desenvolvimento)"
$COMPOSER install --no-dev --optimize-autoloader --no-interaction --no-progress

if [ "$MODE" = "install" ]; then
    if [ ! -f .env ]; then
        cp deploy/env.alwaysdata.example .env
        fail "Criei o .env a partir do modelo. Edite ~/atendly/.env (banco, URL) e rode o install de novo."
    fi
    grep -q '\[conta\]' .env && fail "O .env ainda tem [conta]/[senha]. Preencha os dados e rode de novo."

    if ! grep -q '^APP_KEY=base64' .env; then
        php artisan key:generate --force
    fi
    if grep -q '^ATENDLY_INTEGRATION_TOKEN=$' .env; then
        sed -i "s|^ATENDLY_INTEGRATION_TOKEN=$|ATENDLY_INTEGRATION_TOKEN=$(php -r 'echo bin2hex(random_bytes(24));')|" .env
    fi
    if grep -q '^ATENDLY_DEMO_PASSWORD=$' .env; then
        DEMO_PASSWORD="Atendly-$(php -r 'echo bin2hex(random_bytes(4));')"
        sed -i "s|^ATENDLY_DEMO_PASSWORD=$|ATENDLY_DEMO_PASSWORD=$DEMO_PASSWORD|" .env
    fi
fi

say "Preparando pastas"
mkdir -p storage/framework/{cache/data,sessions,views} storage/logs bootstrap/cache
php artisan storage:link --force >/dev/null 2>&1 || true

php artisan config:clear >/dev/null

say "Banco de dados"
if [ "$MODE" = "install" ]; then
    php artisan migrate --force --seed
else
    php artisan migrate --force
fi

say "Otimizando para produção"
php artisan optimize

if [ ! -f public/build/manifest.json ]; then
    printf '\n\033[1;33mAtenção: public/build ainda não existe. Rode deploy/deploy.ps1 no seu computador para enviar CSS/JS.\033[0m\n'
fi

say "Pronto! $(grep '^APP_URL=' .env | cut -d= -f2)"
if [ -n "${DEMO_PASSWORD:-}" ]; then
    echo "Acesso: admin@atendly.test ou tecnico1@atendly.test  |  senha: $DEMO_PASSWORD"
    echo "(a senha também está em ATENDLY_DEMO_PASSWORD no ~/atendly/.env)"
fi
