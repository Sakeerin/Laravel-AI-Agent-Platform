<?php

namespace App\Services\Tools\BuiltIn;

use App\Services\Tools\BaseTool;
use App\Services\Tools\ToolContext;

class CalculatorTool extends BaseTool
{
    public function name(): string
    {
        return 'calculator';
    }

    public function displayName(): string
    {
        return 'Calculator';
    }

    public function description(): string
    {
        return 'Perform mathematical calculations. Supports basic arithmetic, percentages, and common math functions.';
    }

    public function category(): string
    {
        return 'utility';
    }

    public function parametersSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'expression' => [
                    'type' => 'string',
                    'description' => 'A mathematical expression to evaluate, e.g. "2 * (3 + 4)" or "sqrt(144)"',
                ],
            ],
            'required' => ['expression'],
        ];
    }

    public function execute(array $arguments, ?ToolContext $context = null): array
    {
        $expression = $arguments['expression'] ?? '';

        if (empty($expression)) {
            return ['success' => false, 'result' => null, 'error' => 'Expression is required'];
        }

        if (preg_match('/[a-zA-Z_]\w*\s*\(/', $expression)) {
            $allowed = ['sqrt', 'abs', 'ceil', 'floor', 'round', 'sin', 'cos', 'tan',
                'log', 'log10', 'exp', 'pow', 'min', 'max', 'pi', 'M_PI'];
            preg_match_all('/([a-zA-Z_]\w*)\s*\(/', $expression, $matches);
            foreach ($matches[1] as $fn) {
                if (!in_array($fn, $allowed)) {
                    return ['success' => false, 'result' => null, 'error' => "Function '{$fn}' is not allowed"];
                }
            }
        }

        if (preg_match('/[\$`\\\\]|(\.\s*\.)/', $expression)) {
            return ['success' => false, 'result' => null, 'error' => 'Expression contains forbidden characters'];
        }

        try {
            $safeExpression = str_replace('^', '**', $expression);
            $result = eval("return (float)({$safeExpression});");

            if (!is_finite($result)) {
                return ['success' => false, 'result' => null, 'error' => 'Result is not a finite number'];
            }

            return [
                'success' => true,
                'result' => [
                    'expression' => $expression,
                    'result' => $result,
                ],
            ];
        } catch (\Throwable $e) {
            return ['success' => false, 'result' => null, 'error' => 'Invalid expression: ' . $e->getMessage()];
        }
    }
}
