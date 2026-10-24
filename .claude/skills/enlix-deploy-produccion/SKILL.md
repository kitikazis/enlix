---
name: enlix-deploy-produccion
description: Usar al hacer commit, push o desplegar enlix.pe al hosting compartido (cPanel), o cuando un cambio "no se ve" en producción.
---

# Commit y despliegue de enlix.pe

## Dónde corre cada cosa
| Paso | Dónde | Por qué |
|---|---|---|
| Editar, `php artisan test`, `git commit`, `git push` | PC local (carpeta del repo) | Ahí están los cambios y la identidad git (kitikazis). |
| `git pull`, `optimize:clear` | Servidor (`~/public_html`, Terminal de cPanel) | El servidor solo **recibe** código; no tiene `user.name` ni hace commits. |

Nunca pegar en el servidor un bloque que incluya `git add`/`git commit`/`git push`.

## Commits
- Formato: `tipo: descripcion en español sin tildes` (`feat:`, `fix:`, `test:`, `chore:`).
- **Nunca** agregar `Co-Authored-By: Claude` ni ninguna atribución a Claude.
- Antes de commitear: `php artisan test` (sqlite en memoria, no toca producción).
- Hacer commit solo cuando el usuario lo pida.

```bash
git add <archivos>
git commit -m "fix: descripcion corta"
git push
```

## Desplegar desde la PC por SSH (preferido)
Alias `enlix` en `~/.ssh/config` (llave local autorizada en cPanel; host y usuario solo en ese archivo, nunca en el repo). Este comando exacto está permitido en `.claude/settings.local.json`:
```bash
ssh enlix 'cd ~/public_html && git pull origin main && php artisan optimize:clear && php artisan view:clear && git log --oneline -1'
```
Luego verificar en vivo: `curl -s "https://enlix.pe/?nc=$(date +%s)" | grep ...`. Si aún se ve lo viejo, esperar ~1 min (OPcache reutiliza la vista compilada con el mismo nombre) y repetir antes de investigar más.
Cualquier otro comando que modifique el servidor: pedir confirmación al usuario primero.

## Desplegar (Terminal de cPanel)
```bash
cd ~/public_html
git status                 # si hay archivos modificados en el servidor, NO hacer pull: revisar primero
git pull origin main
php artisan optimize:clear
php artisan view:clear
git log --oneline -1       # confirmar que coincide con el último commit local
```
- Si pide `Username for 'https://github.com'`: usuario `kitikazis` y, como contraseña, un **Personal Access Token** de GitHub (no la contraseña de la cuenta).
- `Ctrl+C` cancela un prompt colgado. `git reset` (sin `--hard`) deshace un `git add` accidental sin tocar archivos.
- `php artisan migrate --force` solo si el commit trae migraciones nuevas, y solo con confirmación explícita del usuario.

## "Mi cambio no se ve en producción"
1. `git log --oneline -1` en el servidor vs. local: ¿el commit llegó?
2. ¿Se corrió `optimize:clear`/`view:clear`?
3. ¿Mensajes de validación en inglés ("The X has already been taken")? Señal de que el servidor corre código anterior a los `messages()` en español.
4. Si todo coincide y sigue igual: OPcache con `validate_timestamps=0` → reiniciar PHP desde cPanel (MultiPHP / "Restart PHP").
5. Hard refresh del navegador (Ctrl+Shift+R) por JS/vistas cacheadas.

## Hosting compartido
- Cuota de disco de 1 GB: no subir `node_modules`, logs grandes ni imágenes sin procesar.
- El `.env` del servidor no se versiona; no sobrescribirlo.
