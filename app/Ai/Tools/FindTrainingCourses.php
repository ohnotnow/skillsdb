<?php

namespace App\Ai\Tools;

use App\Enums\EnrollmentStatus;
use App\Models\Skill;
use App\Models\TrainingCourse;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

class FindTrainingCourses implements Tool
{
    public function description(): string
    {
        return 'Find available training courses. Can search by skill name, keyword, or browse all courses. Returns cost, supplier, ratings from people who completed the course, and whether it offers certification.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'skill_name' => $schema->string(),
            'query' => $schema->string(),
        ];
    }

    public function handle(Request $request): string
    {
        $skillName = $request['skill_name'] ?? null;
        $query = $request['query'] ?? null;

        if ($skillName) {
            return $this->findBySkill($skillName);
        }

        if ($query) {
            return $this->searchCourses($query);
        }

        return $this->allActiveCourses();
    }

    protected function findBySkill(string $skillName): string
    {
        $skill = Skill::where('name', 'like', "%{$skillName}%")
            ->approved()
            ->first();

        if (! $skill) {
            return json_encode([
                'found' => false,
                'message' => "No skill matching '{$skillName}' found in the system.",
            ]);
        }

        $courses = $skill->trainingCourses()
            ->active()
            ->withRatingCounts()
            ->withCount(['users as completed_count' => fn ($q) => $q->where('status', EnrollmentStatus::Completed)])
            ->with('supplier')
            ->get();

        if ($courses->isEmpty()) {
            return json_encode([
                'skill' => $skill->name,
                'courses' => [],
                'message' => "No training courses are currently available for {$skill->name}.",
            ]);
        }

        return json_encode([
            'skill' => $skill->name,
            'courses' => $courses->map(fn ($c) => $this->formatCourse($c))->toArray(),
            'count' => $courses->count(),
        ], JSON_PRETTY_PRINT);
    }

    protected function searchCourses(string $query): string
    {
        $courses = TrainingCourse::active()
            ->withRatingCounts()
            ->withCount(['users as completed_count' => fn ($q) => $q->where('status', EnrollmentStatus::Completed)])
            ->with('supplier', 'skills')
            ->where(fn ($q) => $q->where('name', 'like', "%{$query}%")->orWhere('description', 'like', "%{$query}%"))
            ->limit(15)
            ->get();

        if ($courses->isEmpty()) {
            return json_encode([
                'query' => $query,
                'courses' => [],
                'message' => "No training courses matching '{$query}' found.",
            ]);
        }

        return json_encode([
            'query' => $query,
            'courses' => $courses->map(fn ($c) => $this->formatCourse($c))->toArray(),
            'count' => $courses->count(),
        ], JSON_PRETTY_PRINT);
    }

    protected function allActiveCourses(): string
    {
        $courses = TrainingCourse::active()
            ->withRatingCounts()
            ->withCount(['users as completed_count' => fn ($q) => $q->where('status', EnrollmentStatus::Completed)])
            ->with('supplier', 'skills')
            ->limit(15)
            ->get();

        if ($courses->isEmpty()) {
            return json_encode([
                'courses' => [],
                'message' => 'No training courses are currently available.',
            ]);
        }

        return json_encode([
            'courses' => $courses->map(fn ($c) => $this->formatCourse($c))->toArray(),
            'count' => $courses->count(),
        ], JSON_PRETTY_PRINT);
    }

    protected function formatCourse(TrainingCourse $course): array
    {
        $totalRatings = ($course->good_count ?? 0) + ($course->indifferent_count ?? 0) + ($course->bad_count ?? 0);

        $entry = [
            'name' => $course->name,
            'cost' => $course->isFree() ? 'Free' : $course->cost,
            'offers_certification' => $course->offers_certification,
            'supplier' => $course->supplier?->name ?? 'Unknown',
            'supplier_website' => $course->supplier?->website,
            'completed_by' => $course->completed_count ?? 0,
        ];

        if ($totalRatings > 0) {
            $entry['ratings'] = [
                'good' => $course->good_count ?? 0,
                'indifferent' => $course->indifferent_count ?? 0,
                'bad' => $course->bad_count ?? 0,
                'total' => $totalRatings,
            ];
        }

        if ($course->description) {
            $entry['description'] = $course->description;
        }

        if ($course->prerequisites) {
            $entry['prerequisites'] = $course->prerequisites;
        }

        if ($course->relationLoaded('skills') && $course->skills->isNotEmpty()) {
            $entry['related_skills'] = $course->skills->pluck('name')->toArray();
        }

        return $entry;
    }
}
