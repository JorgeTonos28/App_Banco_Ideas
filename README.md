# INNOVATEP Ideas 💡
### Banco Institucional de Ideas e Innovación para INFOTEP

**INNOVATEP Ideas** es una plataforma web moderna y colaborativa diseñada para capturar, descubrir, valorar y seguir la evolución de propuestas de innovación generadas por los colaboradores del **Instituto Nacional de Formación Técnico Profesional (INFOTEP)** de la República Dominicana.

La plataforma fomenta una cultura participativa bajo el principio:
> **“Capturar una idea debe ser más fácil que olvidarla.”**

---

## 🌟 Características Principales

### 1. Captura, Organización y Publicación Editorial
- **Captura privada y sin fricción**: Registro rápido con título, propuesta, oportunidad detectada, clasificación, etiquetas dinámicas y adjuntos (PDFs, imágenes o documentos). Una idea nueva se guarda como privada o borrador; nunca se publica directamente desde el formulario.
- **Flujo de trabajo privado independiente**: Las ideas no publicadas utilizan los estados `Capturada`, `En clarificación`, `Lista para actuar`, `En ejecución`, `Completada`, `En pausa`, `Descartada` y `Archivada`. Este flujo organiza el trabajo personal y no altera el ciclo comunitario.
- **Revisión editorial humana**: El autor solicita publicación y el equipo de innovación puede aprobar, solicitar cambios, rechazar o retirar la idea. Mientras la solicitud está pendiente, la idea continúa siendo privada.
- **Ciclo comunitario después de publicar**: Sólo las ideas aprobadas utilizan `Nueva`, `En revisión`, `Priorizada`, `En desarrollo`, `Implementada`, `Descartada` y `Archivada`.
- **Publicación por idea madre**: Sólo la raíz de una jerarquía genera una tarjeta en Comunidad. Las subideas aprobadas se muestran dentro de esa raíz y no compiten como publicaciones independientes.
- **Jerarquías multinivel auditadas**: Una idea puede contener subideas en varios niveles. El backend evita ciclos, registra cada cambio de madre y protege las raíces que representan descendientes publicados.
- **Grafo de relaciones semánticas**: Las ideas pueden conectarse como dependencias, habilitadoras, complementarias, derivadas, evoluciones, duplicados, sustituciones o relaciones generales. Las propuestas entre autores requieren confirmación.
- **Taxonomía multidimensional**: Además de la categoría temática principal, el administrador define dimensiones de selección única o múltiple, obligatorias u opcionales, planas o jerárquicas. La migración conserva `category_id` y crea las asociaciones multidimensionales sin perder compatibilidad.
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

### 2. Descubrimiento y Colaboración
- **Explorador con Filtros Multifactor**: Búsqueda global, filtros por dimensiones, categorías, etiquetas, estados, autor, área o departamento, y ordenamiento por más votadas, recientes, en tendencia, más comentadas o implementadas.
- **Navegación estructural**: “Mis Ideas” dispone de vista de árbol y tarjetas; cada ficha muestra madre, subideas, clasificaciones y relaciones verificadas.
- **Votación Comunitaria (1 a 5 Estrellas)**: Sistema de valoración interactivo donde los colaboradores califican el impacto potencial (con restricción de auto-voto para el autor).
- **Conversación y Retroalimentación**: Hilos de comentarios anidados con respuestas y sistema de "Me gusta".
- **Guardar en Favoritas**: Marcadores personales para seguimiento rápido.

### 3. Innovation Score & Leaderboard
- **Algoritmo de Innovation Score (0-100)**: Fórmula ponderada que combina promedio de estrellas, volumen de votos, interacción comunitaria (vistas y comentarios) y vigencia temporal.
- **Podio Top 3**: Reconocimiento visual con medallas de oro (🥇), plata (🥈) y bronce (🥉).
- **Ranking Dinámico**: Filtros por periodos (*Esta semana*, *Este mes*, *Este año*, *Histórico*) y áreas.

