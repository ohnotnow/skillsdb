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

## 2026-01-09 - Admin User Skills Management

### What We Built

Admin user skills management at `/admin/users`:
- Table listing all users with name (as link), email, skills count, last updated
- Click user name to go to `/admin/users/{user}` to manage their skills
- Reuses the existing `SkillsEditor` component (consistent UX across the app)

New/modified files:
- `app/Livewire/Admin/UserSkillsManager.php` - list of users (simplified, no modal)
- `app/Livewire/Admin/UserSkillsEditor.php` - thin wrapper, just heading + SkillsEditor
- `app/Livewire/SkillsEditor.php` - now accepts optional `userId` parameter
- `resources/views/livewire/admin/user-skills-manager.blade.php`
- `resources/views/livewire/admin/user-skills-editor.blade.php`
- `tests/Feature/Livewire/Admin/UserSkillsManagerTest.php` - 11 tests
- `tests/Feature/Livewire/Admin/UserSkillsEditorTest.php` - 14 tests

### UX Decisions & Iterations

**Started with a modal, switched to a dedicated page**
- First implementation had a "Manage" button that opened a flyout modal
- User feedback: clicking a name should go to a page with the familiar skills UI
- Refactored to reuse SkillsEditor component - much cleaner, consistent UX

**Admin context awareness in SkillsEditor**
- Added `isAdminContext` computed property (checks if `userId !== Auth::id()`)
- Hides "Suggest Skill" button when admin is viewing another user
- Toggle label changes from "my skills" to "their skills"
- Toggle defaults to ON in admin context (show what the user has, not everything)

**"None" radio button selection**
- User noticed that skills without a level didn't show "None" as selected
- Required pre-populating `userSkillLevels` array with "none" for all visible skills

### Technical Gotchas

**Livewire hydration with Eloquent models**
- First attempt: stored `public ?User $user` property
- Problem: Livewire's dehydration/hydration between requests wasn't reliable
- Tests passed but real usage had null user errors
- Fix: Store `public ?int $userId` instead, use computed property to load User
- Lesson: For Livewire properties that need to survive across requests, prefer IDs over models

**Passing parameters to nested Livewire components**
- Using `<livewire:skills-editor :user="$user" />` with a User model was unreliable
- Switched to `<livewire:skills-editor :user-id="$user->id" />` (passing the int)
- The mount signature changed from `?User $user = null` to `?int $userId = null`

**loadUserSkillLevels() evolution**
Three iterations to get it right:

1. First: Only loaded user's actual skill levels
   - Problem: "None" radio not selected for unassigned skills

2. Second: Pre-populated from `$this->skills` computed property
   - Problem: In admin context with toggle ON, only showed user's existing skills
   - Unassigned skills still didn't get "none" default

3. Third: Query all approved skills separately, merge with actual levels
   - Problem: Pending skills (like Rust) weren't included
   - User's pending skills showed with no level selected

4. Final solution:
```php
$actualLevels = $this->user->skills()->pluck(...);  // Includes pending
$approvedDefaults = Skill::approved()->pluck(...);  // All approved = "none"
$this->userSkillLevels = $actualLevels + $approvedDefaults;  // Merge
```
The `+` operator keeps left-side values for duplicate keys, so actual levels take precedence.

### What's Next

Created epic `skillsdb-74m` - Skills Matrix Report:
- `.1` - Skills matrix table/grid view (users × skills, colour-coded)
- `.2` - Filter by skills ("who knows Linux?")
- `.3` - Filter by users ("what can these people do?")
- `.4` - Excel export of matrix (supersedes old standalone export issue)

This completes Phase 1 core functionality. The matrix report is the last piece before moving to Phase 2 (API, gamification, etc.).

---

## 2026-01-10 - Skills Matrix & Filters

### What We Built

Skills Matrix page at `/admin/matrix`:
- Grid showing users (rows) × skills (columns) with colour-coded proficiency badges
- Rotated 45° column headers for space efficiency
- Multi-select filters for both skills and users
- Filters persist in URL for shareable links

