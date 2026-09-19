#!/usr/bin/env bash
#
# Crea un proyecto nuevo a partir del kit lnx.
#
# Uso:
#   scripts/new-project.sh <nombre> <dominio> [--app-name "Nombre Visible"] \
#       [--github-owner OWNER] [--target RUTA] [--skip-setup]
#
# Ejemplo:
#   scripts/new-project.sh rukatv-admin admin.rukatv.cl --app-name "Ruka TV Admin"
#
# El script:
#   1. Copia solo los archivos versionados del kit (git archive), sin residuos.
#   2. Inicializa git con la rama main y crea develop (git flow).
#   3. Sustituye identidad: APP_NAME, APP_URL, mail from, composer name,
#      README, enlaces de repositorio (sidebar, reglas, tests).
#   4. Deja la app lista en local (composer, npm, key, migrate --seed, build).
#
set -euo pipefail

usage() {
    grep '^#' "$0" | sed 's/^# \{0,1\}//' | sed -n '1,20p'
    exit "${1:-1}"
}

[[ $# -ge 2 ]] || usage 1

NAME="$1"
DOMAIN="$2"
shift 2

APP_NAME=""
GITHUB_OWNER="${GITHUB_OWNER:-luinuxscl}"
TARGET=""
RUN_SETUP=true

while [[ $# -gt 0 ]]; do
    case "$1" in
        --app-name) APP_NAME="$2"; shift 2 ;;
        --github-owner) GITHUB_OWNER="$2"; shift 2 ;;
        --target) TARGET="$2"; shift 2 ;;
        --skip-setup) RUN_SETUP=false; shift ;;
        -h|--help) usage 0 ;;
        *) echo "Opción desconocida: $1" >&2; usage 1 ;;
    esac
done

[[ "$NAME" =~ ^[a-z0-9][a-z0-9-]*$ ]] || { echo "Nombre inválido: usa minúsculas, números y guiones." >&2; exit 1; }
[[ "$DOMAIN" =~ ^([a-z0-9-]+\.)+[a-z]{2,}$ ]] || { echo "Dominio inválido: $DOMAIN" >&2; exit 1; }
[[ -n "$APP_NAME" ]] || APP_NAME="$NAME"

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
SOURCE_DIR="$(dirname "$SCRIPT_DIR")"
[[ -n "$TARGET" ]] || TARGET="$(dirname "$SOURCE_DIR")/$NAME"

REPO_URL="https://github.com/${GITHUB_OWNER}/${NAME}"
DOMAIN_URL="https://${DOMAIN}"

[[ -e "$TARGET" ]] && { echo "El destino ya existe: $TARGET" >&2; exit 1; }

echo "==> Creando $NAME en $TARGET"
mkdir -p "$TARGET"
git -C "$SOURCE_DIR" archive HEAD | tar -x -C "$TARGET"

cd "$TARGET"
git init -b main >/dev/null

echo "==> Ajustando identidad del proyecto"
export NAME APP_NAME DOMAIN REPO_URL DOMAIN_URL GITHUB_OWNER

# Enlaces al repositorio (sidebar, reglas y tests del kit).
grep -rl --exclude-dir=.git -e 'https://github.com/luinuxscl/lnx' -e 'https://github.com/laravel/livewire-starter-kit' . 2>/dev/null \
    | xargs -r sed -i "s|https://github.com/luinuxscl/lnx|${REPO_URL}|g; s|https://github.com/laravel/livewire-starter-kit|${REPO_URL}|g"

# composer.json
sed -i "s|\"name\": \"laravel/livewire-starter-kit\"|\"name\": \"${GITHUB_OWNER}/${NAME}\"|" composer.json
sed -i "s|\"description\": \"The official Laravel starter kit for Livewire.\"|\"description\": \"${APP_NAME}, construido sobre el kit lnx (Laravel + Livewire).\"|" composer.json

# .env.example
sed -i "s|^APP_NAME=.*|APP_NAME=\"${APP_NAME}\"|" .env.example
sed -i "s|^APP_URL=.*|APP_URL=${DOMAIN_URL}|" .env.example
sed -i "s|^MAIL_FROM_ADDRESS=.*|MAIL_FROM_ADDRESS=\"no-reply@${DOMAIN}\"|" .env.example

# README
sed -i "s|^# lnx — Base de desarrollo Laravel$|# ${NAME} — Base de desarrollo Laravel\n\n- **Producción:** ${DOMAIN_URL}\n- **Repositorio:** ${REPO_URL}\n- **Kit base:** https://github.com/${GITHUB_OWNER}/lnx|" README.md

git add -A
git commit -q -m "chore: proyecto inicial ${NAME} a partir del kit lnx"
git branch develop

echo "==> Repositorio git inicializado (main + develop)"

if [[ "$RUN_SETUP" == true ]]; then
    echo "==> Instalando dependencias y preparando la app"
    composer install --no-interaction --no-progress
    npm install
    cp .env.example .env
    php artisan key:generate --ansi
    php artisan migrate --seed --force
    php artisan storage:link
    npm run build
fi

cat <<EOF

==> Proyecto creado: $TARGET

Siguientes pasos:
  1. Crear el repositorio remoto:
       gh repo create ${GITHUB_OWNER}/${NAME} --private --source=. --remote=origin --push
       git push -u origin develop
  2. Desplegar en Coolify (usa la skill global coolify-laravel-deploy):
       - PostgreSQL gestionado
       - App build-pack dockerfile, puerto 8080, dominio ${DOMAIN_URL}, healthcheck /up
       - Volumen persistente en /var/www/html/storage/app/public
  3. Apuntar el DNS A de ${DOMAIN} al VPS de Coolify.
EOF