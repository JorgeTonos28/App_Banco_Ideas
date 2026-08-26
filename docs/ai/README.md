# Auditoría de IA

Esta carpeta contiene los artefactos versionables de la Fase 0 del asistente de captura y organización de ideas.

## Artefactos versionables

- `AI_CLASSIFICATION_PLAYBOOK_V1.md`: reglas generalizables de redacción, completitud, clasificación, jerarquía, etiquetas y relaciones.
- `AI_TAXONOMY_RECOMMENDATIONS_V1.md`: correcciones y propuestas para dimensiones, categorías y etiquetas.
- `AI_DATA_INCONSISTENCIES_V1.md`: perfil de calidad, hallazgos, riesgos y controles superados.

## Artefactos privados

Los archivos siguientes contienen texto e IDs de ideas de producción y se guardan localmente en `storage/app/private/ai-audit/`, que está excluido de Git:

- `AI_GOLD_STANDARD_V1.json`;
- `AI_EVAL_CASES_V1.json`.

No deben moverse a `docs/`, publicarse ni incorporarse a un commit sin una revisión institucional de privacidad y autorización explícita.

## Validación reproducible

Perfil estructural de la exportación:

```powershell
node scripts/ai/audit-planning-context.mjs C:\ruta\ai-planning-context.json
```

Validación cruzada del Gold Standard y los casos de evaluación:

```powershell
node scripts/ai/validate-ai-audit-artifacts.mjs `
  C:\ruta\ai-planning-context.json `
  storage/app/private/ai-audit/AI_GOLD_STANDARD_V1.json `
  storage/app/private/ai-audit/AI_EVAL_CASES_V1.json
```

La validación estructural no reemplaza la aprobación humana de redacción, jerarquía, taxonomía y relaciones.

## Decisiones aprobadas el 25 de agosto de 2026

- reactivar `12 · Comunicación y Marca Institucional`;
- convertir la idea 17 en raíz y relacionarla como `complements` con la idea 10;
- conservar la idea 20 como rama evolutiva bajo la idea 10;
- archivar las ideas de prueba 30 y 31 y excluirlas del corpus positivo.

El comando `php artisan ideas:apply-ai-audit-decisions` muestra una vista previa. Sólo modifica la base con `--apply`, valida IDs y títulos antes de iniciar la transacción y es idempotente.
