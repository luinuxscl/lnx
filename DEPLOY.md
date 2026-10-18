# Despliegue de las apps lnx

Las apps derivadas se despliegan en Coolify (VPS propio) como contenedores Docker.

## Modelo vigente: build en GitHub, Coolify solo hace pull

```
push a main  ->  GitHub Actions  ->  ghcr.io/luinuxscl/<app>:<sha> y :latest
                                  ->  webhook de deploy de Coolify
                                  ->  Coolify hace pull + rolling update
```

El build **no** corre en el VPS. Eso saca la compilación de los mismos 6 vCPU que
sirven producción y deja el deploy en un pull.

### Números medidos

Deploy de `luz` con el modelo anterior (construyendo en Coolify, commit `aa0e111`):
**414 s**. Desglose:

| Paso | Tiempo |
|---|---|
| `install-php-extensions` (intl, bcmath, gd) | 248 s |
| `npm ci` | 51 s |
| `composer install` / `update` | 15 s |
| build de Vite | 15 s |
| exportar la imagen | 23 s |
| rolling update (healthcheck + apagar el contenedor viejo) | 57 s |

Con el modelo nuevo, el mismo deploy: **63 s** (build en GitHub aparte, ~3 min la
primera vez y ~1 min con la caché de GitHub).

### rukatv-editorial (main) y su staging (develop)

Un mismo repo alimenta **dos** recursos de Coolify: `main` -> `editorial.rukatv.cl`
(tag `latest`) y `develop` -> `staging.editorial.rukatv.cl` (tag `staging`). El
workflow resuelve tag y recurso segun la rama.

| App | Antes | Ahora |
|---|---|---|
| `luz` | 414 s | 63 s |
| `rukatv-editorial` | 286-472 s | 45-58 s |
| `rukatv-editorial-staging` | 319-416 s | 55 s |

Con `stop_grace_period=10` (antes 30) el deploy baja otros ~30 s.

## Cómo agregar una app

1. **Workflow**: copiar `templates/docker-publish.yml` a `.github/workflows/publish.yml`
   de la app y reemplazar `<OWNER>/<APP>` y `<scope>`.
2. **Secretos del repo** (Settings → Secrets and variables → Actions):
   - `COMPOSER_AUTH`: JSON `{"github-oauth":{"github.com":"<token>"}}`, para bajar
     los paquetes privados (el módulo y `lnx-ui`) durante el build.
   - `COOLIFY_TOKEN`: token de la API de Coolify, para disparar el deploy.
3. **Variables del repo**: `COOLIFY_URL` (`https://vps.luinuxscl.com`) y
   `COOLIFY_UUID` (el UUID del recurso en Coolify).
4. **Credenciales de GHCR en Coolify** (una vez por servidor): Servers →
   Docker Registries → Multi-server login → provider *GitHub Container Registry*,
   registry `ghcr.io`, usuario de GitHub y un **token clásico con `read:packages`**.
   Sin esto Coolify no puede bajar las imágenes privadas.
5. **Recurso en Coolify**: debe ser de tipo *Docker Image*
   (`build_pack = dockerimage`, `docker_registry_image_name = ghcr.io/luinuxscl/<app>`,
   `docker_registry_image_tag = latest`) y con el auto-deploy por git **apagado**
   (el deploy lo dispara el workflow). En ese modo Coolify usa
   `docker compose up --pull always`, así que siempre trae la imagen nueva.

## Restricciones y trampas conocidas

- **El workflow no puede vivir en el kit y ser reusable.** GitHub no permite llamar
  a un workflow reusable alojado en otro repositorio **privado** del mismo usuario;
  la excepción es dentro de la misma organización. Verificado: al llamarlo desde
  `luz-app` la API responde `workflow was not found`. Por eso cada app tiene su
  copia, y la plantilla vive en `templates/docker-publish.yml`.
- **Coolify no convierte un recurso existente en *Docker Image*.** Su selector de
  "Build strategy" solo ofrece railpack, nixpacks, static, dockerfile y compose.
  Tampoco la API (`PATCH /applications/{uuid}` rechaza `build_pack=dockerimage`).
  El cambio hay que hacerlo sobre la base de datos del recurso:
  `update applications set build_pack='dockerimage', docker_registry_image_name='ghcr.io/luinuxscl/<app>', docker_registry_image_tag='latest' where uuid='<uuid>';`
  más `is_auto_deploy_enabled=false` en `application_settings`.
- **El Dockerfile debe declarar `ARG COMPOSER_AUTH`** en la etapa que corre Composer.
  Si el workflow pasa el build arg pero el Dockerfile no lo declara, se ignora en
  silencio y el build falla con `404` al bajar `luinuxscl/lnx-ui`.
- **`if: ${{ secrets.X != '' }}` en un paso invalida el workflow completo.** GitHub
  lo rechaza al cargarlo: el run aparece en 0 s, sin jobs y con conclusion `failure`.
  Eso tuvo muertos los `tests.yml` de las apps derivadas desde el primer dia. El
  secreto se pasa por `env:` y se evalua en shell.
- **`stop_grace_period`** es un entero en segundos y se cambia por API
  (`PATCH /applications/{uuid}` con `{"stop_grace_period": 10}`). Los contenedores
  no responden al SIGTERM, asi que hoy se comen el timeout completo.

- **La imagen base compartida `ghcr.io/luinuxscl/lnx-php:8.5`** (`docker/base/Dockerfile`,
  publicada por `base-image.yml`) todavía está **privada**. Mientras siga así las apps
  no pueden usarla en el `FROM` (un runner de GitHub no puede leer un paquete privado
  de otro repo). Publicarla ahorra ~1 min en builds en frío.
- **`docker/base/Dockerfile` no debe construirse en cada app**: el build de GitHub ya
  cachea la capa de extensiones; la base compartida solo aporta en builds en frío.
