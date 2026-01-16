<?php

namespace App\Services\SkillsCoach\Contracts;

use App\Models\User;

interface LlmProvider
{
    /**
     * Send a message to the LLM and get a response.
     *
     * @param  string  $userMessage  The user's message
     * @param  array  $conversationHistory  Previous messages [{role: 'user'|'assistant', content: string}]
     * @param  User  $user  The current user (for context)
     * @return string The assistant's response
     */
    public function chat(string $userMessage, array $conversationHistory, User $user): string;
}
