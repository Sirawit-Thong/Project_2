<!-- Context: project-intelligence/notes | Priority: low | Version: 1.0 | Updated: 2026-09-04 -->

# Living Notes

**Purpose**: Running notes, tips, and gotchas discovered while working on this project. Append freely; keep entries short and actionable.
**Last Updated**: 2026-09-04

## Quick Reference
**Update Triggers**: New gotchas | Useful tips | Workarounds
**Audience**: Developers, AI agents

## Concept
A lightweight scratchpad for knowledge that doesn't fit the formal domain files but saves time on future work.

## Notes

### Setup & Run
- Local: XAMPP → `http://localhost/Project_2/` (auto-detected as local)
- Production: `https://khuruphan-rus.free.nf` (auto-detected as prod)
- DB name local: `equipment_db`; prod: `if0_40083938_invent_db`
- Import schema from `database.sql` / `database_upgrade_*.sql`

### Gotchas
- **Timezone**: Always use `Asia/Bangkok`. Never assume server UTC. PDO sets `SET time_zone='+07:00'`.
- **Autoloader**: New classes must be added to the class map in `app/init.php` (no Composer autoload).
- **Views**: Controllers set `$viewPath` + `$pageTitle`, then `require .../Views/layouts/main.php`. Do NOT echo directly.
- **CSRF**: Every POST form needs `<?= csrf_field() ?>` and the controller must call `validateCsrf()`.
- **File upload**: Uses `finfo` real MIME + `getimagesize`; only JPG/PNG/GIF/WEBP, max 5MB. Filenames are randomized.
- **CSV export**: Always run values through `csvSafe()` to prevent formula injection.
- **Teacher redirect**: Teacher role is forced to `/equipment/my` in `EquipmentController::index()`.

### Tips
- Use `logActivity($userId, $action, $details)` for audit trail on mutations.
- Reuse `paginationLinks($pagination, $baseUrl)` for consistent pagination UI.
- Status badges: `getStatusBadgeClass($status)` maps to Bootstrap colors.
- Dark/light theme is stored in `localStorage`; set `data-bs-theme` before render to avoid FOUC.

## 📂 Codebase References
**Implementation**: `app/init.php`, `config/database.php`, `app/Helpers/functions.php`, `app/Core/csrf.php`, `app/Views/layouts/main.php`, `includes/header.php`

## Related Files
- Technical Domain: `technical-domain.md`
- Decisions Log: `decisions-log.md`
