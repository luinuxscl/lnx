---
name: verify-delivery
description: Procedimiento de verificación de entrega y cierre de ciclo en lnx combinando pruebas funcionales, verificación visual y evidencia.
---

# Verificar una entrega (verify-delivery)

Este procedimiento establece el flujo de verificación de calidad, evidencia y cierre de ciclo para cualquier entrega de código en `lnx`, garantizando que todas las pruebas automatizadas pasen, la interfaz cumpla con el estándar visual e i18n, la documentación esté al día y los criterios de aceptación se hayan verificado antes de integrar o cerrar un issue.

## Cuándo usar
- Al finalizar el desarrollo de un feature o corrección de error, antes de entregar el PR o solicitar la aprobación del usuario.
- Antes de marcar como resuelto o cerrar un issue de GitHub asociado.

## Cuándo NO usar
- Durante las fases iniciales o intermedias de codificación.
- En lugar del procedimiento de construcción de pantallas (usar [design-screen](file:///home/luinux/Code/lnx/.ai/skills/design-screen/SKILL.md) durante la implementación de la UI).
- En lugar del procedimiento de diseño de arquitectura (usar [implement-feature](file:///home/luinux/Code/lnx/.ai/skills/implement-feature/SKILL.md) o [expose-mcp-operation](file:///home/luinux/Code/lnx/.ai/skills/expose-mcp-operation/SKILL.md)).

## Requisitos y entradas
1. **Cambios completados**: Código implementado en la rama correspondiente (`feature/*` o `fix/*`).
2. **Issue o contrato de entrega**: Lista de criterios de aceptación (verificables mediante `gh issue view {id}`).
3. **Entorno local listo**: Dependencias instaladas y base de datos de prueba migrada.

## Resultado esperado
- Suite de pruebas automatizadas en verde sin fallos ni omisiones.
- Formateo de código PHP conforme a las reglas del proyecto (`pint`).
- Validación del contexto de agentes y referencias de documentación sin enlaces ni referencias rotas.
- Compilación de assets exitosa (`npm run build`) si se modificó el frontend.
- Verificación visual bilingüe (es/en), en modos claro/oscuro y viewports móvil/desktop cuando afecte a la interfaz.
- Documentación del proyecto ([README.md](file:///home/luinux/Code/lnx/README.md) y [.ai/rules](file:///home/luinux/Code/lnx/.ai/rules/index.md)) sincronizada en la misma entrega.
- Evidencia clara registrada en la descripción del PR.

## Límites de autorización y seguridad
- No fusionar (`merge`) automáticamente a `main` o `develop` sin revisión previa ni aprobación.
- No asumir que un merge cierra automáticamente un issue de GitHub: comprobar explícitamente los criterios de aceptación y verificar que el issue figure como `CLOSED` tras completar la entrega.
- No agregar checks innecesarios ni pruebas redundantes que no aporten valor al alcance verificado.

---

## Procedimiento paso a paso

### Paso 1: Ejecutar verificaciones automatizadas deterministas
Correr la secuencia de comandos de verificación del proyecto en orden:

1. **Suite de pruebas de comportamiento**:
   ```bash
   php artisan test --compact
   ```
2. **Formateador de código PHP**:
   ```bash
   vendor/bin/pint --dirty --format agent
   ```
3. **Verificación de referencias y contexto de agentes**:
   ```bash
   python3 scripts/check-agent-context.py
   ```
4. **Compilación de assets de producción** (si hubo cambios en vistas, componentes o CSS/JS):
   ```bash
   npm run build
   ```
5. **Sincronización de skills en el entorno agéntico**:
   ```bash
   php artisan boost:update --no-discover --no-interaction
   ```

### Paso 2: Verificación visual y bilingüe (si afectó a la interfaz)
Si la entrega incluye elementos de UI, seguir las pautas de [design-screen](file:///home/luinux/Code/lnx/.ai/skills/design-screen/SKILL.md):
1. **Idiomas**: Probar en Español (idioma por defecto) e Inglés. Verificar que no existan cadenas sin traducir o sin envolver en `__()`.
2. **Temas y Modos**: Verificar la pantalla en modo claro (`light`), oscuro (`dark`) y distintos colores de acento configurados.
3. **Responsividad y Foco**: Probar la vista en pantalla de escritorio y en viewport móvil acotado, así como la navegación por teclado (`focus`).

### Paso 3: Verificación de documentación
1. Comprobar que cualquier cambio de comportamiento, nuevo comando, nueva ruta o nueva configuración esté documentado en [README.md](file:///home/luinux/Code/lnx/README.md) en el mismo commit.
2. Si se crearon o modificaron reglas del proyecto, verificar su registro en [.ai/rules/index.md](file:///home/luinux/Code/lnx/.ai/rules/index.md).

### Paso 4: Evidencia y Cierre del ciclo
1. Revisar cada criterio de aceptación del issue correspondiente (`gh issue view {id}`).
2. Adjuntar las evidencias concretas de las verificaciones (logs de tests, capturas si aplican) en la descripción del PR.
3. Confirmar que los issues asociados queden cerrados (`CLOSED`) al integrar los cambios.
