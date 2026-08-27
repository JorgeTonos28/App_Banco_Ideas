# AI Taxonomy Recommendations V1

## Resumen ejecutivo

La taxonomía es utilizable para un piloto, pero todavía induce a clasificar como `Tecnología` casi cualquier propuesta soportada por software. La dimensión principal debe representar el área beneficiada; la dimensión `Tipo de iniciativa` ya expresa si el entregable es un producto o funcionalidad digital.

La exportación no contiene errores referenciales: las 36 ideas tienen las tres dimensiones obligatorias, no usan categorías inactivas y todos los IDs existen. Los problemas son principalmente semánticos: categorías heredadas duplicadas, una dimensión de alcance que mezcla conceptos diferentes y etiquetas redundantes o demasiado genéricas.

Fuente: exportación `ai-planning-context-v1`, 25 de agosto de 2026. Las cifras `ideas_count_systemwide` y `classified_ideas_count_systemwide` son agregados del sistema; el contenido auditado está limitado a un propietario.

## 1. Perfil observado

| Elemento | Resultado |
|---|---:|
| Ideas auditadas | 36 |
| Ideas raíz / subideas | 11 / 25 |
| Dimensiones | 3 |
| Categorías activas / inactivas | 17 / 11 |
| Etiquetas del sistema | 168 |
| Etiquetas sin uso declarado | 45 |
| Relaciones internas | 14 |
| Ideas sin problema u oportunidad | 27 |
| Ideas con menos de 4 etiquetas | 2 |
| Ideas con más de 7 etiquetas | 2 |

## 2. Correcciones inequívocas

Estas acciones no cambian el modelo conceptual y pueden prepararse para revisión administrativa.

### 2.1 Unificar etiquetas canónicamente equivalentes

| Conservar | Fusionar o retirar | Evidencia |
|---|---|---|
| `63 · Agentes Autónomos` | `12 · Agente autónomo` | Singular/plural; la segunda no tiene uso. |
| `144 · Eventos` | `147 · Evento` | Singular/plural; ambas tienen un uso. |
| `76 · Inteligencia Artificial Generativa` | `28 · IA Generativa` | Mismo concepto; la forma abreviada no tiene uso. |
| `60 · Seguimiento de Proyectos` | `5 · Seguimiento proyectos` | Diferencia sólo gramatical. |
| `145 · Aplicación Web` | `167 · App` | `App` es una forma genérica del mismo artefacto. |

Las etiquetas `8 · Nuevas ideas` y `9 · Nueva idea` no deberían fusionarse: ambas son genéricas, no tienen uso y conviene retirarlas del catálogo sugerible.

### 2.2 Retirar etiquetas de acción genérica del sistema de sugerencias

No deben eliminarse sin revisar historial, pero deben quedar fuera de las sugerencias automáticas:

- `155 · Cambiar`;
- `156 · Mover`;
- `162 · Mejorar`;
- `163 · Robustecer`;
- `34 · Agrupar`;
- `44 · Proyectos`;
- `3 · Innovación`.

Estas palabras describen acciones demasiado amplias y reducen la precisión de búsqueda.

### 2.3 Corregir etiquetas no respaldadas por el contenido

- Idea 27: sustituir colaboración, participación e historial de contribuciones por jerarquía, microideas, trazabilidad y experiencia de usuario.
- Idea 28: reducir de 12 a 7 etiquetas; retirar `Huawei` del estándar salvo que la alianza con ese proveedor sea parte obligatoria del alcance.
- Idea 33: retirar `Inteligencia Artificial`, `Gestión de Tareas`, `Gestión del Conocimiento` y `Auditoría` porque la descripción no las establece.
- Idea 34: retirar `Mejorar` y `Robustecer`; son verbos, no conceptos de recuperación.
- Idea 36: sustituir `App` por `Aplicación Web` y `Links` por un concepto específico como `QR dinámico`.

## 3. Categorías heredadas

Las siguientes categorías inactivas tienen equivalentes activos claros y deben conservarse sólo como alias histórico mientras existan referencias antiguas:

