# Guía para Agentes de Inteligencia Artificial (AGENTS.md)
### Proyecto: INNOVATEP Ideas (Banco de Ideas INFOTEP)

Este documento contiene las reglas, directrices de ingeniería, arquitectura y protocolos obligatorios que **todo agente de IA debe cumplir rigurosamente** al trabajar en esta base de código.

---

## 🚨 REGLAS OBLIGATORIAS E INNEGOCIABLES

### 1. Regla de Mantenimiento y Actualización del README
> **En cada cambio que haga un agente, si la modificación agrega, altera o elimina funcionalidades, comandos, dependencias, rutas, roles o configuraciones relevantes para el `README.md`, el agente TIENE LA OBLIGACIÓN de modificar y mantener actualizado el archivo `README.md` inmediatamente.**

### 2. Regla de Seguridad Universal
> **En cada modificación, nuevo feature o ajuste técnico, se deben tomar en cuenta TODOS los aspectos de seguridad necesarios sin excepción:**
> - **Protección CSRF**: Toda petición que mute estado (POST, PUT, DELETE, PATCH) debe incluir `@csrf` o el header `X-CSRF-TOKEN`.
> - **Autorización estricta (Policies & Gates)**: Nunca confíes únicamente en la interfaz visual. Toda acción en controladores debe validarse mediante `$this->authorize(...)` o Policies dedicadas (`IdeaPolicy`, `CommentPolicy`, `AdminMiddleware`).
> - **Validación rigurosa**: Usar clases `FormRequest` dedicadas para validar tipos de datos, tamaños, rangos y pertenencia a tablas.
> - **Prevención de XSS y SQLi**: Utilizar escape nativo de Blade `{{ $variable }}`, sanitizar texto enriquecido y utilizar siempre Eloquent ORM con consultas parametrizadas.
> - **Subida Segura de Archivos**: Validar estrictamente extensiones y tipos MIME, limitar tamaño máximo (10 MB por defecto) y almacenar en disco con nombres aleatorios/hasheados únicos.
> - **Rate Limiting (Throttle)**: Aplicar límites de frecuencia en rutas críticas como login, votación, creación de ideas y comentarios.

### 3. Regla de Flujo de Trabajo en Git y Ramas (Branching)
> **Para cada requerimiento o cambio solicitado por el usuario:**
> 1. Crear una **rama nueva** descriptiva (ej.: `git checkout -b feature/nombre-del-cambio` o `fix/descripcion-del-arreglo`).
> 2. Realizar las modificaciones y pruebas en el entorno local.
> 3. Solicitar y esperar el **visto bueno explícito del usuario** tras su verificación en local.
> 4. Tras la aprobación, subir la rama a GitHub, hacer el **merge a `main`** y sincronizar el repositorio remoto.
> 5. **PROHIBIDO**: Bajo ninguna circunstancia se debe versionar o subir la carpeta `Prototipo/` a GitHub. Debe mantenerse en el `.gitignore`.

---

## 🏛️ Arquitectura del Sistema

### 1. Estructura de Directorios Clave
```
app/
├── Http/
│   ├── Controllers/
│   │   ├── Admin/               # Controladores del panel de innovación
│   │   ├── AuthController.php   # Login, logout y autenticación
│   │   ├── HomeController.php   # Feed y dashboard principal
│   │   ├── IdeaController.php   # CRUD, votación (1-5★) y favoritas
│   │   ├── CommentController.php# Comentarios, respuestas y likes
│   │   ├── RankingController.php# Leaderboard e Innovation Score
│   │   ├── MyIdeasController.php# Gestión personal de ideas y borradores
│   │   ├── ProfileController.php# Perfil de usuario e insignias
│   │   ├── NotificationController.php
│   │   └── SearchController.php # Búsqueda global instantánea
│   ├── Middleware/
│   │   └── AdminMiddleware.php  # Verificación de rol admin
│   └── Requests/                # FormRequests con validaciones dedicadas
├── Models/
│   ├── User.php                 # Modelo de colaborador y badges
│   ├── Category.php             # Categorías con colores e íconos
│   ├── Tag.php                  # Etiquetas y palabras clave
│   ├── Idea.php                 # Entidad central con cálculo de score
│   ├── IdeaAttachment.php       # Archivos adjuntos y evidencias
│   ├── IdeaRating.php           # Votaciones de 1 a 5 estrellas
│   ├── IdeaComment.php          # Comentarios y árbol de respuestas
│   ├── IdeaCommentLike.php      # Likes en comentarios
│   ├── IdeaFavorite.php         # Ideas guardadas por usuario
│   └── IdeaStatusHistory.php    # Bitácora de evolución del ciclo de vida
└── Policies/
    ├── IdeaPolicy.php           # Permisos de edición, borrado y auto-voto
    └── CommentPolicy.php
resources/
├── css/
│   └── app.css                  # Tokens del sistema Kinetic Institutional
├── js/
│   └── app.js                   # Alpine.js y componentes reactivos
└── views/                       # Vistas Blade semánticas y responsive
```

---

## 🎨 Sistema de Diseño (Kinetic Institutional)

El diseño visual está fundamentado en la paleta institucional de INNOVATEP / INFOTEP reinterpretada con estética moderna de producto SaaS digital:

- **Colores Primarios**:
  - `primary`: `#003e6f` (Azul Institucional Profundo)
  - `primary-container`: `#005696` (Azul Digital)
  - `secondary-container`: `#feb700` (Ámbar de Acción / Destacados)
  - `tertiary`: `#231fb5` (Azul Tecnológico)
  - `surface`: `#f8f9ff` (Fondo Limpio y Aireado)
  - `surface-container-lowest`: `#ffffff` (Tarjetas y Contenedores)
- **Tipografías**:
  - Encabezados: `'Hanken Grotesk'` (`font-headline`)
  - Cuerpo / Textos: `'Inter'` (`font-sans`)
  - Metadatos / Badges / Scores: `'JetBrains Mono'` (`font-mono-tech`)
- **Iconos**: Google Material Symbols Outlined (`<span class="material-symbols-outlined">...</span>`).

---

## ⚡ Algoritmo de Innovation Score

El `InnovationScore` (0 a 100) de cada idea se recalcula en `Idea::recalculateRatingAndScore()` considerando:
1. **Calificación Media (0 a 40 pts)**: Promedio de estrellas obtenido (1 a 5).
2. **Volumen de Votos (0 a 30 pts)**: Cantidad total de colaboradores que han votado.
3. **Interacción Comunitaria (0 a 15 pts)**: Número de comentarios y visualizaciones.
4. **Vigencia y Frescura (0 a 15 pts)**: Bonificación por dinamismo temporal reciente.

---

## 📋 Checklist Obligatorio para el Agente antes de Concluir una Tarea

- [ ] ¿Se creó y trabajó en una rama separada de Git?
- [ ] ¿Se aplicaron todas las validaciones de seguridad en backend y frontend?
- [ ] ¿Se respetó el principio de no auto-voto y control de roles?
- [ ] ¿La carpeta `Prototipo/` se mantuvo excluida de Git?
- [ ] ¿Se ejecutó `npm run build` si se modificaron archivos Blade, CSS o JS?
- [ ] ¿Se actualizó el `README.md` si el cambio introduce nuevas características o altera instrucciones existentes?
- [ ] ¿Se esperó la aprobación del usuario antes de mergear a `main`?
