# INNOVATEP · Centro de Innovación 💡
### Ideas, tareas y colaboración institucional para INFOTEP

**INNOVATEP** evoluciona hacia un Centro de Innovación modular. El **Banco de Ideas** permite capturar, descubrir, valorar y seguir propuestas; el módulo de **Tareas** convierte esas propuestas —o necesidades sueltas— en acciones ejecutables y colaborativas.

La plataforma fomenta una cultura participativa bajo el principio:
> **“Capturar una idea debe ser más fácil que olvidarla.”**

---

## 🌟 Características Principales

### 1. Captura, Organización y Publicación Editorial
- **Captura personal y sin fricción**: Registro rápido con título, propuesta, oportunidad detectada, clasificación, etiquetas dinámicas y adjuntos (PDFs, imágenes o documentos). El autor decide por separado si el contenido está completo o sigue como borrador y quién puede consultarlo.
- **Acceso independiente de la publicación**: Una idea completa puede quedar como `Sólo yo`, `Visible en mi perfil` o compartirse en una comunidad interna. El acceso interno se vincula a una unidad organizacional concreta y puede limitarse a ese nivel o incluir sus niveles dependientes; no activa el ciclo oficial ni incorpora la idea a la comunidad general.
- **Borradores siempre restringidos**: Un borrador no puede compartirse desde el perfil hasta marcarse como idea completa.
- **Flujo de trabajo privado independiente**: Las ideas no publicadas utilizan los estados `Capturada`, `En clarificación`, `Lista para actuar`, `En ejecución`, `Completada`, `En pausa`, `Descartada` y `Archivada`. Este flujo organiza el trabajo personal y no altera el ciclo comunitario.
- **Revisión editorial humana**: El autor solicita publicación y el equipo de innovación puede aprobar, solicitar cambios, rechazar o revertir una publicación general. Mientras la solicitud está pendiente, la idea conserva el acceso elegido por el autor. Al revertir una idea, también se retiran sus descendientes publicados y cada nodo recupera su audiencia contextual previa; si esa audiencia ya no existe, queda en `Sólo yo`. El formulario administrativo inicia en `Sin decisión` y la representación comunitaria se deriva de la jerarquía: sólo las raíces crean tarjetas y las microideas se muestran dentro de su madre.
- **Ciclo comunitario después de publicar**: Sólo las ideas aprobadas utilizan `Nueva`, `En revisión`, `Priorizada`, `En desarrollo`, `Implementada`, `Descartada` y `Archivada`.
- **Exposición por idea madre**: Sólo la raíz de una jerarquía genera una tarjeta tanto en el perfil como en Comunidad. Las microideas se recorren mediante la trazabilidad multinivel dentro de la madre y no compiten como publicaciones independientes.
- **Jerarquías multinivel auditadas y privadas por defecto**: El backend evita ciclos, registra cada cambio de madre y protege las raíces que representan descendientes publicados. Una microidea conserva su audiencia propia aunque su madre sea pública, nunca supera el acceso efectivo de sus ancestros y sólo llega a la comunidad general mediante su propia aprobación editorial.
- **Comunidades organizacionales jerárquicas**: Regionales o sedes, direcciones funcionales y departamentos forman un árbol navegable. Las ideas internas declaran audiencias exactas o con descendientes, mientras la comunidad general de INFOTEP continúa reservada para publicaciones aprobadas editorialmente.
- **Grafo de relaciones semánticas con auditoría humana**: La creación y edición incluyen un selector jerárquico con pestañas `Mis ideas` e `Ideas de otros`, agrupadas por autor. El editor incorpora sugerencias de IA, permite ajustar tipo o justificación y quitar relaciones antes de guardar. Admite dependencias, habilitadoras, complementarias, derivadas, evoluciones, duplicados, sustituciones o relaciones generales. Sólo aparecen ideas autorizadas por `IdeaPolicy`; una conexión entre autores queda pendiente y requiere confirmación del propietario destino.
- **Taxonomía multidimensional**: Además de la categoría temática principal, el administrador define dimensiones de selección única o múltiple, obligatorias u opcionales, planas o jerárquicas. La migración conserva `category_id` y crea las asociaciones multidimensionales sin perder compatibilidad.
- **Guía contextual de clasificación**: Los formularios de creación y edición incluyen una ayuda desplegable que diferencia categoría, dimensiones y etiquetas, recomienda entre 4 y 7 etiquetas y muestra ejemplos de términos útiles o duplicados que deben evitarse.
- **Explorador Modal de Etiquetas (Tag Explorer)**: Catálogo visual e interactivo para examinar todas las etiquetas registradas en el sistema, organizadas en 4 pestañas:
  - 🔤 **Alfabético (A - Z)**: Agrupadas por letra con selector de salto rápido.
  - 🏷️ **Por Categoría Oficial**: Clasificación de etiquetas vinculadas a cada categoría institucional.
  - 🔥 **Más Populares**: Ranking de etiquetas con mayor frecuencia de uso en ideas.
  - 💡 **Recomendadas para tu idea**: Detección dinámica y semántica de etiquetas afines según el texto redactado y la categoría seleccionada.
