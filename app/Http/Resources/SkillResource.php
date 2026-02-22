<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Skill
 *
 * @property \App\Models\SkillUser $pivot
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
