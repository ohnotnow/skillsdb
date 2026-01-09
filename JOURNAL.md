# Development Journal

Notes, decisions, and lessons learned during development. Written for future-us.

---

## 2026-01-09 - Project Kickoff & Core Foundation

### The Big Picture

SkillsDB is extracting skills tracking functionality from an existing application. The old templates in `resources/views/livewire/` (skills-manager.blade.php and profile.blade.php) were our reference for UI patterns - worth a look if you're wondering "how did they do X before?"

### Key Decisions Made

**Skill Approval Workflow**
- Pending skills have `approved_by` and `approved_at` as NULL
- When a user suggests a skill, it's created as pending but they can immediately assign it to themselves with a level
- The skill shows with a "Pending" badge and is only visible to the creator until an admin approves it
- This means users get instant gratification while admins maintain control over the global skills list

**Why SkillCategory is a Separate Model (not just a string)**
- Started as a "stub for now" decision, but having it as a proper model means we can:
  - Add category descriptions later
  - Control which categories exist (no typo variations)
  - Potentially add category-level permissions
- The FK is nullable so skills can exist without a category if needed

**SkillLevel Enum Values**
- 1 = Low, 2 = Medium, 3 = High
- We use `label()` and `colour()` methods (following team conventions)
- Colours: amber for Low, sky for Medium, green for High
- The radio pills include "None" as an option which detaches the skill entirely

**The "None" Level Pattern**
- In the UI, we show None/Low/Medium/High as radio pills
- "None" isn't a SkillLevel enum value - it triggers a detach
- This is cleaner than having a "0" or null level in the pivot table
- Watch out: the value comes through as the string "none", not null

### Component Architecture

**HomePage is intentionally thin**
- It's just a heading and `<livewire:skills-editor />`
- This lets us potentially reuse SkillsEditor elsewhere (admin views, etc.)
- The full-page component uses `#[Layout('components.layouts.app')]`

**SkillsEditor does the heavy lifting**
- Computed property for skills query (with eager loading for category)
- URL-bound search and filter state (`#[Url]` attribute)
- User skill levels stored in `$userSkillLevels` array, keyed by skill ID
- The `loadUserSkillLevels()` method refreshes this after changes

### Things That Caught Us Out

**RefreshDatabase wasn't enabled**
- The default Pest.php had RefreshDatabase commented out
- Tests were failing with "no such table" errors
- Fix: uncomment the `->use(Illuminate\Foundation\Testing\RefreshDatabase::class)` line in tests/Pest.php

**Artisan commands fail if routes reference non-existent classes**
- When adding `Route::get('/', HomePage::class)` before creating the component, artisan commands died
- Workaround: temporarily comment out the route, run make:livewire, uncomment

**Auth::user() type hints**
- Intelephense complains about undefined methods on Auth::user()
- The code works fine - it's just the IDE not knowing the return type
- Could add `@var \App\Models\User $user` comments but we decided not to clutter the code

### UI/UX Notes

**Flyout modals are preferred**
- The "Suggest Skill" modal uses `variant="flyout"`
- Slides in from the side, works beautifully on mobile
- The old templates used this pattern too - stick with it for consistency

**Toast notifications for reassurance**
- After suggesting a skill, we show a success toast via `Flux::toast()`
- Include both a heading ("Skill suggested!") and explanatory text
- Users (especially Windows folk!) appreciate confirmation that things worked

**Skill cards use flex-col with flex-1 spacer**
- Cards have variable content (some have descriptions, some don't)
- The radio pills should always be at the bottom
- `flex-col h-full` on the card content, `flex-1` on the description (or empty div) pushes pills down

### Database & Model Notes

**Pivot table timestamps are important**
- `skill_user` has Laravel timestamps
- Both relationships use `->withTimestamps()`
- This lets us track when skills were added/updated per user

**User.last_updated_skills_at**
- Separate from pivot timestamps - this is "when did the user last touch their skills at all"
- Updated via `$user->touchSkillsUpdatedAt()` after any skill change
- Useful for admins to spot people who haven't updated in ages

**Cascade deletes on pivot**
- If a user is deleted, their skill associations go too
- If a skill is deleted, all user associations go too
- The `approved_by` FK uses `nullOnDelete()` - we keep the skill but lose the approver reference

### Testing Patterns

**Livewire component testing**
- Use `Livewire::actingAs($user)->test(Component::class)`
- Can chain `->set()`, `->call()`, `->assertSee()`, etc.
- Remember to `->assertHasErrors()` for validation tests

**Factory states are your friend**
- `Skill::factory()->approved($admin)` - creates approved skill
- `Skill::factory()->pending()` - creates unapproved skill
- `User::factory()->admin()` - creates admin user

### Files Worth Knowing About

- `app/Enums/SkillLevel.php` - the level enum with label/colour methods
- `database/seeders/TestDataSeeder.php` - creates admin2x/secret user and sample data
- `resources/views/components/layouts/app.blade.php` - main layout with sidebar (shows admin links conditionally)
- `routes/sso-auth.php` - SSO/local auth setup (SSO_ENABLED env flag)

### Still To Do (Phase 1)

- Admin skills management (CRUD, approve pending)
- Admin user skills management (manage skills for any user)
- Excel export (using SimpleSpout - see example-simple-spout.txt)

### Phase 2 Ideas (parked for later)

- Gamification (progress bars, badges, streaks)
- Skills Coach (LLM-powered career suggestions)
- Notifications for admins when skills are suggested
- API endpoints via Sanctum

---

*Add new entries above this line*