- **Entrada Rápida y Múltiple (Separación por Comas)**: Soporte para ingresar múltiples etiquetas de una sola vez separadas por comas (ej.: `IA, Robótica, Talleres`), creando y agregando automáticamente los chips correspondientes tanto al escribir como al pegar texto.
- **Edición In-situ de Etiquetas**: Capacidad de editar, renombrar o corregir cualquier etiqueta ya agregada a una idea directamente desde su chip interactivo (haciendo clic en el ícono de edición ✏️ o doble clic), con guardado instantáneo y revalidación de similitud.
- **Motor Inteligente de Detección de Etiquetas Similares y Duplicadas**:
  - 🛡️ **Prevención Canónica de Duplicados**: Unificación automática de términos sin distinción de mayúsculas/minúsculas o tildes (ej.: `automatizacion` se asocia canónicamente con `Automatización`).
  - ⚡ **Detección de Similares en Tiempo Real (Fuzzy & Lematización en Español)**: Identifica variaciones de singular/plural (`Sensor` ↔ `Sensores`, `Capacitación` ↔ `Capacitaciones`) y errores tipográficos (`Inteligencia Artifical` ↔ `Inteligencia Artificial`).
  - 💡 **Asistente Visual Interactivo**: Notifica al usuario en el formulario y en el explorador modal cuando existen etiquetas similares con conteo de uso en el sistema, permitiendo seleccionarlas con un solo clic o confirmar el término nuevo.
- **Buscador en Tiempo Real y Creación Rápida**: Filtrado instantáneo de etiquetas y opción de registrar nuevos términos con un solo clic.
- **Línea de tiempo por flujo**: Historial separado para trabajo privado, revisión editorial y ciclo comunitario, con actor y observaciones.
- **Captura por voz con revisión humana**: El formulario graba hasta cinco minutos, transcribe el archivo temporal y permite editarlo antes del análisis. La IA distingue `idea`, `task` o `uncertain`: una idea plantea una oportunidad o solución con valor propio; una tarea es una acción ejecutable con condición clara de terminado. Si parece tarea, propone una idea o tarea superior permitida y ofrece trasladar el borrador al módulo de Tareas. La persona confirma el destino; nada se crea, relaciona ni mueve automáticamente.
- **Contexto autorizado para IA**: Las madres candidatas siguen limitadas a ideas válidas del autor. Las relaciones semánticas pueden considerar ideas de otros usuarios, pero exclusivamente dentro del conjunto visible según Policies. Archivadas y descartadas se excluyen de madres y relaciones. La taxonomía, etiquetas, ideas y tareas se envían como allowlists; cualquier ID ajeno al contexto se rechaza nuevamente en Laravel.

### 2. Módulo de Tareas
- **Tareas sueltas o vinculadas a ideas**: Cada tarea puede existir de forma independiente o contribuir a una idea. Una tarea puede contener subtareas en tantos niveles como sean necesarios.
- **Ejecución tipo To Do**: Estados `Pendiente`, `En progreso`, `Completada` y `Cancelada`, prioridad, responsable, fecha y hora de vencimiento, vistas de hoy, próximas, sin fecha, colaborativas y completadas.
- **Recordatorios por correo o navegador**: Los recordatorios se guardan por canal. El programador de Laravel procesa los vencidos cada minuto; el canal navegador muestra avisos nativos mientras INNOVATEP está abierto y tiene permiso.
- **Adjuntos protegidos**: Cada tarea o subtarea admite hasta cinco archivos por envío y 10 MB por archivo. Se almacenan en el disco privado con nombres UUID y sólo se descargan mediante una ruta autorizada.
- **Colaboración comunitaria controlada**: El autor de una idea decide si permite colaboración. Las tareas abiertas reciben solicitudes; el responsable acepta o rechaza y una aprobación asigna la tarea a la persona voluntaria.

