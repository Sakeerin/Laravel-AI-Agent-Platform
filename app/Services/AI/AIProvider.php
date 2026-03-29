<?php

namespace App\Services\AI;

interface AIProvider
{
    public function chat(array $messages, string $model, array $options = []): array;

    /**
     * @return \Generator<string>
     */
    public function stream(array $messages, string $model, array $options = []): \Generator;

    public function models(): array;

    public function name(): string;
}
