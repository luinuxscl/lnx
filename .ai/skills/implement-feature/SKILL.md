---
name: implement-feature
description: Procedimiento de principio a fin para implementar una funcionalidad en lnx coordinando reglas, componentes, i18n, pruebas y documentación.
---

# Implementar una funcionalidad (implement-feature)

Este procedimiento guía la implementación de un feature o historia de usuario de principio a fin en `lnx`, asegurando el cumplimiento de las convenciones de arquitectura, internacionalización, autorización, testing y documentación en una sola entrega.

## Cuándo usar
- Al desarrollar una nueva funcionalidad o historia de usuario que involucre modelos, controladores, componentes Livewire, vistas Blade o lógica de negocio.
- Al modificar comportamientos existentes que afecten la arquitectura, la base de datos o el flujo de usuario.

## Cuándo NO usar
- Para crear o pulir únicamente el diseño visual de una pantalla (usar [design-screen](file:///home/luinux/Code/lnx/.ai/skills/design-screen/SKILL.md)).
- Para expusar herramientas o recursos a través del servidor MCP de aplicación (usar [expose-mcp-operation](file:///home/luinux/Code/lnx/.ai/skills/expose-mcp-operation/SKILL.md)).
- Para ejecutar únicamente las comprobaciones de calidad, evidencia visual o cierre de entrega (usar [verify-delivery](file:///home/luinux/Code/lnx/.ai/skills/verify-delivery/SKILL.md)).

## Requisitos y entradas
1. **Requerimiento o Issue**: Criterios de aceptación definidos (por ejemplo, en GitHub Issues o [ROADMAP.md](file:///home/luinux/Code/lnx/ROADMAP.md)).
2. **Rama Git Flow**: Estar en una rama `feature/{nombre}` creada desde `develop` (o `fix/{nombre}` desde `main`).
3. **Lectura de reglas**: Inspeccionar [README.md](file:///home/luinux/Code/lnx/README.md) y [.ai/rules/index.md](file:///home/luinux/Code/lnx/.ai/rules/index.md) antes de escribir código.

## Resultado esperado
- Funcionalidad completamente integrada y funcional en la aplicación.
- Vistas bilingües (Español e Inglés) usando `__()` y claves registradas en `lang/es.json` y `lang/en.json`.
- Control de acceso por roles usando `App\Enums\Role` y policies/gates de Laravel.
- Pruebas unitarias o de integración pasando con Pest.
- Documentación afectada (README.md, guías o reglas) actualizada en el mismo trabajo.

## Límites de autorización y seguridad
- No modificar dependencias (`composer.json`, `package.json`) ni agregar paquetes nuevos sin aprobación explícita.
- No alterar roles predeterminados ni esquemas de permisos globales sin autorización.
- No incluir secretos, llaves personales, rutas locales absolutas ni nombres de clientes específicos en el código versionado.
- No conceder autorización para publicar en producción, enviar correos masivos o modificar datos reales no solicitados.

## Referencias
- [Paquetes y módulos](references/packages.md): qué puede declarar un paquete instalado en lnx (permisos, aplicaciones de IA, notificaciones, página de paquetes, escritura por MCP y skills). Resumen operativo de [`CONTRATO-MODULO.md`](file:///home/luinux/Code/lnx/CONTRATO-MODULO.md).

---

## Procedimiento paso a paso

### Paso 1: Inspección de contexto y reglas
1. Consultar [.ai/rules/index.md](file:///home/luinux/Code/lnx/.ai/rules/index.md) y leer las reglas aplicables a las rutas que se van a modificar (por ejemplo, [.ai/rules/general.md](file:///home/luinux/Code/lnx/.ai/rules/general.md) o [.ai/rules/views.md](file:///home/luinux/Code/lnx/.ai/rules/views.md)).
2. Revisar componentes existentes (Flux UI, componentes Blade de `resources/views/components/`) para reutilizar antes de crear nuevos.

### Paso 2: Implementación de la lógica y la interfaz
1. **Generación con Artisan**: Usar `php artisan make:model`, `php artisan make:livewire`, `php artisan make:migration`, etc.
2. **Internacionalización**: Garantizar que todo texto visible use `__('Text')` con sus traducciones en `lang/es.json` y `lang/en.json`.
3. **Autorización**: Aplicar comprobaciones con `$user->hasRole(Role::Root)` o `Role::Admin` utilizando siempre `App\Enums\Role`.
4. **Sidebar & Layout**: Si se añade una opción de navegación, ubicarla en la zona correspondiente de `resources/views/layouts/app/sidebar.blade.php` según [.ai/rules/views.md](file:///home/luinux/Code/lnx/.ai/rules/views.md).

### Paso 3: Pruebas de comportamiento y regresión
1. Crear o actualizar las pruebas en Pest (`php artisan make:test --pest {NombreTest}`).
2. Reutilizar las factories de modelos existentes.
3. Ejecutar la suite de pruebas del componente afectado o la suite compacta:
   ```bash
   php artisan test --compact
   ```

### Paso 4: Documentación obligatoria
1. Si la funcionalidad introduce rutas, comportamientos, configuraciones o variables de entorno nuevas, actualizar [README.md](file:///home/luinux/Code/lnx/README.md) en la misma entrega.
2. Si se definieron decisiones de arquitectura aplicables a futuras entregas, solicitar al usuario registrarlas con `record-rule`.

### Paso 5: Delegación a scripts de verificación determinista
Delegar la verificación de estilo y contexto agéntico a los comandos existentes:
1. Formatear PHP:
   ```bash
   vendor/bin/pint --dirty --format agent
   ```
2. Validar contexto agéntico y referencias de documentación:
   ```bash
   python3 scripts/check-agent-context.py
   ```