New/modified files:
- `app/Livewire/Admin/SkillsMatrix.php` - full-page component with filter logic
- `resources/views/livewire/admin/skills-matrix.blade.php` - CSS Grid layout
- `tests/Feature/Livewire/Admin/SkillsMatrixTest.php` - 18 tests
- `routes/web.php` - added `/admin/matrix` route
- `resources/views/components/layouts/app.blade.php` - sidebar link

### Why CSS Grid Instead of flux:table

Started with `flux:table` but hit issues:
- Table expanded to full width even with `w-auto` class
- When filtering to few columns, the Name column stretched awkwardly
- Flux's table styling overrode our width constraints

Switched to CSS Grid with `inline-grid`:
```blade
<div class="inline-grid gap-px" style="grid-template-columns: auto repeat({{ $this->skills->count() }}, 3.5rem);">
```
- `inline-grid` only takes up as much width as needed
- `auto` for name column, fixed `3.5rem` for skill columns
- Dynamic column count via Blade interpolation in the style attribute

### The Rotated Headers Pattern

For space-efficient column headers:
```blade
<div class="h-32 relative">
    <div class="absolute bottom-2 left-4 origin-bottom-left -rotate-45 whitespace-nowrap text-sm font-medium">
        {{ $skill->name }}
    </div>
</div>
```
- `h-32` gives vertical space for the angled text
- `origin-bottom-left` sets rotation pivot point
- `left-4` positions text to align with the badge below (took a few iterations!)
- Parent cell needs `relative` for the absolute positioning

### Filter Behaviour Decision

Initial implementation only filtered columns (skills) - users without the selected skills still appeared as empty rows. This felt wrong.

Changed so skill filter also filters users:
```php
->when($this->selectedSkills, fn ($q) => $q->whereHas('skills', fn ($q) => $q->whereIn('skill_id', $this->selectedSkills)))
```
Now "filter by Git" means "show people who know Git" - much more intuitive.

### The #[Url] Browser History Gotcha

**Problem**: User selects a filter, then clears it, then hits browser refresh - the old filter reappears!

**Why**: Livewire uses `history.pushState()` by default. Each filter change pushes a new history entry. When you clear the filter, it pushes another entry. But the browser's refresh button sometimes uses a cached history state rather than the current URL.

**Fix**: Use `history: 'replace'` to replace the current entry instead of pushing:
```php
#[Url(except: '', history: 'replace')]
public array $selectedSkills = [];
```

The `except: ''` removes the query param when empty, and `history: 'replace'` prevents the stale history issue.

### Empty Cells Visual Treatment

