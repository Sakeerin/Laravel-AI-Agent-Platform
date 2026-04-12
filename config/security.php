<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Prompt injection mitigation
    |--------------------------------------------------------------------------
    |
    | First-line defense: block or sanitize user-supplied text before it is
    | stored and sent to the model. This is heuristic; combine with system
    | instructions in AgentOrchestrator and least-privilege tools.
    |
    */

    'prompt_injection' => [
        'enabled' => env('SECURITY_PROMPT_INJECTION_ENABLED', true),
        // block | sanitize | off
        'mode' => env('SECURITY_PROMPT_INJECTION_MODE', 'block'),
        // When true, append a short security line to the system prompt (defense in depth).
        'system_hardening' => env('SECURITY_SYSTEM_HARDENING', true),
    ],

];
