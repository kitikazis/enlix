---
name: enlix-reporte-avances
description: Usar cuando el usuario pida "avances", "qué se hizo", un resumen de progreso o un reporte para el cliente/jefe sobre enlix.pe en un período (hoy, semana, mes, desde una fecha).
---

# Reporte de avances de enlix.pe

## 1. Definir período y audiencia
- Período: si no lo dice, usar **desde el último reporte** o los **últimos 7 días**, y decir cuál se usó.
- Audiencia:
  - **Cliente / no técnico** (por defecto): lenguaje de negocio, sin nombres de archivos ni clases.
  - **Técnico**: puede incluir hashes, archivos y rutas.

## 2. Recolectar datos (solo lectura)
```bash
git log --since="2026-10-13" --until="2026-10-20 23:59" --no-merges --format="%h|%ad|%s" --date=short
git log --since="2026-10-13" --no-merges --shortstat --format="" | awk '{f+=$1; i+=$4; d+=$6} END {print f" archivos, +"i" / -"d" lineas"}'
git status --short                      # trabajo sin commit = "en curso", no "terminado"
git log origin/main --oneline -1        # lo subido a GitHub
```
- Lo desplegado en producción solo se sabe con `git log --oneline -1` **en el servidor** (Terminal de cPanel). Si no se verificó, reportarlo como "subido, pendiente de verificar en producción", nunca como "en producción".
- Tests: `php artisan test` (sqlite, seguro) y reportar el conteo real.

## 3. Clasificar commits
| Prefijo | Sección del reporte |
|---|---|
| `feat:` | Nuevas funcionalidades |
| `fix:` | Correcciones |
| `test:` | Calidad (agrupar en una línea: "N pruebas automáticas nuevas") |
| `chore:`, `refactor:` | Mantenimiento (una línea, o omitir para cliente) |

- Agrupar commits del mismo tema en un solo punto (ej. 4 commits de productos → "Módulo de productos: …").
- Traducir a beneficio: `fix: chequeo de referencia cuenta productos en papelera` → "El aviso de referencia disponible ya coincide con lo que acepta el guardado; si la referencia es de un producto eliminado, se ofrece restaurarlo."
- No inventar avances: todo punto sale de un commit, del `git status` o de lo hecho en la conversación.

## 4. Formato (cliente)
```markdown
# Avances enlix.pe — 13 al 20 de octubre de 2026

## Resumen
2–3 frases: qué cambió para el negocio esta semana.

## Nuevas funcionalidades
- **Páginas legales**: términos, privacidad y libro de reclamaciones, editables desde el admin.

## Correcciones
- **Productos**: …

## Calidad
- 241 pruebas automáticas pasando (6 nuevas).

## Estado del despliegue
- Subido a GitHub: sí (commit 55f901a). En producción: pendiente de verificar.

## Pendiente / próximos pasos
- …
```
- Fechas absolutas (no "ayer"). Español, frases cortas, sin jerga.
- Versión técnica: añadir hash corto al final de cada punto `(55f901a)` y una sección "Archivos principales".

## 5. Entrega
- Mostrarlo en el chat listo para copiar.
- Si es para compartir (cliente, jefe, grupo), ofrecer publicarlo como página o documento.
- No crear archivos en el repo (`AVANCES.md`, etc.) salvo que el usuario lo pida.
