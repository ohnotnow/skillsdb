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

- ~~Admin skills management (CRUD, approve pending)~~ ✓ Done
- Admin user skills management (manage skills for any user)
- Excel export (using SimpleSpout - see example-simple-spout.txt)

### Phase 2 Ideas (parked for later)

- Gamification (progress bars, badges, streaks)
- Skills Coach (LLM-powered career suggestions)
- Notifications for admins when skills are suggested
- API endpoints via Sanctum

---

## 2026-01-09 - Admin Skills Management

### What We Built

Admin skills management page at `/admin/skills`:
- Table listing all skills with search
- Create/edit skills via flyout modal
- Delete with confirmation
- Approve pending skills (one-click from dropdown)
- Pending skills show inline badge + requester's name instead of user count

New files:
- `app/Http/Middleware/AdminMiddleware.php` - simple `isAdmin()` check, aborts 403
- `app/Livewire/Admin/SkillsManager.php` - full-page component
- `resources/views/livewire/admin/skills-manager.blade.php`
- `tests/Feature/Livewire/Admin/SkillsManagerTest.php` - 18 tests

The middleware is registered in `bootstrap/app.php` with alias `'admin'`.

### Things That Caught Us Out

**Flux::toast() signature gotcha**
- First attempt: `Flux::toast(variant: 'success', heading: 'Done!')` - FAILED
- The first positional argument `$text` is required, even with named params
- Fix: `Flux::toast(heading: 'Done!', text: '', variant: 'success')`
- The existing SkillsEditor code worked because it always passed `text:`

### Design Decisions & Simplifications

**Removed the "Status" column**
- Initially had a column showing "Approved" or "Pending" badges for every skill
- But 99% of skills will be approved - showing "Approved" on everything is noise
- Simplified: pending badge appears inline before the skill name, only when pending
- No badge = approved (the expected default)

**Removed "Show pending only" toggle**
- Initially built a toggle with pending count badge
- But pending skills are rare (maybe a few per year)
- YAGNI - removed the toggle, filter logic, computed property, and tests
- The search still works if you need to find something specific

**Pending skills show requester name, not user count**
- A pending skill will only have one user (the person who suggested it)
- Showing "1" isn't helpful - showing "J. Smith" tells the admin who asked for it
- Approved skills still show the count

### User Model Additions

Added attribute accessors (modern Laravel syntax):
```php
protected function fullName(): Attribute
{
    return Attribute::get(fn () => "{$this->forenames} {$this->surname}");
}

protected function shortName(): Attribute
{
    return Attribute::get(fn () => substr($this->forenames, 0, 1).'. '.$this->surname);
}
```

Note: `fullName()` was previously a regular method - now it's `$user->full_name`.

### TestDataSeeder Fix

The pending "Rust" skill wasn't attached to any user, which looked weird in the admin UI (no requester shown). Fixed by attaching it to the standard user in `assignSkillsToUsers()`.

### Meta: On Sub-Agents and the Beads Tool

We discussed whether a sub-agent for the `bd` (beads) issue tracker would help. Conclusion: **no**.

Why `bd` works well without a sub-agent:
- Commands are quick, output is concise (a few lines)
- Seeing the issue list helps maintain project context
- Closing an issue is a semantic decision, not just a command
- Only used 2-3 times per task - minimal overhead

Sub-agents make sense for: long-running tasks, large output that needs summarising, work that can run in parallel. `bd` is more like glancing at a post-it note.

The `bd` tool was designed by Steve Yegge specifically to be AI-friendly - and it shows. Hierarchical IDs, scannable output, sensible defaults, combined actions (`--claim`), workflow helpers (`--suggest-next`).

### Philosophy Note

Recording what *didn't* work is as valuable as recording what did. The "show pending only" feature was built, tested, worked perfectly... and then removed because it wasn't actually needed. That's not failure - that's learning. Academia has a "negative results" problem; we shouldn't.

"Success is a bad teacher" - we learn more from the things we backed out of than the things that worked first time.

---

*Add new entries above this line*
