# SkillsDB

A database for tracking IT team members and their skills. Team members manage their own skills and proficiency levels, while admins control the global skills list and can view skills across the whole team.

## Features

### For Team Members
- View and search all approved skills
- Set proficiency levels (Low / Medium / High) for each skill
- Filter to show only your assigned skills
- Suggest new skills (pending admin approval, but usable immediately by the suggester)

### For Admins
- Manage the global skills list (create, edit, delete)
- Approve or reject suggested skills
- Manage skills for any user (useful for onboarding new starters)
- Export skills data to Excel

## Data Model

```
SkillCategory
├── id, name

Skill
├── id, name, description
├── skill_category_id (optional)
├── approved_by, approved_at (null = pending approval)

User
├── is_admin
├── last_updated_skills_at
└── skills (many-to-many with level: Low/Medium/High)
```

## Local Development

```bash
git clone https://github.com/UoGSoE/skillsdb.git
cd skillsdb
composer install
npm install
cp .env.example .env
lando start
lando artisan key:generate
lando artisan migrate
lando artisan db:seed --class=TestDataSeeder
npm run build
```

Test user: `admin2x` / `secret`

## Running Tests

```bash
php artisan test
```

## Blah


