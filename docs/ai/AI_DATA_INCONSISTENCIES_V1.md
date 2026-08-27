# AI Data Inconsistencies V1

## Evaluación general: utilizable con correcciones y caveats

La exportación es estructuralmente consistente y sirve para diseñar el asistente, pero no debe utilizarse todavía como estándar de oro sin curación. No hay IDs huérfanos, ciclos, categorías inactivas seleccionadas ni dimensiones obligatorias ausentes. Los principales riesgos están en contenido incompleto, resúmenes derivados, etiquetas redundantes y relaciones semánticas demasiado amplias o redundantes.

Fuente evaluada: `ai-planning-context-v1`, exportada el 25 de agosto de 2026. Grano: una fila por idea del propietario exportado; taxonomía global y relaciones cuyos dos extremos están dentro del conjunto.

## 1. Comprobaciones realizadas

- recuentos declarados frente a arreglos reales;
- unicidad de IDs de ideas, dimensiones, categorías, etiquetas y relaciones;
- integridad madre-hija y detección de ciclos;
- consistencia de la proyección `children_idea_ids_inside_export`;
- existencia y actividad de categorías seleccionadas;
- cardinalidad y cobertura de dimensiones obligatorias;
- concordancia entre categoría principal y clasificaciones;
- existencia de etiquetas referenciadas;
- integridad de origen y destino de relaciones;
- autorrelaciones y pares duplicados;
- completitud de contenido;
- cantidad de etiquetas por idea;
- normalización básica de singular/plural en etiquetas.

Comando reproducible:

```powershell
node scripts/ai/audit-planning-context.mjs C:\ruta\ai-planning-context.json
```

## 2. Perfil

| Métrica | Valor |
|---|---:|
| Ideas | 36 |
| Raíces | 11 |
| Subideas | 25 |
| Dimensiones obligatorias | 3 |
| Categorías activas / inactivas | 17 / 11 |
| Etiquetas | 168 |
| Etiquetas sin uso declarado | 45 |
| Relaciones | 14 |

## 3. Hallazgos prioritarios

### [Alta] 27 de 36 ideas no tienen problema u oportunidad

IDs afectados: `3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 20, 22, 23, 24, 25, 26, 27, 28, 29, 30, 31`.

Impacto: una evaluación que trate esos registros como ideas completas enseñaría al modelo a completar propuestas sin verificar la necesidad que resuelven. También reduce la calidad del recuperador de madres y relaciones.

Confianza: alta; el campo está vacío. En muchos casos el problema se puede formular a partir de la descripción, pero esa formulación debe quedar como recomendación revisable, no como dato actual.

Remediación: el Gold Standard separa el campo actual de la propuesta recomendada y marca como `draft` los casos donde la laguna no puede resolverse sin inventar.

### [Alta] Los 36 resúmenes siguen el patrón de recorte de la descripción

La aplicación genera `summary` desde los primeros 160 caracteres de `description` en creación y edición. Por tanto, el campo no constituye una señal independiente de calidad editorial.

Impacto: duplicar `summary` y `description` en el prompt consume contexto sin añadir evidencia y puede hacer que una coincidencia textual parezca más fuerte de lo que es.

Confianza: alta; los 36 registros coinciden con el prefijo normalizado de su descripción y el código actual sobrescribe el resumen.

Remediación: enviar un solo campo textual al recuperador o ponderar el resumen como derivado. Antes de implementar la UI, decidir si el resumen pasará a ser editable o seguirá calculado.

### [Alta] Dos registros son pruebas, no ideas clasificables

IDs: `30` y `31`.

Evidencia: títulos y descripciones de prueba, sin problema y sin etiquetas.

Impacto: contaminan ejemplos positivos de completitud y clasificación. Sus categorías actuales no están respaldadas por contenido.

Remediación: mantenerlos como casos negativos esperados (`draft`, información faltante, sin clasificación confiable) y solicitar aprobación para archivarlos o eliminarlos de producción.

### [Media] Dos ideas exceden el máximo de siete etiquetas

- idea `28`: 12 etiquetas;
- idea `33`: 10 etiquetas.

Impacto: reduce precisión y enseña al modelo a incluir conceptos incidentales, proveedores o capacidades no mencionadas.

Remediación: reducir a 4–7 conceptos respaldados. Las selecciones propuestas están en `AI_GOLD_STANDARD_V1.json`.

### [Media] Hay 45 etiquetas sin uso declarado

Impacto: amplían el espacio de candidatos, aumentan falsos positivos y conservan vocabulario antiguo que compite con términos canónicos.

Remediación: excluirlas del contexto sugerible por defecto. Revisar su historial antes de borrarlas; algunas pueden funcionar como alias de migración.

### [Media] Duplicados canónicos de etiquetas

Grupos detectados de forma determinista:

- `12 · Agente autónomo` / `63 · Agentes Autónomos`;
- `144 · Eventos` / `147 · Evento`;
- `8 · Nuevas ideas` / `9 · Nueva idea`.

Grupos semánticos adicionales detectados manualmente:

