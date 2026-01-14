<?php

namespace App\Livewire;

use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class SkillsCoach extends Component
{
    public string $prompt = '';

    public array $messages = [];

    public function send(): void
    {
        $this->validate([
            'prompt' => ['required', 'string', 'max:1000'],
        ]);

        $this->messages[] = [
            'role' => 'user',
            'content' => $this->prompt,
        ];

        $this->messages[] = [
            'role' => 'assistant',
            'content' => $this->generateFakeResponse(),
        ];

        $this->reset('prompt');
    }

    public function clearChat(): void
    {
        $this->reset('messages');
    }

    protected function generateFakeResponse(): string
    {
        $responses = [
            'Based on your current skills, you might want to explore some complementary technologies that could enhance your expertise.',
            "Great question! To level up in that area, I'd recommend focusing on hands-on practice and real-world projects.",
            'I notice you have some strong foundational skills. Have you considered building on those with more advanced techniques?',
            "That's an interesting area to explore. Many professionals find that combining theoretical knowledge with practical application works best.",
            "Good thinking! Skill development is a journey, and it's great that you're being proactive about your growth.",
        ];

        return $responses[array_rand($responses)];
    }

    public function render()
    {
        return view('livewire.skills-coach');
    }
}
