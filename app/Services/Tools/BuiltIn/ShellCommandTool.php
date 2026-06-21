<?php

namespace App\Services\Tools\BuiltIn;

use App\Services\Tools\BaseTool;
use App\Services\Tools\ToolContext;
use Symfony\Component\Process\Process;

class ShellCommandTool extends BaseTool
{
    private const ALLOWED_COMMANDS = [
        'ls', 'dir', 'cat', 'head', 'tail', 'wc', 'grep', 'find', 'echo',
        'date', 'whoami', 'pwd', 'which', 'env',
        'php', 'node', 'python', 'python3', 'pip', 'npm', 'npx', 'composer',
        'git', 'curl', 'wget',
        'sort', 'uniq', 'cut', 'tr', 'sed', 'awk',
        'mkdir', 'touch', 'cp', 'mv', 'rm',
        'tar', 'gzip', 'gunzip', 'zip', 'unzip',
        'jq', 'base64', 'md5sum', 'sha256sum',
    ];

    private const BLOCKED_PATTERNS = [
        '/\brm\s+-rf\s+\//', // rm -rf /
        '/\b(sudo|su)\b/',
        '/\b(shutdown|reboot|halt|poweroff)\b/',
        '/\b(mkfs|fdisk|dd)\b/',
        '/\b(iptables|ufw)\b/',
        '/\b(passwd|useradd|userdel|usermod)\b/',
        '/\|\s*(bash|sh|zsh|csh)/',  // piping to shell
        '/>\s*\/etc\//',  // writing to /etc
        '/>\s*\/usr\//',
        '/>\s*\/bin\//',
        '/(^|\s)(\/etc|\/usr|\/bin|\/sbin|\/var|\/root|[A-Za-z]:[\/\\\\])/',
        '/(^|\s|[\/\\\\])\.\.([\/\\\\]|\s|$)/',
    ];

    public function name(): string
    {
        return 'shell_command';
    }

    public function displayName(): string
    {
        return 'Shell Command';
    }

    public function description(): string
    {
        return 'Execute shell commands in a sandboxed environment. Supports common utilities like ls, grep, git, python, node, etc. Commands run within the user\'s sandbox directory.';
    }

    public function category(): string
    {
        return 'system';
    }

    public function timeoutSeconds(): int
    {
        return 60;
    }

    public function requiresApproval(): bool
    {
        return true;
    }

    public function parametersSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'command' => [
                    'type' => 'string',
                    'description' => 'The shell command to execute',
                ],
                'working_directory' => [
                    'type' => 'string',
                    'description' => 'Relative working directory within the sandbox (optional)',
                ],
            ],
            'required' => ['command'],
        ];
    }

    public function execute(array $arguments, ?ToolContext $context = null): array
    {
        $command = $arguments['command'] ?? '';
        $workDir = $arguments['working_directory'] ?? '';

        if (empty($command)) {
            return ['success' => false, 'result' => null, 'error' => 'Command is required'];
        }

        if (!$context) {
            return ['success' => false, 'result' => null, 'error' => 'Tool context is required'];
        }

        $securityCheck = $this->validateCommand($command);
        if ($securityCheck !== null) {
            return ['success' => false, 'result' => null, 'error' => $securityCheck];
        }

        $sandbox = realpath($context->getSandboxPath());
        if ($sandbox === false) {
            return ['success' => false, 'result' => null, 'error' => 'Sandbox path is not available'];
        }

        $cwd = $sandbox;
        if (!empty($workDir)) {
            if ($this->containsPathTraversal($workDir)) {
                return ['success' => false, 'result' => null, 'error' => 'Working directory cannot contain path traversal'];
            }

            $cwd .= DIRECTORY_SEPARATOR . ltrim($workDir, '/\\');
            if (!is_dir($cwd)) {
                mkdir($cwd, 0755, true);
            }

            $resolvedCwd = realpath($cwd);
            if ($resolvedCwd === false || ! $this->isPathInside($resolvedCwd, $sandbox)) {
                return ['success' => false, 'result' => null, 'error' => 'Working directory must stay inside the sandbox'];
            }
            $cwd = $resolvedCwd;
        }

        try {
            $process = Process::fromShellCommandline($command, $cwd);
            $process->setTimeout($this->timeoutSeconds());
            $process->run();

            $output = $process->getOutput();
            $errorOutput = $process->getErrorOutput();
            $exitCode = $process->getExitCode();

            $maxLength = 50000;
            if (strlen($output) > $maxLength) {
                $output = substr($output, 0, $maxLength) . "\n... (output truncated)";
            }

            $result = [
                'exit_code' => $exitCode,
                'stdout' => $output,
            ];

            if (!empty($errorOutput)) {
                $result['stderr'] = substr($errorOutput, 0, $maxLength);
            }

            return [
                'success' => $exitCode === 0,
                'result' => $result,
                'error' => $exitCode !== 0 ? "Command exited with code {$exitCode}" : null,
            ];

        } catch (\Exception $e) {
            return ['success' => false, 'result' => null, 'error' => 'Command execution failed: ' . $e->getMessage()];
        }
    }

    private function validateCommand(string $command): ?string
    {
        foreach (self::BLOCKED_PATTERNS as $pattern) {
            if (preg_match($pattern, $command)) {
                return "Command blocked by security policy: matches dangerous pattern";
            }
        }

        $baseCommand = trim(explode(' ', trim($command))[0]);
        $baseCommand = basename($baseCommand);

        if (!in_array($baseCommand, self::ALLOWED_COMMANDS)) {
            return "Command '{$baseCommand}' is not in the allowed list. Allowed: " . implode(', ', array_slice(self::ALLOWED_COMMANDS, 0, 15)) . '...';
        }

        return null;
    }

    private function containsPathTraversal(string $path): bool
    {
        $segments = preg_split('/[\/\\\\]+/', $path) ?: [];

        return in_array('..', $segments, true);
    }

    private function isPathInside(string $path, string $base): bool
    {
        $path = rtrim(str_replace('\\', '/', $path), '/');
        $base = rtrim(str_replace('\\', '/', $base), '/');

        return $path === $base || str_starts_with($path, $base.'/');
    }
}
