<?php

namespace Tests\Feature;

use Tests\TestCase;

class MemoryApiTest extends TestCase
{
    public function test_memories_index_requires_authentication(): void
    {
        $this->getJson('/api/memories')->assertUnauthorized();
    }

    public function test_agent_settings_requires_authentication(): void
    {
        $this->getJson('/api/agent-settings')->assertUnauthorized();
    }
}
