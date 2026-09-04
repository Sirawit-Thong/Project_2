<!-- Context: project-intelligence/decisions | Priority: medium | Version: 1.0 | Updated: 2026-09-04 -->

# Decisions Log

**Purpose**: Records key architectural and technical decisions with rationale, so future changes respect the original intent.
**Last Updated**: 2026-09-04

## Quick Reference
**Update Triggers**: New architecture decisions | Reversals of prior decisions
**Audience**: Developers, AI agents

## Concept
Every significant decision is logged with context, the decision, and the trade-off. New work should align with these unless a decision is explicitly reversed here.

## Decisions

### D1: Custom PHP MVC over a framework
- **Date**: Initial build
- **Context**: Shared hosting (InfinityFree), no Composer, need lightweight deploy
- **Decision**: Hand-rolled `Router` + `Controller` + `Model` base classes, manual autoloader in `app/init.php`
- **Trade-off**: More boilerplate, but zero vendor deps and full control
- **Code**: `app/Core/Router.php`, `app/Core/Controller.php`, `app/Core/Model.php`, `app/init.php`

### D2: PDO with prepared statements over an ORM
- **Context**: Need raw SQL control for complex joins (equipment filters, depreciation)
- **Decision**: Static `Model` class wrapping PDO; `EMULATE_PREPARES=false`; all queries use `?` placeholders
- **Trade-off**: No ORM magic, but explicit, secure, and fast
- **Code**: `app/Core/Model.php`, `config/database.php`

### D3: Asia/Bangkok timezone enforced globally
- **Context**: InfinityFree runs UTC, causing 7-14 hr timestamp drift
- **Decision**: `date_default_timezone_set('Asia/Bangkok')` in `config/database.php` + `app/init.php`; PDO `SET time_zone='+07:00'`
- **Trade-off**: Thai users see correct local times; minor coupling to Thai locale
- **Code**: `config/database.php`, `app/init.php`

### D4: Thai-language UI with helper translations
- **Context**: End users are Thai university staff/students
- **Decision**: UI text in Thai; status/role labels via `translate*()` helpers; dates via `formatDateThai()`
- **Trade-off**: Not i18n-ready, but matches user needs
- **Code**: `app/Helpers/functions.php`

### D5: Local/Production auto-detect
- **Context**: Same codebase runs on XAMPP (local) and InfinityFree (prod)
- **Decision**: `config/database.php` detects local vs prod via `REMOTE_ADDR`/`HTTP_HOST`/CLI; env vars can override
- **Trade-off**: Simple deploy, but credentials live in config (see security note in file)
- **Code**: `config/database.php`

### D6: Security-first session & auth
- **Context**: Public-facing system with role-based access
- **Decision**: CSRF on all POST, RateLimiter on login, session hardening (httponly/secure/SameSite/strict/30-min timeout/ID regen), secure file upload (finfo + getimagesize)
- **Trade-off**: More code, but protects against common web attacks
- **Code**: `app/Core/csrf.php`, `app/Core/RateLimiter.php`, `app/init.php`, `app/Helpers/functions.php`

## 📂 Codebase References
**Implementation**: `app/Core/*.php`, `config/database.php`, `app/init.php`, `app/Helpers/functions.php`
**Config**: `config/database.php`, `.htaccess`

## Related Files
- Technical Domain: `technical-domain.md`
- Business Domain: `business-domain.md`
