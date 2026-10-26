# Cómo contribuir a lnx

Gracias por el interés. El kit es MIT y las contribuciones son bienvenidas, con dos condiciones
claras:

## 1. Firma el CLA antes del primer pull request

Toda contribución externa necesita el [Acuerdo de Licencia de Contribución](docs/CLA.md). Comenta
en tu pull request:

```
He leído el CLA y acepto sus términos.
```

Sin esa firma no se puede aceptar el cambio. Es lo que permite al proyecto sostener su mapa de
licencias (kit MIT, producto AGPL) sin arrastrar dudas sobre la titularidad.

## 2. Respeta la frontera kit / producto

- **Kit (`lnx`, este repositorio):** capacidades genéricas y reutilizables —autenticación, permisos,
  navegación, theming, APIs, MCP de lectura, IA con cuotas, notificaciones, despliegue—. Licencia MIT.
- **Producto (módulos como el CRM, su cáscara y el agente del producto):** lógica de negocio y la
  experiencia del agente. Vive en otro repositorio, bajo AGPL-3.0-or-later. **No se acepta aquí.**

Si dudas dónde va algo: es genérico y cualquier app lo necesita → kit; es lo que hace que
LikePlatform sea LikePlatform → producto.

## Antes de abrir el pull request

```bash
composer setup          # instala dependencias, .env, migraciones y build
composer ci:check       # Pint + PHPStan + Pest: es lo que corre el CI
```

- Añade o ajusta pruebas en `tests/` para cualquier cambio de comportamiento.
- Respeta el estilo del proyecto (Pint) y las reglas de `.ai/rules/`.
- Un pull request, un tema. Explica **qué** cambia y **por qué**; si toca UI, incluye una captura.

## Convención de commits

`tipo(alcance): descripción en imperativo` — por ejemplo
`fix(navigation): respeta los overrides de root al reconciliar`. Tipos habituales: `feat`, `fix`,
`docs`, `refactor`, `test`, `chore`.

## Reportar problemas

Abre un issue con el entorno (versión de PHP, base de datos, navegador), los pasos para reproducir y
lo que esperabas ver. No incluyas secretos, credenciales ni datos personales de terceros.

## Seguridad

No abras un issue público para una vulnerabilidad: escribe al titular indicado en el
[CLA](docs/CLA.md) o usa el reporte privado de vulnerabilidades de GitHub (*Security → Advisories*).
