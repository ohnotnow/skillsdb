<?php

namespace App\Services\SkillsCoach;

use App\Enums\CoachMessageRole;
use App\Models\CoachConversation;
use App\Models\CoachMessage;
use App\Models\User;
use App\Services\SkillsCoach\Contracts\LlmProvider;

class CoachService
{
    public function __construct(
        protected LlmProvider $provider
    ) {}

    /**
     * Send a message and get a response, persisting both to the conversation.
     */
    public function chat(User $user, string $message, ?CoachConversation $conversation = null): CoachMessage
    {
        $conversation ??= $this->getOrCreateConversation($user);

        // Save the user's message
        $conversation->messages()->create([
            'role' => CoachMessageRole::User,
            'content' => $message,
        ]);

        // Get conversation history for context
        $history = $this->buildConversationHistory($conversation);

        // Get response from LLM
        $responseText = $this->provider->chat($message, $history, $user);

        // Save and return the assistant's response
        return $conversation->messages()->create([
            'role' => CoachMessageRole::Assistant,
            'content' => $responseText,
        ]);
    }

    /**
     * Get the user's current conversation, or create a new one.
     */
    public function getOrCreateConversation(User $user): CoachConversation
    {
        return $user->coachConversations()->firstOrCreate(['user_id' => $user->id]);
    }

    /**
     * Start a fresh conversation for the user.
     */
    public function startNewConversation(User $user): CoachConversation
    {
        return $user->coachConversations()->create();
    }

    /**
     * Build conversation history array for the LLM.
     *
     * @return array<int, array{role: string, content: string}>
     */
    protected function buildConversationHistory(CoachConversation $conversation): array
    {
        return $conversation->messages()
            ->oldest()
            ->get()
            ->map(fn (CoachMessage $msg) => [
                'role' => $msg->role->value,
                'content' => $msg->content,
            ])
            ->toArray();
    }
}
