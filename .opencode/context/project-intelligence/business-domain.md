<!-- Context: project-intelligence/business | Priority: critical | Version: 1.0 | Updated: 2026-09-04 -->

# Business Domain

**Purpose**: Business concepts, roles, and workflows of the equipment management & repair system (ระบบแจ้งซ่อมครุภัณฑ์) for RMUTSB.
**Last Updated**: 2026-09-04

## Quick Reference
**Update Triggers**: New modules | Role changes | Workflow changes
**Audience**: Developers, AI agents, stakeholders

## Concept
ระบบจัดการครุภัณฑ์และแจ้งซ่อมของมหาวิทยาลัย (RMUTSB) — ติดตามทะเบียนครุภัณฑ์, รับแจ้งซ่อม, คำนวณค่าเสื่อมราคา, วัดความพึงพอใจ, และจัดการผู้ใช้ตามบทบาท.

## Roles (บทบาท)
| Role | Thai | Permissions |
|------|------|-------------|
| `admin` | ผู้ดูแลระบบ | Full access, user mgmt, backup, logs, reports |
| `staff` | เจ้าหน้าที่ | Manage equipment, repairs, depreciation, disposal |
| `teacher` | อาจารย์ | View/check equipment, submit repairs, own equipment |
| `student` | นักศึกษา | Submit repairs, view own equipment |

## Core Modules
1. **Equipment (ครุภัณฑ์)** — register, edit, bulk-add, images, inspection, disposal, my-equipment
2. **Repairs (แจ้งซ่อม)** — submit, track status (pending → in_progress → completed / cannot_fix)
3. **Depreciation (ค่าเสื่อมราคา)** — settings, calculation, reports, export
4. **Satisfaction (ความพึงพอใจ)** — post-repair survey, dashboard, export
5. **User Management** — register (student/teacher), pending approval, roles
6. **Reference Data** — departments, sets, items, rooms, room managers
7. **Admin** — backup, logs, reports, exports

## Key Workflows
### Equipment Lifecycle
```
register → available → (repair) → repair → broken → pending_disposal → disposed
```
### Repair Flow
```
student/teacher submit → staff in_progress → completed → satisfaction survey
```

## Business Rules
- Student/teacher register with university email `@rmutsb.ac.th` only
- New accounts are `pending` until admin approves
- Equipment with repair history cannot be deleted
- Depreciation requires cost price + valid purchase year + life/rate settings
- Teacher role auto-redirects to `/equipment/my`

## Status Enumerations
- **Equipment**: `available`, `repair`, `broken`, `disposed`, `pending_disposal`
- **Repair**: `pending`, `in_progress`, `completed`, `cannot_fix`
- **User**: `pending`, `approved`, `rejected`

## 📂 Codebase References
**Implementation**: `app/Controllers/` (AuthController, EquipmentController, RepairController, DepreciationController, SatisfactionController, UserController, AdminController, DashboardController), `app/Models/` (Equipment, Repair, Depreciation*, Satisfaction, User, Department, SetModel, Item, Room, RoomManager, SystemLog)
**Views**: `app/Views/` (dashboard, equipment, repair, depreciation, satisfaction, user, admin, auth, crud)
**Routes**: `index.php`

## Related Files
- Technical Domain: `technical-domain.md`
- Business↔Tech Bridge: `business-tech-bridge.md`
