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

# Enlaces al repositorio (sidebar, reglas y tests del kit), excluyendo el manifiesto del kit.
grep -rl --exclude-dir=.git --exclude='kit-manifest.json' -e 'https://github.com/luinuxscl/lnx' -e 'https://github.com/laravel/livewire-starter-kit' . 2>/dev/null \
    | xargs -r sed -i "s|https://github.com/luinuxscl/lnx|${REPO_URL}|g; s|https://github.com/laravel/livewire-starter-kit|${REPO_URL}|g"

# composer.json
sed -i "s|\"name\": \"laravel/livewire-starter-kit\"|\"name\": \"${GITHUB_OWNER}/${NAME}\"|" composer.json
sed -i "s|\"description\": \"The official Laravel starter kit for Livewire.\"|\"description\": \"${APP_NAME}, construido sobre el kit lnx (Laravel + Livewire).\"|" composer.json

# .env.example
sed -i "s|^APP_NAME=.*|APP_NAME=\"${APP_NAME}\"|" .env.example
sed -i "s|^APP_URL=.*|APP_URL=${DOMAIN_URL}|" .env.example
sed -i "s|^MAIL_FROM_ADDRESS=.*|MAIL_FROM_ADDRESS=\"no-reply@${DOMAIN}\"|" .env.example

# .ai/kit-manifest.json
if [[ -f .ai/kit-manifest.json ]]; then
    CURRENT_ISO="$(date -u +"%Y-%m-%dT%H:%M:%SZ")"
    sed -i "s|\"updated_at\": \".*\"|\"updated_at\": \"${CURRENT_ISO}\"|" .ai/kit-manifest.json
fi

# README
sed -i "s|^# lnx — Base de desarrollo Laravel$|# ${NAME} — Base de desarrollo Laravel\n\n- **Producción:** ${DOMAIN_URL}\n- **Repositorio:** ${REPO_URL}\n- **Kit base:** https://github.com/${GITHUB_OWNER}/lnx|" README.md

# Workflow de publicacion de la imagen: el build corre en GitHub Actions y sube
# la imagen a GHCR; Coolify despues solo hace pull. La plantilla vive en el kit y
# aqui se resuelve la identidad de la app.
if [[ ! -f templates/docker-publish.yml ]]; then
    echo "Falta templates/docker-publish.yml en el kit: no puedo dejar el workflow de despliegue." >&2
    exit 1
fi

mkdir -p .github/workflows
sed "s|<OWNER>/<APP>|${GITHUB_OWNER}/${NAME}|g; s|<scope>|${NAME}|g" \
    templates/docker-publish.yml > .github/workflows/publish.yml
echo "==> Workflow de publicacion en .github/workflows/publish.yml"

# La imagen base compartida y su workflow son del kit, no de la app:
# `docker/base/Dockerfile` y `base-image.yml` publican ghcr.io/luinuxscl/lnx-php y
# el token de la app no puede escribir en ese paquete (cada push dejaria un run
# rojo). La plantilla tampoco se queda: ya se aplico arriba.
rm -rf docker/base .github/workflows/base-image.yml templates
rmdir docker 2>/dev/null || true

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
  2. Apuntar el DNS A de ${DOMAIN} al VPS de Coolify.
  3. Desplegar en Coolify. El build corre en GitHub Actions y Coolify solo hace
     pull; la receta completa esta en DEPLOY.md del kit:
       https://github.com/${GITHUB_OWNER}/lnx/blob/main/DEPLOY.md

     En corto (el workflow .github/workflows/publish.yml ya quedo en el repo):
       a. Secretos del repo:
            COMPOSER_AUTH   JSON {"github-oauth":{"github.com":"<token con repo>"}}
            COOLIFY_TOKEN   token de la API de Coolify
       b. Variables del repo:
            COOLIFY_URL     https://vps.luinuxscl.com
            COOLIFY_UUID    UUID del recurso en Coolify (vacio = no dispara deploy)
       c. Coolify, una vez por servidor: Servers -> Docker Registries ->
          Multi-server login -> GitHub Container Registry, con usuario de GitHub y
          un token clasico de scope read:packages; sin esto no puede bajar la imagen.
       d. Recurso en Coolify: PostgreSQL gestionado, puerto 8080, healthcheck /up y
          volumen persistente en /var/www/html/storage/app/public. El recurso debe
          quedar como tipo *Docker Image* -> ghcr.io/${GITHUB_OWNER}/${NAME}:latest,
          con el auto-deploy por git apagado (el deploy lo dispara el workflow).
       e. Si la app lleva staging: copiar el workflow a la rama develop cambiando
          la rama, el tag (staging) y COOLIFY_UUID al del recurso de staging.
EOF