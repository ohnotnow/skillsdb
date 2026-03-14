# Technical Overview

Last updated: 2026-01-12

## What This Is

A database for tracking IT team members and their skills, with proficiency levels and admin approval workflows.

## Stack

- PHP 8.4 / Laravel 12
- Livewire 4 + Flux UI Pro 2 (TALL stack)
- MySQL database
- Keycloak SSO via Laravel Socialite
- Sanctum for API authentication
- Horizon for queue management
- Pest 4 for testing

## Directory Structure

```
app/
├── Enums/              # SkillLevel, SkillHistoryEvent
├── Http/
│   ├── Controllers/
│   │   ├── Api/        # UserController (Sanctum-protected)
│   │   └── Auth/       # SSOController (Keycloak login)
│   ├── Middleware/     # AdminMiddleware
│   └── Resources/      # API Resources
├── Livewire/
│   ├── Admin/          # Admin-only components
│   │   ├── SkillsManager.php      # CRUD for global skills list
│   │   ├── UserSkillsManager.php  # View all users
│   │   ├── UserSkillsEditor.php   # Edit any user's skills
│   │   ├── SkillsMatrix.php       # Team skills overview
│   │   └── ApiTokensManager.php   # Sanctum token management
│   ├── HomePage.php               # Landing page
│   ├── SkillsEditor.php           # User edits own skills
│   └── SkillsDashboard.php        # Stats and charts
├── Models/             # User, Skill, SkillCategory, SkillUser, SkillHistory
└── Observers/          # SkillUserObserver (tracks history)

routes/
├── web.php             # Main routes (all auth-protected)
├── api.php             # GET /api/users (Sanctum)
└── sso-auth.php        # SSO login/callback routes
```

## Domain Model

```
SkillCategory
    │
    ↓ hasMany
  Skill ←──────────── approved_by ──────────── User
    │                                            │
    └─────────── belongsToMany ─────────────────┘
                    (pivot: SkillUser)
                         │
                         ↓ observed by
                   SkillHistory
```

### Key Model Fields

| Model | Notable Fields |
|-------|----------------|
| User | `is_admin`, `is_staff`, `last_updated_skills_at` |
| Skill | `approved_by`, `approved_at` (null = pending) |
| SkillUser | `level` (enum: 1-3) |
| SkillHistory | `event_type`, `old_level`, `new_level` |

### Enums

**SkillLevel** (int-backed: 1, 2, 3)
- `Low`, `Medium`, `High`
- Helpers: `label()`, `colour()`, `bgClass()`

**SkillHistoryEvent** (string-backed)
- `Added`, `Removed`, `LevelledUp`, `LevelledDown`
- Helpers: `label()`, `icon()`, `colour()`

### Observer: SkillUserObserver

Automatically creates `SkillHistory` entries when:
- Skill added to user → `Added`
- Skill level changed → `LevelledUp` / `LevelledDown`
- Skill removed → `Removed`

## Authorization

| Role | Determined By | Access |
|------|---------------|--------|
| User | `is_staff = true` | Own skills, suggest new skills |
| Admin | `is_admin = true` | All users, approve skills, CRUD |

### Middleware

- `auth` - Standard Laravel auth (all routes)
- `admin` - Checks `$user->isAdmin()`, aborts 403

### Skill Approval Workflow

1. User suggests skill → created with `approved_at = null`
2. User can immediately use their own pending skills
3. Admin approves → sets `approved_at` and `approved_by`
4. Approved skills visible to all users

## Routes Overview

### Web Routes

| Route | Component | Access |
|-------|-----------|--------|
| `/` | HomePage | auth |
| `/admin/skills` | SkillsManager | admin |
| `/admin/users` | UserSkillsManager | admin |
| `/admin/users/{user}` | UserSkillsEditor | admin |
| `/admin/matrix` | SkillsMatrix | admin |
| `/admin/api-tokens` | ApiTokensManager | admin |

### API Routes

| Endpoint | Method | Auth |
|----------|--------|------|
| `/api/users` | GET | Sanctum token |

Returns all users with their skills and categories.

## Key Business Logic

| Location | Purpose |
|----------|---------|
| `User::getSkillDistribution()` | Count skills by level |
| `User::getSkillsOverTimeFromHistory()` | Historical skill points chart |
| `User::hasStaleSkills()` | True if >4 weeks since update |
| `Skill::getTrendingSkills()` | Popular recently-added skills |
| `SkillsEditor::suggestSkill()` | Create pending skill + attach |
| `SkillsManager::approveSkill()` | Admin approves pending skill |

## Testing

- Framework: Pest 4
- Pattern: Feature tests, RefreshDatabase, in-memory SQLite
- Key factories: `User::factory()->admin()`, `Skill::factory()->approved()`, `Skill::factory()->pending()`
- Run: `php artisan test --compact`

## Local Development

```bash
lando start
lando artisan migrate
lando artisan db:seed --class=TestDataSeeder
npm run build
```

Test user: `admin2x` / `secret`

## Needs updated
