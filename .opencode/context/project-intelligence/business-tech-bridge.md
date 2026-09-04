<!-- Context: project-intelligence/bridge | Priority: high | Version: 1.0 | Updated: 2026-09-04 -->

# Business ↔ Tech Bridge

**Purpose**: Maps business concepts to actual code so agents can translate requirements into the right implementation.
**Last Updated**: 2026-09-04

## Quick Reference
**Update Triggers**: New modules | Renames | Refactors
**Audience**: Developers, AI agents

## Concept
Every business concept has a canonical code location. Use this map to find where to implement a feature.

## Concept → Code Map

### Roles
| Business | Code |
|----------|------|
| ผู้ดูแลระบบ | `admin` → `getCurrentRole()`, `hasRole(['admin'])` |
| เจ้าหน้าที่ | `staff` |
| อาจารย์ | `teacher` |
| นักศึกษา | `student` |
| Role label (Thai) | `translateRole($role)` |

### Equipment Status
| Business | Code value | Thai label |
|----------|-----------|-----------|
| พร้อมใช้งาน | `available` | `translateEquipmentStatus()` |
| ส่งซ่อม | `repair` | |
| ซ่อมไม่ได้ | `broken` | |
| จำหน่ายออก | `disposed` | |
| รอจำหน่ายออก | `pending_disposal` | |

### Repair Status
| Business | Code value |
|----------|-----------|
| รอดำเนินการ | `pending` |
| กำลังซ่อม | `in_progress` |
| ซ่อมเสร็จ | `completed` |
| ซ่อมไม่ได้ | `cannot_fix` |

### User Status
| Business | Code value |
|----------|-----------|
| รออนุมัติ | `pending` |
| อนุมัติแล้ว | `approved` |
| ถูกปฏิเสธ | `rejected` |

## Module → Controller/Model Map
| Module | Controller | Model(s) |
|--------|-----------|----------|
| ครุภัณฑ์ | `EquipmentController` | `Equipment`, `EquipmentImage`, `EquipmentStats`, `AssetCategory` |
| แจ้งซ่อม | `RepairController` | `Repair` |
| ค่าเสื่อมราคา | `DepreciationController` | `DepreciationSetting`, `DepreciationReport`, `DepreciationCalculator` |
| ความพึงพอใจ | `SatisfactionController` | `Satisfaction` |
| ผู้ใช้ | `UserController`, `AuthController` | `User` |
| ข้อมูลอ้างอิง | `Department/Set/Item/Room/RoomManagerController` | `Department`, `SetModel`, `Item`, `Room`, `RoomManager` |
| แอดมิน | `AdminController` | `SystemLog` |

## Thai ↔ English Naming
| Thai | English (code) |
|------|----------------|
| ครุภัณฑ์ | `equipment` |
| แจ้งซ่อม | `repair` |
| ค่าเสื่อมราคา | `depreciation` |
| ความพึงพอใจ | `satisfaction` |
| หน่วยงาน/แผนก | `department` |
| ชุด/หมวด | `set` |
| รายการ/ประเภท | `item` |
| ห้อง | `room` |
| ผู้ดูแลห้อง | `room_manager` |

## Common Implementation Patterns
- **List page**: Controller `index()` → `Model::getFiltered()` → view `{module}/index`
- **Form submit**: POST route → `validateCsrf()` → validate → `Model::create/update` → `flash()` → `redirect()`
- **Delete**: POST route → `validateCsrf()` → check constraints → `Model::delete` → `logActivity()` → redirect
- **Export**: GET route → build CSV/array → `csvSafe()` on values → download

## 📂 Codebase References
**Implementation**: `app/Controllers/*.php`, `app/Models/*.php`, `app/Helpers/functions.php` (translate* helpers), `index.php` (routes)
**Views**: `app/Views/{module}/`

## Related Files
- Technical Domain: `technical-domain.md`
- Business Domain: `business-domain.md`
