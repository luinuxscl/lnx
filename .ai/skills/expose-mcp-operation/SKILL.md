---
name: expose-mcp-operation
description: Procedimiento de principio a fin para exponer y validar herramientas/recursos de solo lectura en el servidor MCP de lnx.
---

# Exponer una operación por MCP (expose-mcp-operation)

Este procedimiento define el estándar de desarrollo para añadir o modificar herramientas en el servidor [Laravel MCP](file:///home/luinux/Code/lnx/README.md#servidor-mcp-lectura) de `lnx`, asegurando que todas las operaciones expuestas sean estrictamente de solo lectura, seguras, autorizadas, auditadas y probadas.

## Cuándo usar
- Al crear una nueva herramienta MCP en `app/Mcp/Tools/` para exponer consultas autorizadas a agentes externos.
- Al actualizar esquemas, parámetros, políticas o respuestas de herramientas MCP existentes.

## Cuándo NO usar
- Para integrar clientes o consumidores MCP externos dentro de la lógica del sistema.
- Para editar la configuración de plugins o servidores MCP locales del entorno del desarrollador o del agente.
- Para intentar exponer operaciones de escritura, borrado o modificación (el servidor MCP de lnx es exclusivamente de solo lectura).

## Requisitos y entradas
1. **Contrato de la herramienta**: Nombre en kebab-case (`#[Name]`), descripción clara en inglés (`#[Description]`), argumentos requeridos y esquema de salida.
2. **Regla de autorización**: Definición del rol (`root`, `admin`) o policy de Eloquent que autoriza la consulta.
3. **Servidor de lectura activo**: Referencia a la arquitectura documentada en la sección **Servidor MCP (lectura)** de [README.md](file:///home/luinux/Code/lnx/README.md).

## Resultado esperado
- Clase de herramienta en `app/Mcp/Tools/` extendiendo `ReadOnlyTool` (con la anotación `#[IsReadOnly]`).
- Definición formal de contrato (`schema()`, `outputSchema()`) y mensajes en inglés.
- Control de acceso doble en `authorize()` y `shouldRegister()` reutilizando policies o roles de Laravel.
- Registro de la herramienta en `App\Mcp\Servers\LnxServer::$tools` y actualización de `App\Mcp\Capabilities`.
- Auditoría de invocaciones en la tabla `mcp_tool_invocations` con cabecera `X-Correlation-Id`.
- Suite de pruebas Pest cubriendo respuestas autorizadas, fallos de autenticación (401) y denegación de acceso (403).

## Límites de autorización y seguridad
- El servidor MCP es de **solo lectura**: nunca exponer herramientas que realicen `insert`, `update`, `delete` o alteren el estado del sistema.
- No exponer comandos de terminal, consultas SQL arbitrarias ni accesos genéricos al sistema de archivos.
- No omitir `authorize()` ni `shouldRegister()`: una herramienta sin autorización viola las políticas de seguridad del proyecto.
- Nunca incluir secretos, tokens Bearer de producción ni datos sensibles no autorizados en los esquemas de respuesta.

---

## Procedimiento paso a paso

### Paso 1: Crear la clase de la herramienta
1. Crear la clase en `app/Mcp/Tools/` extendiendo `App\Mcp\Tools\ReadOnlyTool`.
2. Asignar los atributos `#[Name('nombre-herramienta')]` y `#[Description('Description in English for AI agents')]`.

### Paso 2: Configurar la autorización e identidad
1. Reutilizar las policies o enum de roles existentes (`App\Enums\Role`):
   ```php
   public function authorize(User $user): bool
   {
       return $user->hasRole(Role::Root);
   }
   ```
2. Asegurar que `shouldRegister(Request $request)` aplique exactamente la misma comprobación de autorización para evitar que la herramienta sea visible en el descubrimiento si el usuario no tiene permiso.

### Paso 3: Definir contrato, validación y respuesta
1. Implementar `schema()` y `outputSchema()` definiendo con precisión los tipos y descripciones de parámetros en inglés.
2. Validar los argumentos de la petición en `perform(Request $request)` mediante `$request->validate(...)`.
3. Devolver la respuesta en formato estructurado usando `Response::structured(...)`.

### Paso 4: Registrar en el servidor y capacidades
1. Registrar la nueva clase en la propiedad estática `App\Mcp\Servers\LnxServer::$tools`.
2. Actualizar `App\Mcp\Capabilities` si la herramienta debe figurar en el descubrimiento de `list-capabilities`.

### Paso 5: Pruebas automatizadas en Pest
Crear o actualizar la prueba en `tests/Feature/Mcp/` para verificar:
1. **Acceso autorizado**: Devuelve respuesta `200 OK` estructurada y registra invocación en `mcp_tool_invocations` con `correlation_id`.
2. **Sin autenticación**: Devuelve `401 Unauthorized` cuando el token falta o es inválido.
3. **Acceso denegado**: Devuelve `403 Forbidden` cuando el rol no cumple la autorización o la cuenta del usuario está suspendida.

### Paso 6: Delegación determinista y verificación
1. Ejecutar las pruebas específicas de MCP:
   ```bash
   php artisan test --filter=Mcp
   ```
2. Formatear el código PHP generado:
   ```bash
   vendor/bin/pint --dirty --format agent
   ```
3. Comprobar la coherencia del proyecto:
   ```bash
   python3 scripts/check-agent-context.py
   ```
