<?php

namespace App\Jobs;

use App\Models\Conversation;
use App\Services\Memory\MemoryExtractionService;
use Illuminate\Foundation\Queue\Queueable;

class ExtractMemoriesFromConversationJob
{
    use Queueable;

    public function __construct(public int $conversationId) {}

    public function handle(MemoryExtractionService $extraction): void
    {
        $conversation = Conversation::find($this->conversationId);
        if ($conversation) {
            $extraction->extractAndStore($conversation);
        }
    }
}
