<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;

class OllamaProvider implements AIProvider
{
    private string $host;

    public function __construct(?string $host = null)
    {
        $this->host = rtrim($host ?? config('services.ollama.host', 'http://localhost:11434'), '/');
    }

    public function chat(array $messages, string $model, array $options = []): array
    {
        $response = Http::timeout(300)
            ->post("{$this->host}/api/chat", [
                'model' => $model,
                'messages' => $messages,
                'stream' => false,
                'options' => array_filter([
                    'temperature' => $options['temperature'] ?? null,
                    'num_predict' => $options['max_tokens'] ?? null,
                ]),
            ]);

        $response->throw();
        $data = $response->json();

        return [
            'content' => $data['message']['content'] ?? '',
            'model' => $data['model'],
            'input_tokens' => $data['prompt_eval_count'] ?? 0,
            'output_tokens' => $data['eval_count'] ?? 0,
            'stop_reason' => 'stop',
        ];
    }

    public function stream(array $messages, string $model, array $options = []): \Generator
    {
        $response = Http::withOptions(['stream' => true])
            ->timeout(300)
            ->post("{$this->host}/api/chat", [
                'model' => $model,
                'messages' => $messages,
                'stream' => true,
                'options' => array_filter([
                    'temperature' => $options['temperature'] ?? null,
                    'num_predict' => $options['max_tokens'] ?? null,
                ]),
            ]);

        $body = $response->getBody();
        $buffer = '';
        $inputTokens = 0;
        $outputTokens = 0;

        while (! $body->eof()) {
            $chunk = $body->read(1024);
            $buffer .= $chunk;

            while (($newlinePos = strpos($buffer, "\n")) !== false) {
                $line = substr($buffer, 0, $newlinePos);
                $buffer = substr($buffer, $newlinePos + 1);
                $line = trim($line);

                if (empty($line)) {
                    continue;
                }

                $event = json_decode($line, true);
                if (! $event) {
                    continue;
                }

                if (! ($event['done'] ?? false)) {
                    yield [
                        'type' => 'text',
                        'content' => $event['message']['content'] ?? '',
                    ];
                } else {
                    $inputTokens = $event['prompt_eval_count'] ?? 0;
                    $outputTokens = $event['eval_count'] ?? 0;
                    yield [
                        'type' => 'done',
                        'input_tokens' => $inputTokens,
                        'output_tokens' => $outputTokens,
                    ];
                }
            }
        }
    }

    public function models(): array
    {
        try {
            $response = Http::timeout(5)->get("{$this->host}/api/tags");
            $models = $response->json('models', []);
            $result = [];
            foreach ($models as $m) {
                $result[$m['name']] = $m['name'];
            }

            return $result;
        } catch (\Exception) {
            return [];
        }
    }

    public function name(): string
    {
        return 'ollama';
    }

    /**
     * @return list<float>
     */
    public function createEmbedding(string $text, string $model = 'nomic-embed-text'): array
    {
        $response = Http::timeout(120)
            ->post("{$this->host}/api/embeddings", [
                'model' => $model,
                'prompt' => $text,
            ]);

        $response->throw();
        $embedding = $response->json('embedding');
        if (! is_array($embedding)) {
            throw new \RuntimeException('Invalid Ollama embedding response.');
        }

        return array_map(static fn ($v) => (float) $v, $embedding);
    }
}
