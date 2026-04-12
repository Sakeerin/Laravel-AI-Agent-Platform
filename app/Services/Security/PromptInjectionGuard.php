<?php

namespace App\Services\Security;

/**
 * Heuristic prompt-injection filter. Not a substitute for model-level safety
 * policies or tool sandboxing.
 */
final class PromptInjectionGuard
{
    /** @var list<string> Lowercase needles */
    private const NEEDLES = [
        'ignore previous instructions',
        'ignore all previous',
        'disregard previous',
        'disregard all previous',
        'you are now in developer mode',
        'you are now developer mode',
        'new instructions:',
        'reveal your system prompt',
        'reveal the system prompt',
        'repeat your system prompt',
        'repeat the system prompt',
        'jailbreak',
        'dan mode',
        'bypass your guidelines',
        'bypass safety',
        '[inst]',
        '<<sys>>',
        '</s>',
        'override your instructions',
    ];

    public function __construct(
        private readonly bool $enabled,
        private readonly string $mode,
    ) {}

    public static function fromConfig(): self
    {
        $cfg = config('security.prompt_injection', []);

        return new self(
            (bool) ($cfg['enabled'] ?? true),
            is_string($cfg['mode'] ?? null) ? $cfg['mode'] : 'block',
        );
    }

    /**
     * @return array{allowed: bool, content: string, reason: string|null}
     */
    public function check(string $text): array
    {
        if (! $this->enabled || $this->mode === 'off') {
            return ['allowed' => true, 'content' => $text, 'reason' => null];
        }

        $lower = mb_strtolower($text);
        $hit = null;
        foreach (self::NEEDLES as $needle) {
            if (str_contains($lower, $needle)) {
                $hit = $needle;
                break;
            }
        }

        if ($hit === null) {
            return ['allowed' => true, 'content' => $text, 'reason' => null];
        }

        if ($this->mode === 'sanitize') {
            // Remove the first matching phrase (case-insensitive, once per needle occurrence is expensive; strip substring)
            $sanitized = $text;
            $pattern = '/'.preg_quote($hit, '/').'/iu';
            $sanitized = (string) preg_replace($pattern, '[filtered]', $sanitized, 1);

            return [
                'allowed' => true,
                'content' => $sanitized,
                'reason' => 'sanitized',
            ];
        }

        return [
            'allowed' => false,
            'content' => $text,
            'reason' => 'blocked_pattern',
        ];
    }
}
