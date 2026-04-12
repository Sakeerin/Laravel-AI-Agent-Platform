<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Estimated USD per 1M tokens (for dashboards; not billing-grade)
    |--------------------------------------------------------------------------
    |
    | Keys are model id strings as used in chat (prefix or full id). First
    | matching prefix wins. Ollama defaults to zero.
    |
    */

    'models' => [
        'claude' => ['input_per_million' => 3.0, 'output_per_million' => 15.0],
        'gpt-4o' => ['input_per_million' => 2.5, 'output_per_million' => 10.0],
        'gpt-4o-mini' => ['input_per_million' => 0.15, 'output_per_million' => 0.6],
        'gpt-3.5' => ['input_per_million' => 0.5, 'output_per_million' => 1.5],
        'text-embedding' => ['input_per_million' => 0.02, 'output_per_million' => 0.0],
        'ollama' => ['input_per_million' => 0.0, 'output_per_million' => 0.0],
        'default' => ['input_per_million' => 2.0, 'output_per_million' => 8.0],
    ],

];
