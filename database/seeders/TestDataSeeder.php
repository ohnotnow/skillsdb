<?php

namespace Database\Seeders;

use App\Enums\SkillLevel;
use App\Models\Skill;
use App\Models\SkillCategory;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class TestDataSeeder extends Seeder
{
    public function run(): void
    {
        $adminUser = $this->createAdminUser();
        $categories = $this->createSkillCategories();
        $skills = $this->createSkills($adminUser, $categories);
        $users = $this->createTeamMembers();
        $this->assignSkillsToUsers(collect([$adminUser, ...$users]), $skills);
        $this->createApiToken($adminUser);
    }

    private function createAdminUser(): User
    {
        return User::factory()->create([
            'username' => 'admin2x',
            'email' => 'admin2x@example.test',
            'password' => 'secret',
            'is_admin' => true,
        ]);
    }

    private function createTeamMembers(): array
    {
        $users = [];

        // Create 3 more admins
        for ($i = 0; $i < 3; $i++) {
            $users[] = User::factory()->admin()->create();
        }

        // Create 26 standard users (total team of ~30)
        for ($i = 0; $i < 26; $i++) {
            $users[] = User::factory()->create();
        }

        return $users;
    }

    private function createSkillCategories(): array
    {
        return [
            'programming' => SkillCategory::create(['name' => 'Programming Languages']),
            'web' => SkillCategory::create(['name' => 'Web Development']),
            'databases' => SkillCategory::create(['name' => 'Databases']),
            'devops' => SkillCategory::create(['name' => 'DevOps & Infrastructure']),
            'cloud' => SkillCategory::create(['name' => 'Cloud Platforms']),
            'os' => SkillCategory::create(['name' => 'Operating Systems']),
            'networking' => SkillCategory::create(['name' => 'Networking & Security']),
            'research' => SkillCategory::create(['name' => 'Research Computing']),
            'data' => SkillCategory::create(['name' => 'Data & Analytics']),
            'university' => SkillCategory::create(['name' => 'University Systems']),
        ];
    }

    private function createSkills(User $adminUser, array $categories): array
    {
        $skillsData = [
            // Programming Languages
            'programming' => [
                ['name' => 'PHP', 'description' => 'Server-side scripting language for web development'],
                ['name' => 'Python', 'description' => 'General-purpose language popular in research and automation'],
                ['name' => 'JavaScript', 'description' => 'Client-side and server-side scripting language'],
                ['name' => 'TypeScript', 'description' => 'Typed superset of JavaScript'],
                ['name' => 'Java', 'description' => 'Object-oriented language for enterprise applications'],
                ['name' => 'C#', 'description' => 'Microsoft language for .NET applications'],
                ['name' => 'Bash/Shell', 'description' => 'Command-line scripting for automation'],
                ['name' => 'PowerShell', 'description' => 'Windows automation and configuration'],
                ['name' => 'R', 'description' => 'Statistical computing and graphics'],
            ],

            // Web Development
            'web' => [
                ['name' => 'Laravel', 'description' => 'PHP web application framework'],
                ['name' => 'Vue.js', 'description' => 'Progressive JavaScript framework'],
                ['name' => 'React', 'description' => 'JavaScript library for building user interfaces'],
                ['name' => 'Django', 'description' => 'Python web framework'],
                ['name' => 'Node.js', 'description' => 'JavaScript runtime for server-side development'],
                ['name' => 'HTML/CSS', 'description' => 'Web markup and styling'],
                ['name' => 'Tailwind CSS', 'description' => 'Utility-first CSS framework'],
                ['name' => 'REST API Design', 'description' => 'Designing and implementing RESTful APIs'],
            ],

            // Databases
            'databases' => [
                ['name' => 'MySQL/MariaDB', 'description' => 'Open source relational database'],
                ['name' => 'PostgreSQL', 'description' => 'Advanced open source relational database'],
                ['name' => 'Oracle', 'description' => 'Enterprise relational database'],
                ['name' => 'SQL Server', 'description' => 'Microsoft relational database'],
                ['name' => 'MongoDB', 'description' => 'NoSQL document database'],
                ['name' => 'Redis', 'description' => 'In-memory data structure store'],
            ],

            // DevOps & Infrastructure
            'devops' => [
                ['name' => 'Git', 'description' => 'Distributed version control system'],
                ['name' => 'Docker', 'description' => 'Container platform for application deployment'],
                ['name' => 'Kubernetes', 'description' => 'Container orchestration platform'],
                ['name' => 'Ansible', 'description' => 'IT automation and configuration management'],
                ['name' => 'Terraform', 'description' => 'Infrastructure as code'],
                ['name' => 'CI/CD Pipelines', 'description' => 'Continuous integration and deployment'],
                ['name' => 'GitLab', 'description' => 'DevOps platform for version control and CI/CD'],
                ['name' => 'Jenkins', 'description' => 'Automation server for CI/CD'],
            ],

            // Cloud Platforms
            'cloud' => [
                ['name' => 'AWS', 'description' => 'Amazon Web Services cloud platform'],
                ['name' => 'Azure', 'description' => 'Microsoft cloud platform'],
                ['name' => 'Google Cloud', 'description' => 'Google Cloud Platform services'],
                ['name' => 'Jisc Cloud', 'description' => 'UK education sector cloud services'],
            ],

            // Operating Systems
            'os' => [
                ['name' => 'Linux Administration', 'description' => 'Managing Linux servers (RHEL, Ubuntu, etc.)'],
                ['name' => 'Windows Server', 'description' => 'Microsoft server administration'],
                ['name' => 'Active Directory', 'description' => 'Microsoft directory services'],
                ['name' => 'VMware', 'description' => 'Virtualisation platform administration'],
            ],

            // Networking & Security
            'networking' => [
                ['name' => 'Network Administration', 'description' => 'Managing network infrastructure'],
                ['name' => 'Firewalls', 'description' => 'Network security and firewall management'],
                ['name' => 'VPN', 'description' => 'Virtual private network configuration'],
                ['name' => 'SSL/TLS', 'description' => 'Certificate management and encryption'],
                ['name' => 'LDAP', 'description' => 'Directory services integration'],
                ['name' => 'OAuth/SAML', 'description' => 'Single sign-on and identity federation'],
            ],

            // Research Computing
            'research' => [
                ['name' => 'HPC', 'description' => 'High-performance computing clusters'],
                ['name' => 'MATLAB', 'description' => 'Numerical computing environment'],
                ['name' => 'Jupyter', 'description' => 'Interactive computing notebooks'],
                ['name' => 'Slurm', 'description' => 'HPC workload manager'],
                ['name' => 'Research Data Management', 'description' => 'Managing and curating research data'],
            ],

            // Data & Analytics
            'data' => [
                ['name' => 'Power BI', 'description' => 'Microsoft business intelligence tool'],
                ['name' => 'Tableau', 'description' => 'Data visualisation platform'],
                ['name' => 'SQL', 'description' => 'Database query language'],
                ['name' => 'ETL Processes', 'description' => 'Extract, transform, load data pipelines'],
            ],

            // University Systems
            'university' => [
                ['name' => 'Moodle', 'description' => 'Virtual learning environment'],
                ['name' => 'Student Records Systems', 'description' => 'Student information management'],
                ['name' => 'Timetabling Systems', 'description' => 'Academic scheduling software'],
                ['name' => 'Library Systems', 'description' => 'Library management systems integration'],
            ],
        ];

        $skills = [];

        foreach ($skillsData as $categoryKey => $categorySkills) {
            foreach ($categorySkills as $skillData) {
                $key = strtolower(str_replace(['/', ' ', '.'], ['_', '_', ''], $skillData['name']));
                $skills[$key] = Skill::factory()->approved($adminUser)->create([
                    'name' => $skillData['name'],
                    'description' => $skillData['description'],
                    'skill_category_id' => $categories[$categoryKey]->id,
                ]);
            }
        }

        // Add a couple of pending skills (suggested but not approved)
        $skills['rust'] = Skill::factory()->pending()->create([
            'name' => 'Rust',
            'description' => 'Systems programming language with memory safety',
            'skill_category_id' => $categories['programming']->id,
        ]);
        $skills['go'] = Skill::factory()->pending()->create([
            'name' => 'Go',
            'description' => 'Google language for scalable services',
            'skill_category_id' => $categories['programming']->id,
        ]);

        return $skills;
    }

    private function assignSkillsToUsers($users, array $skills): void
    {
        $skillKeys = array_keys($skills);
        $realNow = now();

        foreach ($users as $user) {
            // Each user gets between 5 and 15 skills
            $numSkills = rand(5, 15);
            $userSkillKeys = collect($skillKeys)
                ->shuffle()
                ->take($numSkills);

            foreach ($userSkillKeys as $skillKey) {
                $skill = $skills[$skillKey];

                // Random initial level weighted towards Low/Medium
                $initialLevel = $this->randomWeightedLevel();

                // Random time in the past 12 months
                $addedAt = $realNow->copy()->subDays(rand(30, 365));

                $this->attachSkillAtTime($user, $skill, $initialLevel, $addedAt);

                // 30% chance of having levelled up since adding
                if (rand(1, 100) <= 30 && $initialLevel !== SkillLevel::High) {
                    $newLevel = $initialLevel === SkillLevel::Low ? SkillLevel::Medium : SkillLevel::High;
                    $upgradedAt = $addedAt->copy()->addDays(rand(30, 180));
                    if ($upgradedAt->lt($realNow)) {
                        $this->updateSkillAtTime($user, $skill, $newLevel, $upgradedAt);
                    }
                }
            }

            // Random last updated timestamp in last 6 months
            Carbon::setTestNow($realNow->copy()->subDays(rand(1, 180)));
            $user->touchSkillsUpdatedAt();
            Carbon::setTestNow();
        }
    }

    private function randomWeightedLevel(): SkillLevel
    {
        $rand = rand(1, 100);
        if ($rand <= 40) {
            return SkillLevel::Low;
        }
        if ($rand <= 80) {
            return SkillLevel::Medium;
        }

        return SkillLevel::High;
    }

    private function updateSkillAtTime(User $user, Skill $skill, SkillLevel $level, $timestamp): void
    {
        Carbon::setTestNow($timestamp);
        $user->skills()->updateExistingPivot($skill->id, ['level' => $level->value]);
        Carbon::setTestNow();
    }

    private function attachSkillAtTime(User $user, Skill $skill, SkillLevel $level, $timestamp): void
    {
        Carbon::setTestNow($timestamp);
        $user->skills()->attach($skill->id, [
            'level' => $level->value,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);
        Carbon::setTestNow();
    }

    private function createApiToken(User $user): void
    {
        $token = $user->createToken('dev-token');

        $this->command->info('');
        $this->command->info('API Token for '.$user->username.':');
        $this->command->info($token->plainTextToken);
        $this->command->info('');
    }
}
