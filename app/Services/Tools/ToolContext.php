<?php

namespace App\Services\Tools;

use App\Models\Conversation;
use App\Models\User;

class ToolContext
{
    public function __construct(
        public readonly User $user,
        public readonly ?Conversation $conversation = null,
        public readonly ?string $sandboxPath = null,
    ) {}

    public function getSandboxPath(): string
    {
        if ($this->sandboxPath) {
            return $this->sandboxPath;
        }

        $path = storage_path("app/sandbox/user_{$this->user->id}");

        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }

        return $path;
    }
}
