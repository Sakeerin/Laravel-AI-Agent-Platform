<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;

class OpenAIProvider implements AIProvider
{
    private string $apiKey;
    private string $baseUrl = 'https://api.openai.com/v1';

    public function __construct(?string $apiKey = null)
    {
        $this->apiKey = $apiKey ?? config('services.openai.api_key', '');
    }

    public function chat(array $messages, string $model, array $options = []): array
    {
        $payload = $this->buildPayload($messages, $model, $options);

        $response = Http::withToken($this->apiKey)
            ->timeout(120)
            ->post("{$this->baseUrl}/chat/completions", $payload);

        $response->throw();
        $data = $response->json();

        return [
            'content' => $data['choices'][0]['message']['content'] ?? '',
            'model' => $data['model'],
            'input_tokens' => $data['usage']['prompt_tokens'] ?? 0,
            'output_tokens' => $data['usage']['completion_tokens'] ?? 0,
            'stop_reason' => $data['choices'][0]['finish_reason'] ?? null,
        ];
    }

    public function stream(array $messages, string $model, array $options = []): \Generator
    {
        $payload = $this->buildPayload($messages, $model, $options);
        $payload['stream'] = true;
        $payload['stream_options'] = ['include_usage' => true];

        $response = Http::withToken($this->apiKey)
            ->withOptions(['stream' => true])
            ->timeout(120)
            ->post("{$this->baseUrl}/chat/completions", $payload);

        $body = $response->getBody();
        $buffer = '';
        $inputTokens = 0;
        $outputTokens = 0;

        while (!$body->eof()) {
            $chunk = $body->read(1024);
            $buffer .= $chunk;

            while (($newlinePos = strpos($buffer, "\n")) !== false) {
                $line = substr($buffer, 0, $newlinePos);
                $buffer = substr($buffer, $newlinePos + 1);
                $line = trim($line);

                if (!str_starts_with($line, 'data: ')) {
                    continue;
                }

                $json = substr($line, 6);
                if ($json === '[DONE]') {
                    yield [
                        'type' => 'done',
                        'input_tokens' => $inputTokens,
                        'output_tokens' => $outputTokens,
                    ];
                    break 2;
                }

                $event = json_decode($json, true);
                if (!$event) {
                    continue;
                }

                if (isset($event['usage'])) {
                    $inputTokens = $event['usage']['prompt_tokens'] ?? 0;
                    $outputTokens = $event['usage']['completion_tokens'] ?? 0;
                }

                $delta = $event['choices'][0]['delta'] ?? [];
                if (isset($delta['content']) && $delta['content'] !== '') {
                    yield [
                        'type' => 'text',
                        'content' => $delta['content'],
                    ];
                }
            }
        }
    }

    public function models(): array
    {
        return [
            'gpt-4o' => 'gpt-4o',
            'gpt-4o-mini' => 'gpt-4o-mini',
            'gpt-4-turbo' => 'gpt-4-turbo',
        ];
    }

    public function name(): string
    {
        return 'openai';
    }

    private function resolveModel(string $model): string
    {
        return $this->models()[$model] ?? $model;
    }

    private function buildPayload(array $messages, string $model, array $options): array
    {
        return [
            'model' => $this->resolveModel($model),
            'messages' => $messages,
            'max_tokens' => $options['max_tokens'] ?? 4096,
            'temperature' => $options['temperature'] ?? 0.7,
        ];
    }
}