### 3. Descubrimiento y Colaboración
- **Búsqueda respetuosa del contexto**: La búsqueda global ignora espacios y acentos, consulta contenido, clasificación, etiquetas y autor, e incluye ideas propias privadas, publicaciones generales, perfiles visibles y comunidades internas autorizadas; la Policy de cada idea continúa siendo la autoridad final para evitar fugas entre audiencias.
- **Explorador con Filtros Multifactor**: “Explorar Ideas” filtra automáticamente mientras se escribe, abre por defecto en `Todas las ideas` y permite ordenar explícitamente por más votadas, recientes, en tendencia, mejor valoradas, más comentadas o implementadas. También añade filtros por dimensiones, categorías, etiquetas, estados, autor, área o departamento.
- **Navegación estructural y estados ocultables**: “Mis Ideas”, la trazabilidad de cada ficha y el selector de madre usan árboles multinivel. Completadas, archivadas y descartadas quedan ocultas por defecto; cada idea madre tiene un filtro propio para mostrarlas. Al mostrarse usan borde verde, amarillo o rojo. Las preferencias se recuerdan localmente por rama.
- **Cierre coherente de ramas**: Al cambiar una madre a `Completada`, `Archivada` o `Descartada`, el estado privado se propaga transaccionalmente a sus descendientes y cada nodo registra el cambio heredado en su historial.
- **Exportación jerárquica**: La ficha de cualquier idea madre exporta su árbol visible. Los títulos son obligatorios y se pueden añadir descripciones, problemas u oportunidades, etiquetas, categorías y relaciones semánticas, como documento compatible con Word o JSON.
- **Creación contextual de microideas**: Los nodos propios y la ficha de una idea muestran una acción para agregar una hija. La madre se asigna o cambia únicamente desde el formulario de creación o edición, cuyo selector jerárquico sólo contiene ideas del usuario y excluye descendientes inválidos.
- **Valoración preliminar y comunitaria (1 a 5 estrellas)**: Una idea madre visible en el perfil puede recibir votos antes de publicarse, con actualización inmediata del promedio y sin autovoto. Las microideas nunca reciben votos independientes.
- **Continuidad de las valoraciones**: Los votos preliminares se conservan al publicar la idea, pero no activan Innovation Score ni participación en rankings antes de la aprobación editorial.
- **Conversación y Retroalimentación**: Hilos de comentarios anidados con respuestas y sistema de "Me gusta".
- **Guardar en Favoritas**: Marcadores personales para seguimiento rápido.

### 4. Innovation Score & Leaderboard
- **Algoritmo de Innovation Score (0-100)**: Fórmula ponderada que combina promedio de estrellas, volumen de votos, interacción comunitaria (vistas y comentarios) y vigencia temporal. Sólo se activa para ideas madre aprobadas en Comunidad.
- **Podio Top 3**: Reconocimiento visual con medallas de oro (🥇), plata (🥈) y bronce (🥉).
- **Ranking Dinámico**: Filtros por periodos (*Esta semana*, *Este mes*, *Este año*, *Histórico*) y áreas.

