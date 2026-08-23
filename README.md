# INNOVATEP Ideas 💡
### Banco Institucional de Ideas e Innovación para INFOTEP

**INNOVATEP Ideas** es una plataforma web moderna y colaborativa diseñada para capturar, descubrir, valorar y seguir la evolución de propuestas de innovación generadas por los colaboradores del **Instituto Nacional de Formación Técnico Profesional (INFOTEP)** de la República Dominicana.

La plataforma fomenta una cultura participativa bajo el principio:
> **“Capturar una idea debe ser más fácil que olvidarla.”**

---

## 🌟 Características Principales

### 1. Captura y Gestión de Ideas
- **Publicación ágil y sin fricción**: Registro rápido con título, propuesta, oportunidad detectada, categoría, etiquetas dinámicas y adjuntos (PDFs, imágenes o documentos).
- **Explorador Modal de Etiquetas (Tag Explorer)**: Catálogo visual e interactivo para examinar todas las etiquetas registradas en el sistema, organizadas en 4 pestañas:
  - 🔤 **Alfabético (A - Z)**: Agrupadas por letra con selector de salto rápido.
  - 🏷️ **Por Categoría Oficial**: Clasificación de etiquetas vinculadas a cada categoría institucional.
  - 🔥 **Más Populares**: Ranking de etiquetas con mayor frecuencia de uso en ideas.
  - 💡 **Recomendadas para tu idea**: Detección dinámica y semántica de etiquetas afines según el texto redactado y la categoría seleccionada.
- **Entrada Rápida y Múltiple (Separación por Comas)**: Soporte para ingresar múltiples etiquetas de una sola vez separadas por comas (ej.: `IA, Robótica, Talleres`), creando y agregando automáticamente los chips correspondientes tanto al escribir como al pegar texto.
- **Motor Inteligente de Detección de Etiquetas Similares y Duplicadas**:
  - 🛡️ **Prevención Canónica de Duplicados**: Unificación automática de términos sin distinción de mayúsculas/minúsculas o tildes (ej.: `automatizacion` se asocia canónicamente con `Automatización`).
  - ⚡ **Detección de Similares en Tiempo Real (Fuzzy & Lematización en Español)**: Identifica variaciones de singular/plural (`Sensor` ↔ `Sensores`, `Capacitación` ↔ `Capacitaciones`) y errores tipográficos (`Inteligencia Artifical` ↔ `Inteligencia Artificial`).
  - 💡 **Asistente Visual Interactivo**: Notifica al usuario en el formulario y en el explorador modal cuando existen etiquetas similares con conteo de uso en el sistema, permitiendo seleccionarlas con un solo clic o confirmar el término nuevo.
- **Buscador en Tiempo Real y Creación Rápida**: Filtrado instantáneo de etiquetas y opción de registrar nuevos términos con un solo clic.
- **Borradores y Estados**: Capacidad de guardar borradores privados o publicar directamente.
- **Ciclo de Vida Evolutivo**: Seguimiento en tiempo real a través de las etapas:
  `💡 Nueva` → `👀 En revisión` → `⭐ Priorizada` → `🧪 En desarrollo` → `🚀 Implementada` (con opciones de `⛔ Descartada` y `📦 Archivada`).
- **Línea de tiempo**: Historial público con observaciones registradas por el equipo de innovación.

### 2. Descubrimiento y Colaboración
- **Explorador con Filtros Multifactor**: Búsqueda global, filtrado por categorías, etiquetas, estados, autor, área o departamento, y ordenamiento por más votadas, recientes, en tendencia, más comentadas o implementadas.
- **Votación Comunitaria (1 a 5 Estrellas)**: Sistema de valoración interactivo donde los colaboradores califican el impacto potencial (con restricción de auto-voto para el autor).
- **Conversación y Retroalimentación**: Hilos de comentarios anidados con respuestas y sistema de "Me gusta".
- **Guardar en Favoritas**: Marcadores personales para seguimiento rápido.

### 3. Innovation Score & Leaderboard
- **Algoritmo de Innovation Score (0-100)**: Fórmula ponderada que combina promedio de estrellas, volumen de votos, interacción comunitaria (vistas y comentarios) y vigencia temporal.
- **Podio Top 3**: Reconocimiento visual con medallas de oro (🥇), plata (🥈) y bronce (🥉).
- **Ranking Dinámico**: Filtros por periodos (*Esta semana*, *Este mes*, *Este año*, *Histórico*) y áreas.

### 4. Gestión Personal, Seguridad y 2FA
- **Mis Ideas (Módulo Inicial)**: Espacio principal de trabajo donde cada colaborador gestiona sus propuestas publicadas, borradores, ideas implementadas y favoritas desde el primer momento.
- **Comunidad (Feed de Innovación)**: Ecosistema colaborativo institucional con métricas en tiempo real, propuestas destacadas, tendencias y leaderboard.
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
- **Gestión Avanzada de Ideas**: Tabla interactiva con búsqueda, selección múltiple, cambio rápido de estado y panel lateral (*slide-over drawer*) para asignar responsables, fijar prioridad (`Baja`, `Media`, `Alta`, `Estratégica`), redactar observaciones internas y programar fechas de seguimiento.
- **Control Integral de Usuarios y Onboarding**:
  - Modo 1: **Invitación por Correo**: Despacho de enlace de onboarding seguro con expiración de 72 horas para autoconfiguración del colaborador.
  - Modo 2: **Contraseña Temporal**: Creación directa forzando al usuario a cambiar su contraseña de manera obligatoria en su primer inicio de sesión.
  - **Regla de Protección de Administrador Único**: Bloqueo estricto para impedir eliminar, desactivar o degradar al último administrador general activo.
