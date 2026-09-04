<!-- Context: project-intelligence/navigation | Priority: critical | Version: 1.0 | Updated: 2026-09-04 -->

# Project Intelligence — Navigation

**Purpose**: Quick overview of all project intelligence files. Start here to find the right context.
**Last Updated**: 2026-09-04

## Quick Routes (start here)
| File | Description | Priority |
|------|-------------|----------|
| `technical-domain.md` | Tech stack, architecture, code patterns, naming, standards, security | critical |
| `business-domain.md` | Business concepts, roles, workflows, modules | critical |
| `business-tech-bridge.md` | Map business concepts → code implementation | high |
| `decisions-log.md` | Key architectural decisions & rationale | medium |
| `living-notes.md` | Running notes, tips, gotchas | low |

## Deep Dives
| Topic | File | Section |
|-------|------|---------|
| Tech stack & stack rationale | `technical-domain.md` | Primary Stack |
| Controller pattern | `technical-domain.md` | Code Patterns |
| Model pattern | `technical-domain.md` | Code Patterns |
| Routing | `technical-domain.md` | Code Patterns |
| Naming conventions | `technical-domain.md` | Naming Conventions |
| Code standards | `technical-domain.md` | Code Standards |
| Security requirements | `technical-domain.md` | Security Requirements |
| Roles & permissions | `business-domain.md` | Roles |
| Core modules | `business-domain.md` | Core Modules |
| Workflows | `business-domain.md` | Key Workflows |
| Status enums | `business-domain.md` | Status Enumerations |
| Concept → code map | `business-tech-bridge.md` | Concept → Code Map |
| Module → controller/model | `business-tech-bridge.md` | Module → Controller/Model Map |
| Thai ↔ English naming | `business-tech-bridge.md` | Thai ↔ English Naming |
| Architecture decisions | `decisions-log.md` | Decisions |
| Setup & gotchas | `living-notes.md` | Notes |

## How to Use
1. **New feature** → read `technical-domain.md` (patterns) + `business-tech-bridge.md` (where to implement)
2. **Understand domain** → read `business-domain.md`
3. **Why something is built this way** → read `decisions-log.md`
4. **Quick tips** → read `living-notes.md`

## 📂 Codebase References
**Implementation**: `index.php`, `app/` (Controllers, Models, Views, Core, Helpers), `config/database.php`, `includes/`

## Related Files
- All files in this directory are linked above.