### 5. Gestión Personal, Seguridad y 2FA
- **Banco de Ideas**: Módulo donde cada colaborador gestiona su espacio personal, comunidades internas, borradores, árboles de microideas, solicitudes editoriales, publicaciones, implementaciones y favoritas.
- **Comunidades por nivel**: Al entrar en Comunidad, cada colaborador llega a su unidad más específica y puede subir hacia dirección, regional/sede y comunidad general, o bajar a niveles dependientes cuando corresponda. Cada nivel tiene búsqueda automática limitada a su propio contexto; las comunidades internas muestran sólo ideas madre habilitadas para su audiencia y la comunidad general mantiene exclusivamente publicaciones aprobadas.
- **Edición de Perfil y Ajustador de Imagen Canvas**: Indicadores de dimensiones recomendadas (**400 × 400 px, 1:1**) con herramienta interactiva en Canvas para zoom, desplazamiento (pan/drag), rotación y encuadre antes de guardar.
- **Cambio de Contraseña Segura**: Formulario dedicado en `/mi-perfil/seguridad` con verificación en tiempo real de políticas de robustez (mínimo 8 caracteres, mayúsculas, minúsculas y números).
- **Autenticación en Dos Pasos (2FA)**:
  - **App Autenticadora (TOTP/QR)**: Compatibilidad con Google Authenticator, Microsoft Authenticator y Authy mediante escaneo de código QR y generación de claves de respaldo.
  - **Código por Correo (Email OTP)**: Códigos temporales de 6 dígitos enviados al correo institucional con expiración y rate limiting.
  - **Desafío en Inicio de Sesión**: Protección reforzada al autenticarse en la plataforma.
  - **Códigos de Recuperación**: El desafío de acceso permite utilizar códigos de emergencia generados al activar 2FA.
- **Onboarding e Invitaciones**: Activación de cuenta por correo con verificación y establecimiento personalizado de contraseña.

### 6. Panel Administrativo (Panel de Innovación)
- **Dashboard Estadístico**: Métricas de ideas recibidas, en revisión, tasa de implementación, distribución por categoría y departamentos más activos.
- **Gestión Editorial y de Ciclo de Vida**: La administración comienza con una lista del primer nivel de ideas madre y permite avanzar o retroceder un nivel por vez, con una acción separada para abrir cada ficha. La búsqueda filtra automáticamente el nivel actual; el panel lateral permite decidir publicaciones sin alterar la jerarquía, asignar responsables, fijar prioridad, registrar observaciones y programar seguimiento.
- **Control Integral de Usuarios y Onboarding**:
  - Modo 1: **Invitación por Correo**: Despacho de enlace de onboarding seguro con expiración de 72 horas para autoconfiguración del colaborador.
  - Modo 2: **Contraseña Temporal**: Creación directa forzando al usuario a cambiar su contraseña de manera obligatoria en su primer inicio de sesión.
  - **Regla de Protección de Administrador Único**: Bloqueo estricto para impedir eliminar, desactivar o degradar al último administrador general activo.
- **Gestión de estructura organizacional**:
  - Árbol administrable de regionales o sedes, direcciones funcionales y departamentos, con validación de niveles superiores, prevención de ciclos y bloqueo de eliminación cuando existen miembros o dependencias.
  - El directorio, las invitaciones y el onboarding asignan a cada colaborador su unidad más específica y conservan automáticamente la regional raíz para compatibilidad.
  - Cuando una invitación ya tiene unidad organizacional, el colaborador la ve como dato informativo y el servidor impide sustituirla durante la activación.
  - La unidad organizacional se muestra en el perfil, pero sólo puede ser modificada desde Administración para evitar ampliaciones de acceso no autorizadas.
- **Taxonomía Multidimensional Administrable**:
  - Dimensiones configurables con modo único o múltiple, obligatoriedad, activación y orden.
  - Términos controlados con icono, color, jerarquía padre-hijo y protección cuando están en uso.
  - Facetas navegables en el explorador y clasificación integrada en creación y edición.
  - Guía de gobernanza visible en la administración para decidir cuándo crear dimensiones, términos controlados o etiquetas reutilizables.
- **Control Inteligente de Etiquetas**:
  - 🔍 **Buscador Dinámico en Tiempo Real**: Filtrado reactivo instantáneo de etiquetas por nombre y conteo de ideas mientras se escribe.
  - ✏️ **Edición y Corrección de Nombres**: Modificación de términos con normalización de caracteres y slugs.
  - ⚡ **Fusión Inteligente Automática**: Si al corregir una etiqueta se asigna el nombre de una etiqueta ya existente, el sistema unifica automáticamente ambas etiquetas reasignando de manera segura todas las ideas vinculadas y eliminando la duplicada.
  - 🔀 **Fusión Manual Asistida**: Selector visual para transferir propuestas entre descriptores y consolidar el catálogo.

---

## 🛠️ Stack Tecnológico

