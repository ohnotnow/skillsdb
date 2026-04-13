<?php

namespace App\Http\Resources;

use App\Models\Skill;
use App\Models\SkillUser;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Skill
 *
 * @property SkillUser $pivot
 */
class SkillResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $level = $this->whenPivotLoaded('skill_user', function () {
            return $this->pivot->level;
        });

        return [
            'id' => $this->id,
            'name' => $this->name,
            'category' => $this->category?->name,
            'level' => $level?->label(),
            'level_value' => $level?->value,
        ];
    }
}