- `28 · IA Generativa` / `76 · Inteligencia Artificial Generativa`;
- `5 · Seguimiento proyectos` / `60 · Seguimiento de Proyectos`;
- `145 · Aplicación Web` / `167 · App`.

Impacto: fragmenta conteos y hace que la acción `reuse_existing` no sea determinista.

Remediación: establecer alias canónicos y ejecutar `TagSimilarityService` antes de presentar cualquier etiqueta nueva.

### [Media] La dimensión principal se usa como proxy de “solución con software”

Trece ideas están clasificadas en `Tecnología`, incluidas propuestas cuyo beneficio principal es gestión, gobernanza o experiencia de usuario.

Impacto: sesga la IA hacia Tecnología y hace redundante la dimensión `Tipo de iniciativa`.

Remediación: aplicar la regla “área beneficiada, no herramienta utilizada”. Las reclasificaciones concretas están en `AI_TAXONOMY_RECOMMENDATIONS_V1.md`.

### [Media] El área principal está configurada como jerárquica, pero no contiene ramas

Las 28 categorías exportadas tienen `parent_id = null`; `path` está vacío y `path_label` sólo contiene el nombre.

Impacto: no existe evidencia real para entrenar o evaluar selección de subcategorías, aunque el esquema admita jerarquía.

Remediación: tratar V1 como taxonomía plana o aprobar una primera estructura de subcategorías antes de evaluar clasificación jerárquica.

### [Media] Siete relaciones requieren retirada o cambio de tipo

Revisión recomendada:

| Relación | Estado recomendado | Motivo |
|---:|---|---|
| 1 | Cambiar `related_to` por `depends_on` o conservar sólo con revisión | La justificación describe al Banco de Ideas como plataforma necesaria para el caso INNOVATEP. |
| 5 | Cambiar `depends_on` por `complements` | La captura por voz puede sugerir etiquetas sin depender del explorador visual de etiquetas. |
| 8 | Retirar | Une las ideas 12 y 8, que ya tienen relación hija-madre; es redundante. |
| 11 | Retirar o justificar mejor | Cualquier investigación podría publicarse en el portal; el vínculo actual es demasiado genérico. |
| 10 | Retirar mientras la idea 22 sea hija de la 20 | La relación `enables` repite la jerarquía actual. |
| 12 | Retirar o justificar mejor | La capacidad de votación no tiene un mecanismo específico de integración descrito con el portal. |
| 14 | Retirar o justificar mejor | La posible publicación de una herramienta en el portal es una relación genérica aplicable a muchos productos. |

No se detectaron pares duplicados, autorrelaciones ni referencias externas dentro de las 14 relaciones exportadas.

### [Media] Una jerarquía contradice la autonomía declarada

Idea `17` está bajo la idea `10`, pero su descripción establece que también existirán tareas independientes que no nacen de ideas.

Impacto: una funcionalidad autónoma puede quedar oculta como microidea y el Gold Standard enseñaría contención donde sólo existe integración.

Remediación: recomendarla como raíz y representar la integración mediante `complements`, sujeto a confirmación humana.

### [Baja] Dos subideas están ubicadas bajo una rama demasiado específica

Ideas `3` y `4` están bajo la idea `26`. El explorador de etiquetas y la investigación de arquitectura de IA apoyan varias capacidades del producto, no sólo el árbol de ideas.

Remediación: moverlas como hijas directas de la idea `10`, sujeto a revisión.

## 4. Controles que pasaron

- Los recuentos declarados coinciden con los arreglos reales.
- No hay IDs duplicados.
- No hay madres inexistentes ni ciclos.
- La lista de hijas coincide con los `parent_idea_id`.
- Todas las ideas tienen selección en las tres dimensiones requeridas.
- Ninguna dimensión `single` tiene múltiples valores.
- Ninguna idea utiliza categoría inactiva.
- Toda categoría principal aparece en sus clasificaciones.
- Todas las etiquetas utilizadas existen en la taxonomía exportada.
- Todas las relaciones apuntan a ideas incluidas.
- No hay relaciones de una idea consigo misma ni pares repetidos.

## 5. Riesgos y límites de la auditoría

- La exportación representa un solo propietario; no demuestra que las reglas generalicen a otras áreas o estilos de redacción.
- Los conteos de uso de categorías y etiquetas son globales, pero no se incluyó contenido de otros autores para reconciliarlos.
- No se incluyeron estados, visibilidad, comentarios, adjuntos ni historial; la auditoría no puede juzgar esas dimensiones.
- Las decisiones de redacción y jerarquía son recomendaciones humanas curadas, no correcciones automáticas.
- No se ejecutó ninguna modificación sobre producción.

## 6. Pruebas automatizadas recomendadas para el exportador

- unicidad de IDs;
- consistencia de estadísticas;
- integridad madre-hija y ausencia de ciclos;
- IDs de clasificaciones y relaciones dentro de allowlists;
- dimensiones requeridas presentes y cardinalidad válida;
- categorías activas únicamente;
- 4–7 etiquetas para ideas completas;
- detección de sinónimos singular/plural;
- marcación explícita de campos derivados, especialmente `summary`;
- exclusión o etiquetado de registros de prueba.