- **Backend**: [Laravel 13.x](https://laravel.com) / PHP 8.3+
- **Base de Datos**: SQLite (por defecto para desarrollo inmediato) / MySQL / PostgreSQL
- **Frontend**: Blade Components + [Tailwind CSS v4](https://tailwindcss.com) + [Alpine.js](https://alpinejs.dev)
- **Tipografía**: Google Fonts (*Hanken Grotesk*, *Inter*, *JetBrains Mono*)
- **Iconografía**: Google Material Symbols Outlined
- **Build Tool**: [Vite](https://vitejs.dev)

---

## 🚀 Instalación y Despliegue Local

### Requisitos Previos
- PHP >= 8.3 con extensiones habilitadas (`pdo_sqlite`, `mbstring`, `openssl`, `fileinfo`, `gd` o `imagick`).
- Composer >= 2.x
- Node.js >= 18.x & npm

### Pasos de Instalación

1. **Clonar el repositorio**:
   ```bash
   git clone https://github.com/JorgeTonos28/App_Banco_Ideas.git
   cd App_Banco_Ideas
   ```

2. **Instalar dependencias de PHP**:
   ```bash
   composer install
   ```

3. **Configurar variables de entorno**:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Ejecutar migraciones y datos de prueba (Seeders)**:
   ```bash
   touch database/database.sqlite
   php artisan migrate:fresh --seed
   php artisan storage:link
   ```

5. **Instalar y compilar assets frontend**:
   ```bash
   npm install
   npm run build
   ```
   *(Para desarrollo en vivo con Hot Reload: `npm run dev`)*

6. **Iniciar el servidor local**:
   ```bash
   php artisan serve
   ```
   Visita la aplicación en: `http://localhost:8000`

---

## 🚀 Despliegue en cPanel bajo `/banco`

Para publicar la aplicación en `https://apps.innovatep.com/banco` sin exponer el código fuente, mantén Laravel fuera del Document Root:

```text
/home/innovatep/Portal_Apps/banco/                 # Aplicación Laravel privada
/home/innovatep/apps.innovatep.com/                # Document Root del subdominio
/home/innovatep/apps.innovatep.com/banco/          # Solo contenido de public/
```

El `.env` debe permanecer en la aplicación privada y utilizar:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://apps.innovatep.com/banco
SESSION_PATH=/banco
```

Para enviar invitaciones desde una cuenta de correo de cPanel mediante SMTP seguro:

```env
MAIL_MAILER=smtp
MAIL_SCHEME=smtps
MAIL_HOST=mail.innovatep.com
MAIL_PORT=465
MAIL_USERNAME=info@innovatep.com
MAIL_PASSWORD="CONTRASEÑA_DE_LA_CUENTA_DE_CORREO"
MAIL_FROM_ADDRESS=info@innovatep.com
MAIL_FROM_NAME="INNOVATEP — INFOTEP"
```

En Laravel 13 se utiliza `MAIL_SCHEME`; no es necesario configurar `MAIL_ENCRYPTION`. Después de cambiar el `.env`, limpia la configuración con `php artisan optimize:clear`. Las invitaciones se envían de forma síncrona, por lo que este flujo no requiere un worker de colas adicional en cPanel.

El `index.php` público debe cargar `vendor/autoload.php` y `bootstrap/app.php` desde la ruta privada, y configurar esa carpeta pública mediante `$app->usePublicPath(__DIR__)`. El enlace `storage` público debe apuntar a `storage/app/public` de la aplicación privada.

El `.htaccess` de la raíz del subdominio puede redirigir `https://apps.innovatep.com/` a `https://innovatep.com/`; el `.htaccess` dentro de `/banco` debe conservar el front controller de Laravel. La ruta raíz de la aplicación redirige visitantes a `/banco/login` y usuarios autenticados a `/banco/mis-ideas`.

En producción utiliza `php artisan migrate --force`; no ejecutes `migrate:fresh --seed`, porque el seeder contiene cuentas de demostración.

Los recordatorios de tareas requieren el scheduler. Configura una tarea cron por minuto (ajusta la ruta privada):

```cron
* * * * * cd /home/innovatep/Portal_Apps/banco && php artisan schedule:run >> /dev/null 2>&1
```

Después de migrar, un administrador configura la clave cifrada, modelos y funciones desde **Administración → Inteligencia artificial**. `OPENAI_API_KEY` funciona como fallback de despliegue, pero la interfaz administrativa es la opción recomendada. La prueba de conexión consulta únicamente el modelo de transcripción configurado y no envía contenido de ideas.

### Migración de la taxonomía en producción

`php artisan migrate --force` crea automáticamente la dimensión principal **Área de innovación**, asigna las categorías existentes a esa dimensión y migra cada `ideas.category_id` a la tabla multidimensional `idea_category`. No es necesario modificar previamente los registros actuales.

La migración de acceso agrega `ideas.access_scope` sin convertir ideas privadas en visibles. Las publicaciones existentes se inicializan con acceso de perfil para que, si luego se retiran de Comunidad, continúen siendo localizables desde el perfil de su autor; el autor puede cambiar posteriormente ese acceso a `Sólo yo`.

La migración de reversión editorial agrega `ideas.pre_publication_access_scope`. Para publicaciones ya existentes toma como punto de restauración su `access_scope` actual; en solicitudes nuevas captura automáticamente la audiencia vigente antes de que el equipo de innovación publique la idea.

La migración de comunidades organizacionales amplía `regionals` con `parent_id` y `type`, agrega la unidad organizacional más específica a usuarios e invitaciones, y crea `idea_community_shares`. Los usuarios existentes conservan su regional como unidad inicial. Cada audiencia interna registra si aplica sólo a la unidad seleccionada o también a sus descendientes.

Las dimensiones y términos adicionales pueden gestionarse desde **Administración → Categorías**. La reorganización inicial de las ideas existentes mediante comandos de Tinker debe prepararse después de revisar el inventario real de producción, para que jerarquías, dimensiones, relaciones y etiquetas se apliquen con identificadores correctos y de forma idempotente.

La migración del Centro de Innovación crea `tasks`, `task_attachments`, `task_reminders`, `task_volunteers` y `task_status_histories`, además de `ideas.allow_task_collaboration`. No transforma ideas existentes ni abre colaboración automáticamente.

### Reglas recomendadas de clasificación

- Cada idea debe tener una categoría temática principal y completar todas las dimensiones obligatorias.
- Una dimensión nueva debe responder una pregunta estable, aplicar a la mayoría de las ideas y aportar un filtro útil para navegar.
- Un término controlado debería servir, como referencia, para cinco ideas actuales o previstas. Las excepciones deben tener valor claro de recuperación o navegación.
- Cada idea debería usar entre 4 y 7 etiquetas concretas, combinando ecosistema, capacidades, tecnología o método y, cuando aporte, audiencia o resultado.
- Las etiquetas deben escribirse como sustantivos breves, preferiblemente de 2 a 4 palabras, con una forma canónica y las siglas en mayúsculas.
- Antes de crear una etiqueta se deben revisar coincidencias y términos similares. Estados, visibilidad, publicación y jerarquía no deben duplicarse como categorías o etiquetas.

---

## 🤖 Asistente de captura y organización con IA

La Fase 0 del asistente de captura por voz y organización de ideas está documentada en [`docs/ai`](docs/ai/README.md). Incluye el playbook de clasificación, recomendaciones de taxonomía, el informe de inconsistencias y scripts reproducibles para validar exportaciones, el Gold Standard y los casos de evaluación.

El Gold Standard y los casos derivados de producción se almacenan exclusivamente en `storage/app/private/ai-audit/`, una ruta excluida de Git. No deben publicarse ni moverse a una carpeta versionada sin revisión institucional de privacidad.

```bash
node scripts/ai/audit-planning-context.mjs /ruta/ai-planning-context.json
node scripts/ai/validate-ai-audit-artifacts.mjs /ruta/ai-planning-context.json storage/app/private/ai-audit/AI_GOLD_STANDARD_V1.json storage/app/private/ai-audit/AI_EVAL_CASES_V1.json
```

El primer vertical está implementado con una abstracción de proveedores y adaptadores OpenAI:

- `gpt-transcribe` para archivos de audio completos;
- `gpt-5.6-luna` con razonamiento `low` para organización y relaciones;
- escalamiento determinista a `gpt-5.6-terra` con razonamiento `medium` cuando la confianza no supera el umbral configurado o la primera salida no pasa la validación semántica;
- Responses API con JSON Schema estricto y `store: false`;
- claves cifradas mediante `APP_KEY`, URLs de proveedor no editables y modelos limitados por configuración versionada;
- telemetría de modelo, versión de prompt, latencia, unidades, éxito y escalamiento, sin audio, transcripción, prompt ni contenido generado.

El audio permanece en el archivo temporal administrado por PHP durante la solicitud y no se copia a ningún disco de Laravel. Los endpoints usan CSRF, autenticación, FormRequests, límite de 10 MB y throttling independiente.

### Aplicar las decisiones de datos aprobadas

El comando siguiente verifica los IDs, títulos y autoría esperados y muestra una vista previa sin escribir:

```bash
php artisan ideas:apply-ai-audit-decisions
```

Después de revisar la salida contra la base correcta, la aplicación explícita e idempotente se realiza con:

```bash
php artisan ideas:apply-ai-audit-decisions --apply
```

Este comando reactiva la categoría 12, convierte la idea 17 en raíz complementaria de la idea 10, conserva la idea 20 bajo la idea 10 y archiva los registros de prueba 30 y 31. No debe ejecutarse en producción sin respaldo y verificación administrativa previa.

### Verificación local

```bash
composer install
npm ci
npm run build
npm run test:js
php artisan test
```

El `composer.lock` está resuelto para PHP 8.3 y mantiene Symfony 7.4; evita regenerarlo con una plataforma PHP distinta sin comprobar compatibilidad.

---

## 🔐 Cuentas de Demostración Preconfiguradas

| Rol | Correo Electrónico | Contraseña | Cargo / Departamento |
|---|---|---|---|
| **Administrador** | `admin@infotep.gob.do` | `password123` | Director de Innovación y Desarrollo |
| **Docente / Usuario** | `maria.gonzalez@infotep.gob.do` | `password123` | Coordinadora de Formación Virtual |
| **Instructor Técnico** | `luis.morales@infotep.gob.do` | `password123` | Instructor Industrial (Regional Norte) |
| **Especialista Procesos**| `laura.jimenez@infotep.gob.do` | `password123` | Logística y Operaciones (Regional Este) |
| **Docente TI** | `francisco.reyes@infotep.gob.do` | `password123` | Tecnologías de la Información (Regional Sur) |

---

## 🛡️ Seguridad Implementada

1. **Protección CSRF**: Tokens obligatorios en todas las peticiones POST, PUT y DELETE.
2. **Políticas de Autorización (Policies & Gates)**:
   - `IdeaPolicy`: Restringe visualización, edición, eliminación, organización, publicación y votación según autoría, rol, acceso efectivo, ancestros y estado. Las microideas no son votables.
   - `IdeaRelationPolicy`: Sólo el autor objetivo o un administrador puede confirmar relaciones propuestas entre autores; sólo el origen o un administrador puede eliminarlas.
   - `TaskPolicy`: Separa lectura, edición, cambio de estado, adjuntos, voluntariado y aprobación. Una tarea abierta nunca evita la Policy de su idea vinculada.
   - `AdminMiddleware`: Protección estricta en todas las rutas administrativas `/admin/*`.
   - Control de auto-voto: Un creador no puede calificar su propia propuesta.
3. **Validación y Sanitización**: Uso estricto de `FormRequest` con validación de tipo, longitud, formato, pertenencia taxonómica, ciclos jerárquicos y acceso a ideas relacionadas.
4. **Subida Segura de Archivos**: Validación de tipos MIME permitidos, límite de 10 MB y nombres aleatorios. Los adjuntos de tareas usan almacenamiento privado y descarga autorizada con `X-Content-Type-Options: nosniff`.
5. **Rate Limiting**: Throttling en login (`6/min`), solicitudes editoriales (`5/min`), organización y relaciones manuales (`30/min`), transcripción de IA (`10/min`), análisis de IA (`20/min`), votación (`30/min`) y comentarios (`15/min`).
6. **Protección contra SQL Injection**: Consultas exclusivamente a través de Eloquent ORM con sentencias preparadas.
7. **Controles de IA**: Audio máximo de 10 MB, credenciales cifradas, proveedores/modelos en allowlists, contexto limitado por Policies, prompts que tratan el texto como dato no confiable, salidas estructuradas validadas de nuevo en servidor y ausencia de mutaciones hasta la confirmación humana.

---

## 📄 Licencia

Desarrollado para el ecosistema de innovación institucional de **INFOTEP**. Todos los derechos reservados.
