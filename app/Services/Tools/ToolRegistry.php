<?php

namespace App\Services\Tools;

use App\Models\Skill;
use InvalidArgumentException;

class ToolRegistry
{
    /** @var array<string, BaseTool> */
    private array $tools = [];

    public function register(BaseTool $tool): void
    {
        $this->tools[$tool->name()] = $tool;
    }

    public function get(string $name): BaseTool
    {
        if (!isset($this->tools[$name])) {
            throw new InvalidArgumentException("Tool [{$name}] is not registered.");
        }

        return $this->tools[$name];
    }

    public function has(string $name): bool
    {
        return isset($this->tools[$name]);
    }

    /**
     * @return array<string, BaseTool>
     */
    public function all(): array
    {
        return $this->tools;
    }

    /**
     * Get only enabled tools (checking DB skill records).
     *
     * @return array<string, BaseTool>
     */
    public function enabled(): array
    {
        $disabledSkills = Skill::where('is_enabled', false)
            ->pluck('name')
            ->toArray();

        return array_filter(
            $this->tools,
            fn(BaseTool $tool) => !in_array($tool->name(), $disabledSkills)
        );
    }

    /**
     * Get tool definitions for AI function calling.
     */
    public function getToolDefinitions(): array
    {
        return array_values(
            array_map(
                fn(BaseTool $tool) => $tool->toToolDefinition(),
                $this->enabled()
            )
        );
    }

    /**
     * Sync all registered tools to the database skills table.
     */
    public function syncToDatabase(): void
    {
        foreach ($this->tools as $tool) {
            Skill::updateOrCreate(
                ['name' => $tool->name()],
                [
                    'display_name' => $tool->displayName(),
                    'description' => $tool->description(),
                    'parameters_schema' => $tool->parametersSchema(),
                    'category' => $tool->category(),
                    'is_system' => true,
                    'timeout_seconds' => $tool->timeoutSeconds(),
                    'requires_approval' => $tool->requiresApproval(),
                ]
            );
        }
    }
}