### 4. Gestión Personal, Seguridad y 2FA
- **Mis Ideas (Módulo Inicial)**: Espacio principal de trabajo donde cada colaborador gestiona ideas privadas, árboles de subideas, solicitudes editoriales, publicaciones, implementaciones y favoritas.
- **Comunidad (Feed de Innovación)**: Ecosistema colaborativo que muestra sólo ideas principales aprobadas, junto con la cantidad de subideas integradas.
- **Edición de Perfil y Ajustador de Imagen Canvas**: Indicadores de dimensiones recomendadas (**400 × 400 px, 1:1**) con herramienta interactiva en Canvas para zoom, desplazamiento (pan/drag), rotación y encuadre antes de guardar.
- **Cambio de Contraseña Segura**: Formulario dedicado en `/mi-perfil/seguridad` con verificación en tiempo real de políticas de robustez (mínimo 8 caracteres, mayúsculas, minúsculas y números).
- **Autenticación en Dos Pasos (2FA)**:
  - **App Autenticadora (TOTP/QR)**: Compatibilidad con Google Authenticator, Microsoft Authenticator y Authy mediante escaneo de código QR y generación de claves de respaldo.
  - **Código por Correo (Email OTP)**: Códigos temporales de 6 dígitos enviados al correo institucional con expiración y rate limiting.
  - **Desafío en Inicio de Sesión**: Protección reforzada al autenticarse en la plataforma.
  - **Códigos de Recuperación**: El desafío de acceso permite utilizar códigos de emergencia generados al activar 2FA.
- **Onboarding e Invitaciones**: Activación de cuenta por correo con verificación y establecimiento personalizado de contraseña.

### 5. Panel Administrativo (Panel de Innovación)
- **Dashboard Estadístico**: Métricas de ideas recibidas, en revisión, tasa de implementación, distribución por categoría y departamentos más activos.
- **Gestión Editorial y de Ciclo de Vida**: Tabla interactiva con filtro de publicación y panel lateral para aprobar ideas principales, representar subideas, solicitar cambios, asignar responsables, fijar prioridad, registrar observaciones y programar seguimiento.
- **Control Integral de Usuarios y Onboarding**:
  - Modo 1: **Invitación por Correo**: Despacho de enlace de onboarding seguro con expiración de 72 horas para autoconfiguración del colaborador.
  - Modo 2: **Contraseña Temporal**: Creación directa forzando al usuario a cambiar su contraseña de manera obligatoria en su primer inicio de sesión.
  - **Regla de Protección de Administrador Único**: Bloqueo estricto para impedir eliminar, desactivar o degradar al último administrador general activo.
- **Gestión Configurable de Regionales INFOTEP**:
  - Mantenimiento completo de direcciones regionales (`ONA - Oficina Nacional`, `DRM - Regional Metropolitana`, `DRO`, `DRV`, `DRCS`, `DRE`, `DRCN`, `DRCNE`, `DRS`).
- **Taxonomía Multidimensional Administrable**:
  - Dimensiones configurables con modo único o múltiple, obligatoriedad, activación y orden.
  - Términos controlados con icono, color, jerarquía padre-hijo y protección cuando están en uso.
  - Facetas navegables en el explorador y clasificación integrada en creación y edición.
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
- PHP >= 8.2 con extensiones habilitadas (`pdo_sqlite`, `mbstring`, `openssl`, `fileinfo`, `gd` o `imagick`).
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

### Migración de la taxonomía en producción

`php artisan migrate --force` crea automáticamente la dimensión principal **Área de innovación**, asigna las categorías existentes a esa dimensión y migra cada `ideas.category_id` a la tabla multidimensional `idea_category`. No es necesario modificar previamente los registros actuales.

Las dimensiones y términos adicionales pueden gestionarse desde **Administración → Categorías**. La reorganización inicial de las ideas existentes mediante comandos de Tinker debe prepararse después de revisar el inventario real de producción, para que jerarquías, dimensiones, relaciones y etiquetas se apliquen con identificadores correctos y de forma idempotente.

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
   - `IdeaPolicy`: Restringe visualización, edición, eliminación, organización y solicitudes de publicación según autoría, rol y estado.
   - `IdeaRelationPolicy`: Sólo el autor objetivo o un administrador puede confirmar relaciones propuestas entre autores; sólo el origen o un administrador puede eliminarlas.
   - `AdminMiddleware`: Protección estricta en todas las rutas administrativas `/admin/*`.
   - Control de auto-voto: Un creador no puede calificar su propia propuesta.
3. **Validación y Sanitización**: Uso estricto de `FormRequest` con validación de tipo, longitud, formato, pertenencia taxonómica, ciclos jerárquicos y acceso a ideas relacionadas.
4. **Subida Segura de Archivos**: Validación de tipos MIME permitidos (`pdf,jpg,jpeg,png,webp,doc,docx,xls,xlsx,zip`), límite de 10 MB y almacenamiento con nombres hash únicos.
5. **Rate Limiting**: Throttling en login (`6/min`), solicitudes editoriales (`5/min`), organización y relaciones (`30/min`), votación (`30/min`) y comentarios (`15/min`).
6. **Protección contra SQL Injection**: Consultas exclusivamente a través de Eloquent ORM con sentencias preparadas.

---

## 📄 Licencia

Desarrollado para el ecosistema de innovación institucional de **INFOTEP**. Todos los derechos reservados.
