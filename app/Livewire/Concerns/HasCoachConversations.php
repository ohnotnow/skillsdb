<?php

namespace App\Livewire\Concerns;

use App\Models\AgentConversation;
use Livewire\Attributes\On;

trait HasCoachConversations
{
    // Components using this trait must define these properties:
    // protected string $exportPrefix = 'coach-chat-';
    // protected string $exportTitle = 'Skills Coach Conversation';

    #[On('conversation-selected')]
    public function switchConversation(string $conversationId): void
    {
        $this->conversationId = $conversationId;
        $this->loadConversation();
    }

    #[On('conversation-deleted-active')]
    public function handleActiveConversationDeleted(): void
    {
        $this->conversationId = null;
        $this->reset('messages');
    }

    public function exportConversation(string $format): mixed
    {
        if (! $this->conversationId) {
            return null;
        }

        $conversation = auth()->user()->agentConversations()
            ->with('messages')
            ->findOrFail($this->conversationId);

        $filename = $this->exportPrefix.$conversation->created_at->format('Y-m-d');

        if ($format === 'json') {
            return $this->exportAsJson($conversation, $filename);
        }

        return $this->exportAsMarkdown($conversation, $filename);
    }

    protected function exportAsJson(AgentConversation $conversation, string $filename): mixed
    {
        $data = [
            'exported_at' => now()->toIso8601String(),
            'conversation' => $this->getJsonConversationData($conversation),
        ];

        return response()->streamDownload(function () use ($data) {
            echo json_encode($data, JSON_PRETTY_PRINT);
        }, $filename.'.json', ['Content-Type' => 'application/json']);
    }

    protected function getJsonConversationData(AgentConversation $conversation): array
    {
        return [
            'id' => $conversation->id,
            'created_at' => $conversation->created_at->toIso8601String(),
            'messages' => $conversation->messages
                ->whereIn('role', ['user', 'assistant'])
                ->map(fn ($m) => [
                    'role' => $m->role,
                    'content' => $m->content,
                    'created_at' => $m->created_at->toIso8601String(),
                ])->values()->toArray(),
        ];
    }

    protected function exportAsMarkdown(AgentConversation $conversation, string $filename): mixed
    {
        $content = $this->getMarkdownHeader($conversation);

        foreach ($conversation->messages->whereIn('role', ['user', 'assistant']) as $message) {
            $speaker = $message->role === 'user' ? '**You**' : '**Coach**';
            $content .= $speaker.' ('.$message->created_at->format('g:ia')."):\n\n";
            $content .= $message->content."\n\n";
        }

        return response()->streamDownload(function () use ($content) {
            echo $content;
        }, $filename.'.md', ['Content-Type' => 'text/markdown']);
    }

    protected function getMarkdownHeader(AgentConversation $conversation): string
    {
        return "# {$this->exportTitle}\n\n"
            .'Exported: '.now()->format('F j, Y g:ia')."\n"
            .'Started: '.$conversation->created_at->format('F j, Y g:ia')."\n\n"
            ."---\n\n";
    }
}
