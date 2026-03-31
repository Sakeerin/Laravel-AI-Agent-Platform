<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;

class AnthropicProvider implements AIProvider
{
    private string $apiKey;
    private string $baseUrl = 'https://api.anthropic.com/v1';

    public function __construct(?string $apiKey = null)
    {
        $this->apiKey = $apiKey ?? config('services.anthropic.api_key', '');
    }

    public function chat(array $messages, string $model, array $options = []): array
    {
        $payload = $this->buildPayload($messages, $model, $options);

        $response = Http::withHeaders($this->headers())
            ->timeout(120)
            ->post("{$this->baseUrl}/messages", $payload);

        $response->throw();
        $data = $response->json();

        $textContent = '';
        $toolUseCalls = [];

        foreach ($data['content'] ?? [] as $block) {
            if ($block['type'] === 'text') {
                $textContent .= $block['text'];
            } elseif ($block['type'] === 'tool_use') {
                $toolUseCalls[] = [
                    'id' => $block['id'],
                    'name' => $block['name'],
                    'input' => $block['input'] ?? [],
                ];
            }
        }

        return [
            'content' => $textContent,
            'model' => $data['model'],
            'input_tokens' => $data['usage']['input_tokens'] ?? 0,
            'output_tokens' => $data['usage']['output_tokens'] ?? 0,
            'stop_reason' => $data['stop_reason'] ?? null,
            'tool_calls' => $toolUseCalls,
            'raw_content' => $data['content'] ?? [],
        ];
    }

    public function stream(array $messages, string $model, array $options = []): \Generator
    {
        $payload = $this->buildPayload($messages, $model, $options);
        $payload['stream'] = true;

        $response = Http::withHeaders($this->headers())
            ->withOptions(['stream' => true])
            ->timeout(120)
            ->post("{$this->baseUrl}/messages", $payload);

        $body = $response->getBody();
        $buffer = '';
        $inputTokens = 0;
        $outputTokens = 0;
        $currentToolUse = null;
        $toolJsonBuffer = '';
        $stopReason = null;

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
                    break 2;
                }

                $event = json_decode($json, true);
                if (!$event) {
                    continue;
                }

                $type = $event['type'] ?? '';

                if ($type === 'content_block_start') {
                    $block = $event['content_block'] ?? [];
                    if ($block['type'] === 'tool_use') {
                        $currentToolUse = [
                            'id' => $block['id'],
                            'name' => $block['name'],
                        ];
                        $toolJsonBuffer = '';
                    }
                } elseif ($type === 'content_block_delta') {
                    $delta = $event['delta'] ?? [];
                    if ($delta['type'] === 'text_delta' && isset($delta['text'])) {
                        yield [
                            'type' => 'text',
                            'content' => $delta['text'],
                        ];
                    } elseif ($delta['type'] === 'input_json_delta' && $currentToolUse) {
                        $toolJsonBuffer .= $delta['partial_json'] ?? '';
                    }
                } elseif ($type === 'content_block_stop') {
                    if ($currentToolUse) {
                        $currentToolUse['input'] = json_decode($toolJsonBuffer, true) ?? [];
                        yield [
                            'type' => 'tool_use',
                            'tool_call' => $currentToolUse,
                        ];
                        $currentToolUse = null;
                        $toolJsonBuffer = '';
                    }
                } elseif ($type === 'message_start' && isset($event['message']['usage'])) {
                    $inputTokens = $event['message']['usage']['input_tokens'] ?? 0;
                } elseif ($type === 'message_delta') {
                    if (isset($event['usage'])) {
                        $outputTokens = $event['usage']['output_tokens'] ?? 0;
                    }
                    $stopReason = $event['delta']['stop_reason'] ?? $stopReason;
                } elseif ($type === 'message_stop') {
                    yield [
                        'type' => 'done',
                        'input_tokens' => $inputTokens,
                        'output_tokens' => $outputTokens,
                        'stop_reason' => $stopReason,
                    ];
                }
            }
        }
    }

    public function models(): array
    {
        return [
            'claude-sonnet' => 'claude-sonnet-4-20250514',
            'claude-haiku' => 'claude-haiku-3-5-20241022',
            'claude-opus' => 'claude-opus-4-20250514',
        ];
    }

    public function name(): string
    {
        return 'anthropic';
    }

    private function resolveModel(string $model): string
    {
        return $this->models()[$model] ?? $model;
    }

    private function headers(): array
    {
        return [
            'x-api-key' => $this->apiKey,
            'anthropic-version' => '2023-06-01',
            'content-type' => 'application/json',
        ];
    }

    private function buildPayload(array $messages, string $model, array $options): array
    {
        $systemMessages = array_filter($messages, fn($m) => $m['role'] === 'system');
        $chatMessages = array_values(array_filter($messages, fn($m) => $m['role'] !== 'system'));

        $payload = [
            'model' => $this->resolveModel($model),
            'max_tokens' => $options['max_tokens'] ?? 4096,
            'messages' => $chatMessages,
        ];

        if (!empty($systemMessages)) {
            $payload['system'] = implode("\n", array_column($systemMessages, 'content'));
        }

        if (!empty($options['tools'])) {
            $payload['tools'] = $options['tools'];
        }

        if (isset($options['temperature'])) {
            $payload['temperature'] = $options['temperature'];
        }

        return $payload;
    }
}
