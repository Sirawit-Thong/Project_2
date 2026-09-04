<!-- Context: project-intelligence/technical | Priority: critical | Version: 1.0 | Updated: 2026-09-04 -->

# Technical Domain

**Purpose**: Tech stack, architecture, and development patterns for the equipment management & repair system (ระบบแจ้งซ่อมครุภัณฑ์).
**Last Updated**: 2026-09-04

## Quick Reference
**Update Triggers**: Tech stack changes | New patterns | Architecture decisions
**Audience**: Developers, AI agents

## Primary Stack
| Layer | Technology | Rationale |
|-------|-----------|-----------|
| Framework | Custom PHP MVC (no Composer) | Lightweight, full control, no vendor deps |
| Language | PHP 8+ | Native, shared hosting friendly |
| Database | MySQL via PDO (utf8mb4) | Prepared statements, `EMULATE_PREPARES=false` |
| Frontend | Bootstrap 5.3.2 + Icons + Chart.js | CDN, Prompt font, dark/light theme |
| Deploy | XAMPP (local) / InfinityFree (prod) | Auto-detect via `config/database.php` |

## Architecture
- **Front Controller**: `index.php` → `Router::dispatch()` → `Controller@method`
- **Autoloader**: manual class map in `app/init.php` (no Composer)
- **Layers**: `app/Controllers/`, `app/Models/`, `app/Views/`, `app/Core/`, `app/Helpers/`, `includes/`
- **Time**: `Asia/Bangkok` enforced globally (กัน InfinityFree UTC ช้า 7-14 ชม.)

## Code Patterns

### Controller (extends `Controller`)
```php
class EquipmentController extends Controller {
    public function index() {
        $this->requireLogin();
        $this->authorize(['admin', 'staff']);
        $result = Equipment::getFiltered($filters, $page, $perPage);
        $pageTitle = 'ทะเบียนครุภัณฑ์';
        $viewPath = 'equipment/index';
        require __DIR__ . '/../Views/layouts/main.php';
    }
    public function delete($id) {
        $this->validateCsrf();          // POST only
        Equipment::delete($id);
        logActivity(getCurrentUserId(), 'ลบครุภัณฑ์', 'รหัส: ' . $id);
        $this->flash('success', 'ลบสำเร็จ');
        $this->redirect(SITE_URL . '/equipment');
    }
}
```

### Model (extends `Model`, static)
```php
class Equipment extends Model {
    protected static $table = 'equipment';
    public static function getFiltered($filters, $page, $perPage = 20) {
        // Build $where[] + $params[] with '?' placeholders
        $total = (int) self::fetchColumn($countSql, $params);
        $pagination = self::paginate($total, $page, $perPage);
        return self::fetchAll($sql, $params);
    }
}
```

### Route (in `index.php`)
```php
$router->get('/equipment', 'EquipmentController@index');
$router->post('/equipment/edit/{id}', 'EquipmentController@edit');
```

## Naming Conventions
| Type | Convention | Example |
|------|-----------|---------|
| Files | PascalCase + suffix | `EquipmentController.php`, `Equipment.php` |
| Classes | PascalCase | `EquipmentController`, `SetModel` |
| Methods | camelCase | `getFiltered`, `requireLogin` |
| DB tables | snake_case | `equipment`, `room_managers` |
| Routes | kebab-case | `/equipment/bulk-add` |

## Code Standards
- All DB access via prepared statements (`?` placeholders) — never string interpolation
- Controllers: `requireLogin()` + `authorize([...])` at top of each method
- POST mutations always call `validateCsrf()`
- Views escape output with `e()` / `sanitize()`; use `<?= ?>` not `<?php echo ?>`
- Thai UI text; use helper `translate*()` for status/role labels
- Flash messages via `$this->flash($type, $msg)` + `flashMessage()` in layout
- Log actions with `logActivity($userId, $action, $details)`

## Security Requirements
- CSRF token on all POST forms (`csrf_field()` + `require_csrf()`)
- Role-based access: `authorize(['admin','staff'])`, `hasRole()`, `requireRole()`
- Session hardening: httponly, secure (HTTPS), SameSite=Lax, strict mode, 30-min timeout, ID regeneration
- Login rate limiting via `RateLimiter` (5 attempts / 15 min lockout)
- Secure file upload: `finfo` real MIME check, `getimagesize` verify, random filename, 5MB max
- Output escaping with `e()`; CSV formula injection guard via `csvSafe()`
- Security headers in `app/init.php` (nosniff, SAMEORIGIN, XSS-Protection, Referrer-Policy)

## 📂 Codebase References
**Implementation**: `index.php` (routes), `app/Core/Router.php`, `app/Core/Controller.php`, `app/Core/Model.php`, `app/init.php` (autoloader/security), `config/database.php` (PDO/timezone), `app/Helpers/functions.php` (helpers), `app/Core/csrf.php`, `app/Core/RateLimiter.php`, `app/Core/ErrorHandler.php`
**Config**: `config/database.php`, `.htaccess`

## Related Files
- Business Domain: `business-domain.md`
- Business↔Tech Bridge: `business-tech-bridge.md`
- Decisions Log: `decisions-log.md`