- **Gestión Configurable de Regionales INFOTEP**:
  - Mantenimiento completo de direcciones regionales (`ONA - Oficina Nacional`, `DRM - Regional Metropolitana`, `DRO`, `DRV`, `DRCS`, `DRE`, `DRCN`, `DRCNE`, `DRS`).
  - Creación de nuevas sedes, edición de nombres/códigos y alternancia de estados (habilitar / inhabilitar).
- **Taxonomía**: Administración de categorías y fusión de etiquetas duplicadas.

---

## 🛠️ Stack Tecnológico

- **Backend**: [Laravel 11.x](https://laravel.com) / PHP 8.4+
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

### Poblar Categorías Oficiales en Producción (vía Tinker)

Para registrar la batería completa de categorías oficiales de innovación en un entorno de producción sin cargar datos de prueba, ejecuta `php artisan tinker` e ingresa el siguiente bloque:

```php
$categories = [
    ['name' => 'Tecnología e Inteligencia Artificial', 'icon' => 'memory', 'color' => '#231fb5', 'description' => 'Soluciones digitales, automatización, IA, ciberseguridad e infraestructura tecnológica.'],
    ['name' => 'Formación y Metodología Docente', 'icon' => 'school', 'color' => '#003e6f', 'description' => 'Metodologías docentes, currículo formativo, entornos híbridos y pedagogía técnico-profesional.'],
    ['name' => 'Procesos y Simplificación Administrativa', 'icon' => 'account_tree', 'color' => '#005696', 'description' => 'Optimización de flujos de trabajo, trámites internos, digitalización y eficiencia operativa.'],
    ['name' => 'Experiencia del Participante', 'icon' => 'group', 'color' => '#00838f', 'description' => 'Iniciativas para enriquecer el aprendizaje, acompañamiento, bienestar y servicios al estudiante.'],
    ['name' => 'Experiencia del Colaborador', 'icon' => 'sentiment_very_satisfied', 'color' => '#d81b60', 'description' => 'Desarrollo humano, bienestar institucional, cultura colaborativa y clima laboral.'],
    ['name' => 'Sostenibilidad y Medio Ambiente', 'icon' => 'eco', 'color' => '#2e7d32', 'description' => 'Eficiencia energética, campus verde, reducción de huella de carbono y reciclaje institucional.'],
    ['name' => 'Servicios Empresariales y Comunitarios', 'icon' => 'support_agent', 'color' => '#e65100', 'description' => 'Atención al sector empresarial, vinculación comunitaria, pasantías y consultoría técnica.'],
    ['name' => 'Infraestructura, Talleres y Laboratorios', 'icon' => 'apartment', 'color' => '#455a64', 'description' => 'Modernización de espacios físicos, equipamiento técnico, seguridad industrial y mantenimiento.'],
    ['name' => 'Innovación Curricular y Carreras 4.0', 'icon' => 'auto_stories', 'color' => '#6200ea', 'description' => 'Diseño de programas para industrias 4.0, microcredenciales y ocupaciones emergentes.'],
    ['name' => 'Emprendimiento y Transferencia Tecnológica', 'icon' => 'rocket_launch', 'color' => '#f57c00', 'description' => 'Incubación de proyectos, prototipado, ferias de innovación y patentes.'],
    ['name' => 'Comunicación y Marca Institucional', 'icon' => 'campaign', 'color' => '#c2185b', 'description' => 'Estrategias de difusión, visibilidad de logros, canales digitales y cultura de innovación.'],
    ['name' => 'Inclusión y Accesibilidad', 'icon' => 'accessibility_new', 'color' => '#00796b', 'description' => 'Programas inclusivos para personas con discapacidad, equidad y acceso universal.'],
];

foreach ($categories as $cat) {
    \App\Models\Category::firstOrCreate(
        ['name' => $cat['name']],
        [
            'slug' => \Illuminate\Support\Str::slug($cat['name']),
            'icon' => $cat['icon'],
            'color' => $cat['color'],
            'description' => $cat['description']
        ]
    );
}
echo "Categorías oficiales creadas exitosamente.\n";
```

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
   - `IdeaPolicy`: Los usuarios solo pueden modificar o eliminar sus ideas si están en estado borrador o nueva.
   - `AdminMiddleware`: Protección estricta en todas las rutas administrativas `/admin/*`.
   - Control de auto-voto: Un creador no puede calificar su propia propuesta.
3. **Validación y Sanitización**: Uso estricto de `FormRequest` con validación de tipo, longitud y formato.
4. **Subida Segura de Archivos**: Validación de tipos MIME permitidos (`pdf,jpg,jpeg,png,webp,doc,docx,xls,xlsx,zip`), límite de 10 MB y almacenamiento con nombres hash únicos.
5. **Rate Limiting**: Throttling en login (`6/min`), votación (`30/min`) y comentarios (`15/min`).
6. **Protección contra SQL Injection**: Consultas exclusivamente a través de Eloquent ORM con sentencias preparadas.

---

## 📄 Licencia

Desarrollado para el ecosistema de innovación institucional de **INFOTEP**. Todos los derechos reservados.
