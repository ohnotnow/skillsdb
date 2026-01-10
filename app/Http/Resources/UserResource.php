<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'username' => $this->username,
            'full_name' => $this->full_name,
            'email' => $this->email,
            'is_admin' => $this->is_admin,
            'last_updated_skills_at' => $this->last_updated_skills_at,
            'skills' => SkillResource::collection($this->whenLoaded('skills')),
        ];
    }
}