| Inactiva | Canónica activa |
|---|---|
| `2 · Tecnología e Inteligencia Artificial` | `25 · Tecnología` |
| `3 · Formación y Metodología Docente` | `24 · Formación` |
| `4 · Procesos y Simplificación Administrativa` | `23 · Procesos` |
| `7 · Sostenibilidad y Medio Ambiente` | `26 · Sostenibilidad` |
| `8 · Servicios Empresariales y Comunitarios` | `27 · Servicios` |
| `9 · Infraestructura, Talleres y Laboratorios` | `28 · Infraestructura` |

`1 · Indefinido` debe permanecer inactiva y nunca incluirse en el contexto de IA.

Las categorías inactivas `10`, `11`, `12` y `13` no son simples duplicados y requieren decisión:

- `10 · Innovación Curricular y Carreras 4.0`: puede ser una subcategoría de Formación.
- `11 · Emprendimiento y Transferencia Tecnológica`: puede ser una subcategoría de Servicios o un área activa independiente.
- `12 · Comunicación y Marca Institucional`: conviene reactivarla; las ideas 11 y 36 no encajan bien en `Servicios` o `Procesos`.
- `13 · Inclusión y Accesibilidad`: es transversal. Encaja mejor como dimensión o etiqueta controlada que como área principal exclusiva.

## 4. Recomendaciones que requieren decisión humana

### 4.1 Reactivar Comunicación y Marca Institucional

Recomendación: reactivar `12 · Comunicación y Marca Institucional` antes de cerrar el Gold Standard productivo.

Casos beneficiados:

- idea 11, portal público de innovación;
- idea 36, generador institucional de códigos QR para Mercadeo.

Mientras siga inactiva, el fallback válido es `27 · Servicios` para la idea 11 y `23 · Procesos` para la idea 36. La IA no debe devolver el ID 12 hasta que la configuración activa enviada por Laravel lo permita.

### 4.2 Usar realmente la jerarquía del área principal

`Área de innovación` está marcada como jerárquica, pero las 28 categorías exportadas son nodos raíz y tienen `path` vacío. El piloto puede operar con una lista plana, pero no debe evaluarse capacidad de clasificación jerárquica hasta que existan subcategorías reales.

Primeras subcategorías candidatas:

- Tecnología → Seguridad de la información;
- Tecnología → Inteligencia artificial y automatización;
- Procesos → Gobernanza y flujo editorial;
- Procesos → Gestión del conocimiento;
- Formación → Investigación y prospectiva;
- Comunicación y Marca → Canales y productos digitales.

No crearlas automáticamente desde resultados del modelo. Deben aprobarse en administración y entrar después al Gold Standard.

### 4.3 Separar alcance de audiencia o renombrar la dimensión

`Alcance organizacional` mezcla tres clases de concepto:

- extensión institucional (`Institucional`);
- unidad territorial (`Regional o centro`);
- límite de iniciativa (`Programa o proyecto`);
- audiencia (`Público externo`).

Opción mínima recomendada para V1: renombrarla a `Ámbito de aplicación` y documentar que admite múltiples valores.

Opción más limpia para una V2:

1. `Cobertura organizacional`: institucional, dirección/departamento, regional/centro, programa/proyecto.
2. `Audiencia`: interna, participantes, empresas, ciudadanía/público externo.

No se recomienda hacer esta división durante el primer vertical de IA porque cambiaría formularios, migraciones y casos de evaluación.

### 4.4 Ampliar Tipo de iniciativa sólo después del piloto

Los cinco valores actuales cubren la exportación, pero fuerzan futuros casos de infraestructura o lineamientos dentro de `Programa o iniciativa`.

Candidatas para una fase posterior:

- mejora de proceso;
- política o lineamiento;
- infraestructura o equipamiento.

La evidencia actual no justifica agregarlas antes de evaluar más autores y áreas.

## 5. Reclasificación recomendada por idea

`Actual → Recomendada`. Cuando no se indica cambio, la selección actual se considera válida.

