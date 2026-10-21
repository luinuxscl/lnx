<?php

return [
    'groups' => [
        'getting_started' => 'Primeros pasos',
        'account' => 'Cuenta y acceso',
        'collaboration' => 'Usuarios y colaboración',
        'support' => 'Soporte',
        'ai' => 'IA y conocimiento',
        'operations' => 'Operación',
        'access' => 'Acceso',
        'packages' => 'Paquetes',
    ],

    'guides' => [
        'getting_started' => [
            'title' => 'Primeros pasos y navegación',
            'summary' => 'Conoce las zonas principales de lnx y cómo moverte entre ellas.',
            'body' => <<<'MD'
## La pantalla principal

Al iniciar sesión llegas al [Dashboard](/dashboard). El menú lateral concentra las funciones de la aplicación y el menú inferior reúne las herramientas de tu rol.

1. Usa el **menú principal** para volver al Dashboard.
2. Usa el **menú inferior** para abrir las herramientas de tu rol.
3. En escritorio, la barra superior muestra las migas de pan de la página actual.

## Ayuda y cuenta

- El botón de **Ayuda** de la cabecera abre este centro, las preguntas frecuentes y el soporte.
- El selector de idioma cambia la interfaz sin cerrar la sesión.
- El menú de usuario, al final del sidebar, lleva a ajustes y al cierre de sesión.

## Buenas prácticas

- Mantén tu perfil actualizado.
- Configura una passkey o 2FA antes de compartir tu cuenta.
- Consulta esta guía cuando un módulo nuevo aparezca en el menú.
MD,
        ],

        'dashboard' => [
            'title' => 'Dashboard',
            'summary' => 'Interpreta los indicadores y accesos rápidos de tu panel.',
            'body' => <<<'MD'
## Qué muestra el Dashboard

El [Dashboard](/dashboard) reúne indicadores de uso, actividad reciente y accesos directos. El contenido depende de tu rol: root y admin ven métricas de administración; el resto ve su propio resumen.

1. Revisa las tarjetas superiores para detectar cambios de uso.
2. Abre una tarjeta para ir a la sección relacionada.
3. Usa el menú lateral si necesitas una función que no aparece en los accesos rápidos.

## Si un indicador no coincide

Los datos se calculan con la zona horaria de tu perfil. Si el rango no es el esperado, revisa **Idioma, zona horaria y apariencia**.
MD,
        ],

        'profile_security' => [
            'title' => 'Perfil, seguridad, passkeys y 2FA',
            'summary' => 'Protege tu cuenta y mantén tus datos de contacto al día.',
            'body' => <<<'MD'
## Perfil

1. Abre [Perfil](/settings/profile).
2. Actualiza tu nombre, correo y foto.
3. Guarda los cambios y verifica el mensaje de confirmación.

## Seguridad

En [Seguridad](/settings/security) puedes cambiar la contraseña, revocar sesiones y gestionar la autenticación en dos pasos.

## Passkeys

1. Pulsa **Añadir passkey**.
2. Sigue las instrucciones del navegador o del sistema operativo.
3. Cierra sesión y prueba el acceso con la passkey antes de depender de ella.

## 2FA

1. Activa la autenticación en dos pasos.
2. Escanea el código con tu aplicación de autenticación.
3. Guarda los códigos de recuperación en un lugar seguro.

> Si pierdes el dispositivo, usa un código de recuperación o contacta a root para recuperar el acceso.
MD,
        ],

        'notifications' => [
            'title' => 'Notificaciones',
            'summary' => 'Revisa los avisos de la campana y márcalos como leídos.',
            'body' => <<<'MD'
## La campana

La campana de la cabecera muestra las notificaciones no leídas. Al abrirla puedes:

1. Revisar el mensaje y su enlace.
2. Abrir el recurso relacionado.
3. Marcar la notificación como leída.

La página [Notificaciones](/notifications) conserva el historial completo.

## Qué genera una notificación

- Cambios de rol y avisos administrativos.
- Respuestas y cambios de estado en tus solicitudes de soporte.
- Eventos de los módulos instalados que declaren su propio tipo de notificación.

Si una notificación no abre el recurso esperado, el módulo que la envía aún no declaró su enlace; avisa a root.
MD,
        ],

        'users_invitations' => [
            'title' => 'Usuarios e invitaciones',
            'summary' => 'Invita cuentas, asigna roles y gestiona el acceso del equipo.',
            'body' => <<<'MD'
## Invitar a una persona

1. Abre [Usuarios](/admin/users).
2. Pulsa **Invitar** y escribe el correo.
3. Envía la invitación y comparte el enlace si la persona no la recibe.

## Asignar roles

Los roles disponibles son **admin** y usuario estándar. El rol root no se asigna desde la interfaz.

1. Abre la ficha del usuario.
2. Selecciona el rol.
3. Guarda y confirma que el cambio aparece en la lista.

## Suspender y reactivar

Una cuenta suspendida conserva sus datos, pero no puede iniciar sesión. Reactívala desde la misma ficha.

> Solo root administra roles y suspensiones. Si eres admin, confirma con root antes de cambiar el acceso de otra persona.
MD,
        ],

        'feedback_support' => [
            'title' => 'Feedback y soporte',
            'summary' => 'Envía una solicitud, adjunta una captura y sigue su estado.',
            'body' => <<<'MD'
## Crear una solicitud

1. Abre el botón **Ayuda** de la cabecera y elige **Contactar soporte**.
2. Indica el tipo: problema, idea de mejora o pregunta.
3. Describe qué esperabas y qué ocurrió.
4. Adjunta una captura si ayuda a entender el caso.
5. Envía la solicitud.

El panel registra la pantalla, la versión y el contexto técnico para facilitar el diagnóstico. Nunca incluyas contraseñas ni datos sensibles.

## Seguir el estado

En [Mis solicitudes](/support?mine=1) ves tus tickets y su estado: recibido, en progreso, esperando tu respuesta, resuelto o cerrado. Cuando soporte responda, recibirás una notificación.

## Reabrir un caso

Si un caso resuelto sigue afectándote, ábrelo y usa **Reabrir solicitud** para explicar qué falta.
MD,
        ],

        'chat' => [
            'title' => 'Chat y asistente de texto',
            'summary' => 'Usa el chat para consultar el producto y redactar mejores solicitudes.',
            'body' => <<<'MD'
## Abrir el chat

1. Entra en [Chat](/chat).
2. Escribe tu pregunta y envíala.
3. Revisa la respuesta y las fuentes citadas cuando estén disponibles.

## Asistente de texto

El asistente puede mejorar la redacción de una solicitud de soporte sin cambiar el significado. Solo se envía el texto de la descripción; las imágenes nunca se envían a la IA.

1. Escribe el borrador en el panel de soporte.
2. Pulsa **Mejorar redacción con IA**.
3. Revisa la propuesta y acéptala o descártala.

## Límites

El chat y el asistente dependen de la configuración de IA de la instalación. Si aparecen deshabilitados, root debe revisar la configuración y la cuota.
MD,
        ],

        'knowledge' => [
            'title' => 'Knowledge y RAG',
            'summary' => 'Organiza manuales y documentos para respuestas con referencias.',
            'body' => <<<'MD'
## Subir conocimiento

1. Abre [Knowledge](/knowledge).
2. Pulsa **Añadir documento**.
3. Selecciona el archivo y espera la extracción.
4. Revisa el texto extraído y confirma el documento.

## Bases de conocimiento

Las bases agrupan documentos por audiencia. Usa [Bases](/knowledge/bases) para crear una base, activarla o desactivarla.

## Cómo se usa en el chat

El chat consulta las bases activas y cita los fragmentos recuperados. Si una respuesta no cita la fuente esperada:

1. Confirma que el documento está confirmado y activo.
2. Revisa que la base esté asociada a la conversación.
3. Vuelve a formular la pregunta con términos del documento.

> La extracción acepta PDF con texto seleccionable, TXT, MD, JSON y CSV, hasta 10 MB.
MD,
        ],

        'documents' => [
            'title' => 'Documentos',
            'summary' => 'Extrae datos de archivos y revisa el resultado antes de confirmarlo.',
            'body' => <<<'MD'
## Extraer un documento

1. Abre [Document AI](/documents).
2. Sube el archivo y espera el procesamiento.
3. Revisa los campos extraídos y corrige lo que haga falta.
4. Confirma el documento para conservar el resultado.

## Estados

- **Pendiente**: el archivo se recibió y espera procesamiento.
- **Procesando**: la extracción está en curso.
- **Requiere revisión**: hay campos dudosos.
- **Confirmado**: el resultado se revisó y se guardó.
- **Fallido**: el archivo no se pudo procesar.

Descarga el original desde el detalle si necesitas compararlo con la extracción.
MD,
        ],

        'emails' => [
            'title' => 'Correos enviados',
            'summary' => 'Revisa el historial de correos transaccionales y sus errores.',
            'body' => <<<'MD'
## Consultar el historial

1. Abre [Correos enviados](/admin/emails).
2. Filtra por estado o busca por destinatario o asunto.
3. Selecciona un correo para ver su contenido y el error, si falló.

## Buenas prácticas

- Un envío fallido no se reintenta automáticamente.
- Corrige la causa y vuelve a disparar la acción que genera el correo.
- No reenvíes correos con datos sensibles a destinatarios equivocados.

El historial es solo de lectura y está disponible para root.
MD,
        ],

        'packages' => [
            'title' => 'Paquetes',
            'summary' => 'Consulta versiones instaladas y actualiza los módulos de la app.',
            'body' => <<<'MD'
## Paquetes instalados

1. Abre [Paquetes](/admin/packages).
2. Revisa la versión instalada y la última disponible.
3. Copia el comando de actualización cuando haya una versión nueva.

## Actualizar

1. Haz una copia de seguridad o confirma que el despliegue la tiene.
2. Ejecuta el comando de actualización en el servidor.
3. Revisa migraciones, caché y registro de errores.
4. Verifica el paquete en esta página después del despliegue.

## Componentes UI

La página [UI/UX components](/admin/packages/lnx-ui) muestra la galería del paquete visual compartido y su versión.
MD,
        ],

        'activity' => [
            'title' => 'Actividad administrativa',
            'summary' => 'Audita cambios de usuarios, navegación y configuración.',
            'body' => <<<'MD'
## Revisar la actividad

1. Abre [Actividad](/admin/activity).
2. Filtra por actor, acción o rango de fechas.
3. Abre el detalle para ver los valores anteriores y nuevos.

## Para qué sirve

- Reconstruir quién cambió un rol o una configuración.
- Confirmar que una acción administrativa se completó.
- Investigar un acceso o cambio inesperado.

La actividad es inmutable desde la interfaz y solo root puede consultarla.
MD,
        ],

        'setup' => [
            'title' => 'Checklist de puesta en marcha',
            'summary' => 'Completa la configuración mínima antes de abrir la app.',
            'body' => <<<'MD'
## Completar el checklist

1. Abre [Checklist](/admin/setup).
2. Revisa cada punto pendiente.
3. Pulsa el enlace de la tarea para ir a la sección correspondiente.
4. Vuelve al checklist y confirma que el punto quedó completo.

## Puntos habituales

- Credenciales root de desarrollo reemplazadas.
- Correo saliente configurado.
- IA configurada o deshabilitada de forma consciente.
- Roles y primeras cuentas revisados.
- Navegación y paquetes comprobados.

El checklist no bloquea la aplicación: es una guía de operación para root.
MD,
        ],

        'preferences' => [
            'title' => 'Idioma, zona horaria y apariencia',
            'summary' => 'Ajusta la interfaz y el formato de fechas a tu forma de trabajo.',
            'body' => <<<'MD'
## Cambiar preferencias

1. Abre [Apariencia](/settings/appearance).
2. Elige idioma, zona horaria, tema y esquema de color.
3. Guarda los cambios.

Las preferencias se guardan por cuenta y se aplican en todos los dispositivos donde inicies sesión.

## Idioma

El selector de la cabecera cambia el idioma al instante. Si una traducción falta, la interfaz muestra la cadena fuente en inglés.

## Fechas y horas

Las fechas se muestran con tu zona horaria. Si un informe no coincide con tu hora local, revisa primero esta preferencia.
MD,
        ],

        'general_settings' => [
            'title' => 'Configuración general',
            'summary' => 'Define identidad, correo y valores compartidos de la instalación.',
            'body' => <<<'MD'
## Abrir la configuración

1. Entra en [Configuración general](/admin/settings).
2. Revisa la identidad del sitio, el correo saliente y las opciones compartidas.
3. Guarda y prueba el envío de un correo si cambiaste el transporte.

## Buenas prácticas

- Cambia una sola sección a la vez.
- Prueba el correo antes de anunciar el cambio.
- Revisa la [Actividad](/admin/activity) si un valor no se guardó como esperabas.

Esta configuración es exclusiva de root.
MD,
        ],

    ],

    'faq' => [
        'reset_password' => [
            'title' => '¿Cómo restablezco mi contraseña?',
            'summary' => 'Usa el enlace de recuperación desde el inicio de sesión.',
            'body' => 'En la pantalla de acceso pulsa **¿Olvidaste tu contraseña?**, escribe tu correo y abre el enlace que recibirás. El enlace caduca; si no llega, revisa spam o pide a root que confirme tu correo.',
        ],

        'change_email' => [
            'title' => '¿Cómo cambio mi correo?',
            'summary' => 'Actualiza el correo desde tu perfil y verifica el cambio.',
            'body' => 'Abre [Perfil](/settings/profile), escribe el nuevo correo y guarda. Si la verificación está activa, confirma el mensaje que llegará a la nueva dirección antes de cerrar sesión.',
        ],

        'passkeys' => [
            'title' => '¿Qué hago si pierdo mi passkey?',
            'summary' => 'Usa otro método de acceso o un código de recuperación.',
            'body' => 'Si conservas la sesión, elimina la passkey perdida en [Seguridad](/settings/security) y registra una nueva. Si no puedes entrar, usa 2FA o pide a root que revise tu acceso. Nunca compartas tus códigos de recuperación.',
        ],

        'two_factor' => [
            'title' => '¿Dónde guardo los códigos de 2FA?',
            'summary' => 'Guárdalos fuera del dispositivo que usa el segundo factor.',
            'body' => 'Descarga o copia los códigos de recuperación cuando actives 2FA y guárdalos en un gestor de contraseñas o en un lugar físico seguro. Cada código sirve una sola vez.',
        ],

        'language' => [
            'title' => '¿Cómo cambio el idioma o la zona horaria?',
            'summary' => 'Ajusta ambas preferencias desde Apariencia.',
            'body' => 'Usa el selector de idioma de la cabecera o abre [Apariencia](/settings/appearance). La zona horaria se guarda por cuenta y afecta a las fechas que ves en la aplicación.',
        ],

        'notifications' => [
            'title' => '¿Por qué no recibo notificaciones?',
            'summary' => 'Revisa la campana, la configuración del navegador y el tipo de evento.',
            'body' => 'Abre [Notificaciones](/notifications) para confirmar que el evento se registró. Las notificaciones de la app son internas: si esperabas un correo, revisa que el correo saliente esté configurado y que tu dirección sea correcta.',
        ],

        'contact_support' => [
            'title' => '¿Cómo contacto a soporte?',
            'summary' => 'Crea una solicitud desde el botón Ayuda.',
            'body' => 'Abre **Ayuda → Contactar soporte**, describe el problema y adjunta una captura si es útil. El panel añade automáticamente la pantalla, la versión y el contexto técnico para acelerar el diagnóstico.',
        ],

        'request_status' => [
            'title' => '¿Dónde veo el estado de mi solicitud?',
            'summary' => 'Consulta tus tickets en Mis solicitudes.',
            'body' => 'Entra en [Mis solicitudes](/support?mine=1). Cada ticket muestra su estado y la conversación. Cuando soporte responda o cambie el estado, recibirás una notificación.',
        ],

        'ai_disabled' => [
            'title' => '¿Por qué aparece la IA deshabilitada?',
            'summary' => 'La función depende de la configuración y la cuota de la instalación.',
            'body' => 'La IA se habilita desde la configuración de root. Si el chat o el asistente no responden, revisa con root que el proveedor esté configurado, que el modelo esté permitido y que la cuota no se haya agotado.',
        ],

        'ai_cost' => [
            'title' => '¿Cómo reviso el consumo de IA?',
            'summary' => 'Consulta los intentos, tokens y coste registrados.',
            'body' => 'Abre [Uso de IA](/admin/ai/usage). Cada intento registra modelo, tokens, coste, duración y estado. Filtra por fecha o usuario para detectar picos antes de que agoten la cuota.',
        ],

        'install_package' => [
            'title' => '¿Cómo instalo un paquete nuevo?',
            'summary' => 'Instala con Composer, revisa migraciones y comprueba la página de paquetes.',
            'body' => 'Instala el paquete con Composer en el servidor, ejecuta las migraciones y limpia la caché. El paquete aparece en [Paquetes](/admin/packages) solo si declara sus metadatos en el registro. Sigue el procedimiento de despliegue de la instalación.',
        ],

        'package_permissions' => [
            'title' => '¿Por qué un módulo no aparece en el menú?',
            'summary' => 'El paquete debe declarar su ruta, permisos y metadatos.',
            'body' => 'Un módulo no se publica solo por estar en `vendor/`. Debe declarar sus permisos, su entrada de menú y, si corresponde, su ayuda. Después de instalarlo, ejecuta el sincronizador de navegación y revisa [Navegación](/admin/navigation).',
        ],

        'privacy' => [
            'title' => '¿Qué datos se envían a la IA o al soporte?',
            'summary' => 'Solo se envía el texto necesario y nunca las imágenes a la IA.',
            'body' => 'El asistente de soporte envía únicamente la descripción que escribes; las imágenes se adjuntan al ticket y no se envían a la IA. El chat envía tu mensaje y el contexto de las bases de conocimiento asociadas. No incluyas contraseñas, tokens ni datos personales innecesarios.',
        ],

        'invite_user' => [
            'title' => '¿Cómo invito a una persona al equipo?',
            'summary' => 'Envía una invitación desde la administración de usuarios.',
            'body' => 'Abre [Usuarios](/admin/users), pulsa **Invitar**, escribe el correo y envía. La persona recibe un enlace para crear su cuenta. Si la invitación caduca, genera una nueva.',
        ],

        'suspend_user' => [
            'title' => '¿Qué ocurre al suspender una cuenta?',
            'summary' => 'La cuenta conserva sus datos, pero no puede iniciar sesión.',
            'body' => 'La suspensión revoca el acceso sin borrar datos ni historial. Puedes reactivar la cuenta desde su ficha. Revisa el motivo antes de suspender y registra el cambio en la conversación interna del equipo.',
        ],
    ],
];