Empty cells (user doesn't have this skill) initially showed nothing - conceptually correct but visually odd. Added a subtle zinc badge with a minus icon:
```blade
@else
    <flux:badge size="sm" color="zinc" icon="minus-circle"></flux:badge>
@endif
```
Makes the grid feel complete without drawing attention away from actual skill levels.

### Testing Computed Properties in Livewire

Can't use `->viewData('skills')` for Livewire computed properties - they're not passed as view data. Instead, access the component instance directly:
```php
$component = Livewire::actingAs($admin)
    ->test(SkillsMatrix::class)
    ->set('selectedSkills', [$docker->id]);

$skills = $component->instance()->skills;
expect($skills)->toHaveCount(1);
```

### Don't Fight Flux

Early implementation had lots of manual styling (`bg-zinc-50`, `border-zinc-200`, etc.) on a custom table. This violated the team convention to let Flux handle styling.

Lesson reinforced: only use positioning/layout classes (flex, grid, gap, padding, margin). Let Flux handle colours, borders, typography. When Flux components don't fit, use plain HTML but still avoid colour classes - the grid cells have no visual styling, just layout.

### What's Left

- `skillsdb-74m.4` - Excel export of matrix (last item in the epic)
- Then Phase 2 begins (gamification, API, etc.)

---

## 2026-01-10 - Excel Export & API Token Management

### What We Built

**Excel Export for Skills Matrix**
- Added export button to `/admin/matrix`
- Uses SimpleSpout to generate `.xlsx` files
- Respects current filters (skills and users)
- Downloads as `skills-matrix-YYYY-MM-DD.xlsx`

**API Token Management at `/admin/api-tokens`**
- Full CRUD for Sanctum personal access tokens
- Admins can see/delete all tokens across all users
- Create modal with name + optional expiry date
- Shows copyable token immediately after creation (only chance to see it!)
- Table shows: name, created by, created at, last used, expiry status

New files:
- `app/Livewire/Admin/ApiTokensManager.php`
- `resources/views/livewire/admin/api-tokens-manager.blade.php`
- `tests/Feature/Livewire/Admin/ApiTokensManagerTest.php` - 16 tests
- `database/migrations/2026_01_10_*_create_personal_access_tokens_table.php` (Sanctum)

Modified:
- `app/Models/User.php` - added `HasApiTokens` trait

### Flux Modal Patterns Have Changed

The old pattern used boolean properties:
```php
public bool $showModal = false;
// Then wire:model="showModal" on the modal
```

The new pattern uses named modals with triggers:
```blade
<flux:modal.trigger name="create-thing">
    <flux:button>Create</flux:button>
</flux:modal.trigger>

<flux:modal name="create-thing" variant="flyout" @close="resetForm">
    <!-- content -->
</flux:modal>
```

Benefits:
- No boolean property to manage
- `@close` event fires when modal closes (by any means)
- `Flux::modal('name')->close()` to close programmatically
- `<flux:modal.close>` wrapper for close buttons

### Flux Input Shorthand

Don't need `flux:field` + `flux:label` + `flux:error` boilerplate:
```blade
{{-- Old way --}}
<flux:field>
    <flux:label>Token Name</flux:label>
    <flux:input wire:model="tokenName" />
    <flux:error name="tokenName" />
</flux:field>

{{-- New way --}}
<flux:input wire:model="tokenName" label="Token Name" />
```

The shorthand handles the field wrapper, label, and error display automatically.

### Flux Date-Picker Min Value

The `min` attribute only accepts:
- A date string (`2026-01-15`)
- The literal string `"today"`

**Not valid**: `min="tomorrow"`

For "tomorrow or later", compute it dynamically:
```blade
<flux:date-picker :min="now()->addDay()->format('Y-m-d')" />
```

### Flux::toast() Signature Reminder

The `$text` parameter is required, even when using named parameters:
```php
// WRONG - will error
Flux::toast(heading: 'Done!', variant: 'success');

// RIGHT
Flux::toast(heading: 'Done!', text: '', variant: 'success');
```

### Issue Tracking Lesson

Started with issue "API endpoints (Sanctum)" but only built token management UI. Rather than stretching the issue to cover both:
- Renamed existing issue to "API Token Management UI" and closed it
- Created new issue for the actual endpoint work

Cleaner history, each issue represents one deliverable.

### What's Next

- `skillsdb-6jq.5` - GET /api/users endpoint (Sanctum-protected) - for PowerBI integration
- Phase 2 continues: notifications, gamification, skills coach

---

## 2026-01-10 - API Endpoint & Admin Notifications

### API Endpoint

Added `GET /api/users` endpoint for PowerBI integration:
- Sanctum-protected at `/api/users`
- Returns all users with their skills, categories, and proficiency levels
- Uses `UserResource` and `SkillResource` for clean JSON formatting
- Response includes both `level` (human-readable) and `level_value` (numeric) for flexibility

Added a dev API token to `TestDataSeeder` - prints to console when seeding so you can test immediately.

### Admin Notifications for Pending Skills

Daily digest email sent to admins at 8:30am listing any pending skills:
- `app/Mail/PendingSkillsDigest.php` - queued mailable (SMTP can be flaky)
- `resources/views/emails/pending-skills-digest.blade.php` - markdown template
- `app/Console/Commands/SendPendingSkillsDigest.php` - finds pending skills, emails admins
- Scheduled in `routes/console.php` via `Schedule::command()->dailyAt('08:30')`

Design decision: single daily digest rather than real-time notifications. Admins don't need instant alerts for skill suggestions - a morning summary is plenty. Reduces inbox noise.

To test manually: `php artisan skills:send-pending-digest`

### What's Left

Phase 2 nice-to-haves:
- Skills Coach (LLM-powered career suggestions)
- Gamification & engagement features

---

## 2026-01-11 - Gamification Foundation Work

### What We Built

Started the Gamification & Engagement epic with three foundational pieces:

**Level-up Animation**
- CSS animation triggers when a user selects "High" skill level
- Uses Alpine.js `x-effect` to detect when level *changes* to High (not on page load)
- Subtle scale + glow effect using official Sky Blue brand colour (R0 G83 B152)
- Animation defined in `resources/css/app.css`, triggered via `animate-level-up` class

**User Model Skill Stats Methods**
- `getSkillDistribution()` - returns `['low' => x, 'medium' => y, 'high' => z, 'total' => n]`
- `hasStaleSkills()` - true if not updated in >4 weeks (or never)
- `getLastUpdatedText()` - human-readable text, with `<em>never</em>` or `<em>ages ago</em>` for stale users

**Skill Model Trending Query**
- `Skill::getTrendingSkills(days: 30, limit: 5)` - returns skills recently added by users
- Each skill includes `recent_additions_count` attribute
- Useful for "Docker was added by 6 people this month" type insights

### Technical Notes

**SQLite HAVING Clause Limitation**
The initial `getTrendingSkills()` implementation used `->having('recent_additions_count', '>', 0)` which works in MySQL but fails in SQLite (used for tests). SQLite complains about HAVING on non-aggregate queries when using subquery counts via `withCount()`.

Fix: Filter in PHP after fetching:
```php
->get()
->filter(fn ($skill) => $skill->recent_additions_count > 0)
->sortByDesc('recent_additions_count')
->take($limit)
->values();
```

Not a performance concern for our small dataset.

**Alpine x-effect for Animation Triggers**
To only animate on *change* (not initial load), track previous value:
```blade
x-data="{ levelUp: false, prevLevel: null }"
x-effect="
    let level = $wire.userSkillLevels[{{ $skill->id }}];
    if (prevLevel !== null && prevLevel !== '3' && (level === '3' || level === 3)) {
        levelUp = true;
        setTimeout(() => levelUp = false, 700);
    }
    prevLevel = level;
"
```

### Design Decisions

**Dashboard Chart Strategy**
Discussed what charts to show and agreed on keeping it simple:
- User home page: personal skill count over time (sparkline)
- Admin view: team total over time + "hot skills this month"
- Deep analysis: leave for Excel export or PowerBI via API

Build simple first, let real usage guide what's worth investing in later.

### What's Next

- `skillsdb-ad6.3` - SkillsDashboard component (uses the foundation we just built)
- Then embed on home page and admin matrix
- Sparkline charts using Flux's `flux:chart` component

---

## 2026-01-11 - Skill History Tracking

### The Vision

Record every skill journey event - when skills were added, removed, or levelled up/down. This unlocks:
- Timeline/activity feed views
- Calendar visualizations
- Richer chart data based on actual events (not inferred from current state)
- Admin insights ("5 people levelled up this week")
- Celebratory messaging ("You reached High in Laravel on Dec 5th!")

The spec was written up in `SKILL_HISTORY.md` before implementation - worth reading if you need the full picture.

### What We Built

| Component | Purpose |
|-----------|---------|
| `SkillHistoryEvent` enum | Added, Removed, LevelledUp, LevelledDown - with label/icon/colour methods |
| `SkillHistory` model | The history record - links user, skill, event type, old/new levels, timestamp |
| `SkillUser` pivot model | Custom pivot for `skill_user` table - this is the magic bit |
| `SkillUserObserver` | Watches the pivot and auto-records history on created/updated/deleted |

The observer is registered via the `#[ObservedBy]` attribute on `SkillUser` - no manual registration needed.

### The Custom Pivot Model Pattern

This is the key architectural decision. Instead of the default anonymous pivot:

```php
// Before
return $this->belongsToMany(Skill::class)
    ->withPivot('level')
    ->withTimestamps();

// After
return $this->belongsToMany(Skill::class)
    ->using(SkillUser::class)  // <-- Custom pivot model
    ->withPivot('level')
    ->withTimestamps();
```

The `SkillUser` model extends `Pivot` (not `Model`) and has:
- `$incrementing = true` (pivots don't auto-increment by default)
- Cast for `level` to `SkillLevel` enum
- The `#[ObservedBy(SkillUserObserver::class)]` attribute

Now every `attach()`, `detach()`, and `updateExistingPivot()` fires observer events automatically. No changes needed to existing code that manipulates skills!

### Observer Gotchas

**The enum cast comparison issue**

First attempt at detecting level changes:
```php
$oldLevel = $skillUser->getOriginal('level');
$newLevel = $skillUser->level->value;
// Compare $newLevel > $oldLevel
```

Problem: `getOriginal()` returns the cast value (a `SkillLevel` enum), not the raw integer. Comparing an int to an enum gave wrong results.

Fix:
```php
$original = $skillUser->getOriginal('level');
$oldLevel = $original instanceof SkillLevel ? $original->value : (int) $original;
```

**History timestamps**

The `SkillHistory` model only has `created_at` (no `updated_at` - history records are immutable). We use `$timestamps = false` and set `created_at` in the `booted()` method:

```php
protected static function booted(): void
{
    static::creating(function (SkillHistory $history) {
        $history->created_at ??= now();
    });
}
```

The `??=` allows manual timestamp override (useful for seeding historical data).

### Breaking Change: Pivot Level is Now an Enum

Adding the `SkillUser` pivot model with a `level` cast means `$pivot->level` returns a `SkillLevel` enum, not an integer.

Had to update:
- `User::getSkillLevel()` - was calling `SkillLevel::from($pivot->level)`, now just returns `$pivot->level`
- `SkillResource` - same issue
- Several tests that expected `->toBe(3)` now need `->toBe(SkillLevel::High)`

If you're writing new code that touches `$pivot->level`, remember it's an enum!

### The Play Space

Created a quick "play space" at `/play` for experimenting with the new feature:
- `app/Livewire/PlaySpace.php` - dead simple component
- `resources/views/livewire/play-space.blade.php` - flux:table of all history
- Link in admin sidebar with a play icon

Title has an emoji (🎮) because sometimes you need a bit of joy. This is explicitly a throwaway page for experimenting - feel free to gut it and rebuild for whatever you're testing next.

### Seeder Behaviour

The `TestDataSeeder` already attaches skills at various timestamps and updates levels. With the observer now in place, history gets recorded automatically when seeding.

Note: The history `created_at` timestamps will be "now" (when you run the seeder), not the historical dates from `attachSkillAtTime()`. This is fine for testing - the structure is there, and in production the history will track real events.

### Testing Coverage

`tests/Feature/Models/SkillHistoryTest.php` covers:
- History recorded on skill add
- History recorded on level increase (LevelledUp)
- History recorded on level decrease (LevelledDown)
- History recorded on skill removal
- No history when level stays the same
- Bulk operations record individual events
- Model relationships work correctly
- User's `skillHistory` relationship returns events in order

### What This Enables (Future)

Now that we're recording events, we can build:
- **Timeline view** - Show a user's skill journey chronologically
- **Calendar heatmap** - GitHub-style activity visualization
- **Better charts** - Plot actual events, not just current state
- **Admin dashboard** - "5 people levelled up this week", "Docker is trending"
- **Achievements** - "First skill!", "10 skills milestone", "Levelled up 3 times this month"
- **Notifications** - "Congratulations on reaching High in PHP!"

The foundation is in place. Build what users actually want, not everything we *could* build.

### Files Reference

New:
- `app/Enums/SkillHistoryEvent.php`
- `app/Models/SkillHistory.php`
- `app/Models/SkillUser.php`
- `app/Observers/SkillUserObserver.php`
- `database/migrations/*_create_skill_histories_table.php`
- `database/factories/SkillHistoryFactory.php`
- `tests/Feature/Models/SkillHistoryTest.php`
- `app/Livewire/PlaySpace.php` (+ view)

Modified:
- `app/Models/User.php` - added `using(SkillUser::class)` + `skillHistory()` relationship
- `app/Models/Skill.php` - added `using(SkillUser::class)`
- `app/Http/Resources/SkillResource.php` - removed redundant `SkillLevel::from()` call

---

*Add new entries above this line*