| Idea | Área principal | Tipo | Alcance | Confianza / observación |
|---:|---|---|---|---|
| 1 | Servicios | Programa o iniciativa | Programa/proyecto; Regional/centro | Alta |
| 2 | Tecnología | Producto digital | Programa/proyecto | Alta |
| 3 | Tecnología | Funcionalidad | Institucional | Alta |
| 4 | Tecnología | Investigación aplicada | Institucional | Alta |
| 5 | Tecnología | Funcionalidad | Institucional | Alta |
| 6 | Tecnología → Procesos | Funcionalidad | Institucional | Media; el beneficio es organizar la clasificación. |
| 7 | Tecnología → Procesos | Funcionalidad | Institucional; Programa/proyecto; Regional/centro | Alta |
| 8 | Tecnología → Procesos | Funcionalidad | Institucional | Alta |
| 9 | Procesos | Funcionalidad | Institucional | Alta |
| 10 | Tecnología → Procesos | Producto digital | Institucional | Media; el producto digital soporta gestión de innovación. |
| 11 | Servicios → Comunicación y Marca | Producto digital | Institucional; Público externo | Alta si se reactiva categoría 12. |
| 12 | Procesos → Experiencia del Colaborador | Funcionalidad | Institucional | Media; prioriza colaboración sobre flujo. |
| 13 | Tecnología | Funcionalidad | Institucional | Alta |
| 14 | Tecnología → Experiencia del Colaborador | Funcionalidad | Institucional | Alta |
| 15 | Procesos | Funcionalidad | Institucional | Alta |
| 16 | Experiencia del Colaborador | Funcionalidad | Institucional | Alta |
| 17 | Procesos | Funcionalidad → Producto digital | Institucional | Alta si se confirma que también admite tareas independientes. |
| 18 | Formación | Investigación aplicada | Institucional; Público externo | Alta |
| 19 | Infraestructura | Programa o iniciativa | Institucional | Alta |
| 20 | Servicios | Funcionalidad → Producto digital | Institucional; Público externo | Media; es una evolución de producto con gobierno propio. |
| 21 | Formación | Actividad formativa | Programa/proyecto | Alta |
| 22 | Tecnología | Funcionalidad | Institucional; Público externo | Alta |
| 23 | Procesos → Experiencia del Colaborador | Funcionalidad | Institucional | Alta |
| 24 | Procesos | Funcionalidad | Institucional | Alta |
| 25 | Experiencia del Participante | Producto digital | Programa/proyecto | Alta |
| 26 | Tecnología → Procesos | Funcionalidad | Institucional | Alta |
| 27 | Experiencia del Colaborador | Funcionalidad | Institucional; retirar Programa/proyecto | Alta |
| 28 | Formación | Actividad formativa | Institucional; Regional/centro | Media; falta definir audiencia y propósito. |
| 29 | Experiencia del Colaborador → Formación | Actividad formativa | Institucional | Media |
| 30 | Sin recomendación | Sin recomendación | Sin recomendación | Alta: registro de prueba, debe quedar borrador. |
| 31 | Sin recomendación | Sin recomendación | Sin recomendación | Alta: registro de prueba, debe quedar borrador. |
| 32 | Experiencia del Colaborador | Producto digital → Funcionalidad | Institucional | Alta |
| 33 | Procesos | Producto digital | Institucional; Programa/proyecto | Alta |
| 34 | Experiencia del Colaborador → Tecnología | Producto digital → Funcionalidad | Institucional | Alta |
| 35 | Experiencia del Colaborador | Producto digital → Funcionalidad | Institucional | Alta |
| 36 | Procesos → Comunicación y Marca | Producto digital | Institucional | Alta si se reactiva categoría 12. |

## 6. Política operativa recomendada

Antes de enviar la taxonomía a un proveedor:

1. incluir sólo dimensiones y categorías activas;
2. incluir descripciones, cardinalidad y rutas completas;
3. excluir categorías legacy inactivas;
4. incluir un mapa de alias sólo en el validador determinista, no como opciones sugeribles;
5. limitar etiquetas candidatas a las usadas, administrativamente aprobadas o recuperadas por similitud;
6. aplicar una lista de etiquetas no sugeribles para términos genéricos;
7. exigir revisión humana para cualquier etiqueta nueva o recomendación que requiera activar una categoría.

## 7. Decisiones humanas aprobadas

El 25 de agosto de 2026 se aprobó reactivar la categoría 12, tratar la idea 17 como producto raíz complementario de la idea 10, conservar la idea 20 bajo la idea 10 y archivar los registros de prueba 30 y 31 fuera del corpus positivo.

Fuera de esas cuatro decisiones editoriales, `summary` conserva por compatibilidad técnica el comportamiento actual: se deriva de `description` y no se incorpora como campo editable independiente. Esto no se presenta como una quinta decisión aprobada.
