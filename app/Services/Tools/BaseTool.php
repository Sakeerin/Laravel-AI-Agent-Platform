<?php

namespace App\Services\Tools;

abstract class BaseTool
{
    abstract public function name(): string;

    abstract public function displayName(): string;

    abstract public function description(): string;

    abstract public function category(): string;

    /**
     * JSON Schema for the tool's input parameters.
     */
    abstract public function parametersSchema(): array;

    /**
     * Execute the tool with the given arguments.
     *
     * @return array{success: bool, result: mixed, error?: string}
     */
    abstract public function execute(array $arguments, ?ToolContext $context = null): array;

    public function timeoutSeconds(): int
    {
        return 30;
    }

    public function requiresApproval(): bool
    {
        return false;
    }

    /**
     * Convert to Anthropic tool_use format.
     */
    public function toToolDefinition(): array
    {
        return [
            'name' => $this->name(),
            'description' => $this->description(),
            'input_schema' => $this->parametersSchema(),
        ];
    }
}
