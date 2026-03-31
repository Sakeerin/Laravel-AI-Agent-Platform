<?php

namespace App\Services\Tools\BuiltIn;

use App\Services\Tools\BaseTool;
use App\Services\Tools\ToolContext;

class FileSystemTool extends BaseTool
{
    public function name(): string
    {
        return 'file_system';
    }

    public function displayName(): string
    {
        return 'File System';
    }

    public function description(): string
    {
        return 'Read, write, list, and delete files within a sandboxed directory. Use this to create, modify, or inspect files.';
    }

    public function category(): string
    {
        return 'filesystem';
    }

    public function parametersSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'action' => [
                    'type' => 'string',
                    'enum' => ['read', 'write', 'append', 'list', 'delete', 'exists', 'mkdir'],
                    'description' => 'The file operation to perform',
                ],
                'path' => [
                    'type' => 'string',
                    'description' => 'Relative file path within the sandbox',
                ],
                'content' => [
                    'type' => 'string',
                    'description' => 'Content to write (for write/append actions)',
                ],
            ],
            'required' => ['action', 'path'],
        ];
    }

    public function execute(array $arguments, ?ToolContext $context = null): array
    {
        $action = $arguments['action'] ?? '';
        $relativePath = $arguments['path'] ?? '';
        $content = $arguments['content'] ?? '';

        if (!$context) {
            return ['success' => false, 'result' => null, 'error' => 'Tool context is required'];
        }

        $sandboxPath = $context->getSandboxPath();
        $fullPath = $this->resolvePath($sandboxPath, $relativePath);

        if (!$fullPath) {
            return ['success' => false, 'result' => null, 'error' => 'Path traversal detected: path must stay within sandbox'];
        }

        return match ($action) {
            'read' => $this->readFile($fullPath),
            'write' => $this->writeFile($fullPath, $content),
            'append' => $this->appendFile($fullPath, $content),
            'list' => $this->listDirectory($fullPath, $sandboxPath),
            'delete' => $this->deleteFile($fullPath),
            'exists' => $this->fileExists($fullPath),
            'mkdir' => $this->makeDirectory($fullPath),
            default => ['success' => false, 'result' => null, 'error' => "Unknown action: {$action}"],
        };
    }

    private function resolvePath(string $sandboxPath, string $relativePath): ?string
    {
        $relativePath = ltrim(str_replace('\\', '/', $relativePath), '/');
        $fullPath = realpath($sandboxPath) ?: $sandboxPath;
        $resolved = $fullPath . DIRECTORY_SEPARATOR . $relativePath;

        // For new files that don't exist yet, check parent directory
        $checkPath = file_exists($resolved) ? realpath($resolved) : realpath(dirname($resolved));

        if ($checkPath === false) {
            // Parent directory doesn't exist either, check against sandbox
            $normalizedSandbox = str_replace('\\', '/', $fullPath);
            $normalizedResolved = str_replace('\\', '/', $resolved);
            if (!str_starts_with($normalizedResolved, $normalizedSandbox)) {
                return null;
            }
            return $resolved;
        }

        $normalizedSandbox = str_replace('\\', '/', realpath($sandboxPath) ?: $sandboxPath);
        $normalizedCheck = str_replace('\\', '/', $checkPath);

        if (!str_starts_with($normalizedCheck, $normalizedSandbox)) {
            return null;
        }

        return $resolved;
    }

    private function readFile(string $path): array
    {
        if (!is_file($path)) {
            return ['success' => false, 'result' => null, 'error' => 'File not found'];
        }

        $size = filesize($path);
        if ($size > 1024 * 1024) {
            return ['success' => false, 'result' => null, 'error' => 'File too large (max 1MB)'];
        }

        return ['success' => true, 'result' => file_get_contents($path)];
    }

    private function writeFile(string $path, string $content): array
    {
        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($path, $content);
        return ['success' => true, 'result' => 'File written successfully (' . strlen($content) . ' bytes)'];
    }

    private function appendFile(string $path, string $content): array
    {
        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($path, $content, FILE_APPEND);
        return ['success' => true, 'result' => 'Content appended successfully'];
    }

    private function listDirectory(string $path, string $sandboxPath): array
    {
        if (!is_dir($path)) {
            return ['success' => false, 'result' => null, 'error' => 'Directory not found'];
        }

        $items = [];
        foreach (scandir($path) as $item) {
            if ($item === '.' || $item === '..') continue;
            $fullItemPath = $path . DIRECTORY_SEPARATOR . $item;
            $items[] = [
                'name' => $item,
                'type' => is_dir($fullItemPath) ? 'directory' : 'file',
                'size' => is_file($fullItemPath) ? filesize($fullItemPath) : null,
            ];
        }

        return ['success' => true, 'result' => $items];
    }

    private function deleteFile(string $path): array
    {
        if (is_file($path)) {
            unlink($path);
            return ['success' => true, 'result' => 'File deleted'];
        }

        if (is_dir($path)) {
            $this->deleteDirectory($path);
            return ['success' => true, 'result' => 'Directory deleted'];
        }

        return ['success' => false, 'result' => null, 'error' => 'Path not found'];
    }

    private function fileExists(string $path): array
    {
        return [
            'success' => true,
            'result' => [
                'exists' => file_exists($path),
                'is_file' => is_file($path),
                'is_directory' => is_dir($path),
            ],
        ];
    }

    private function makeDirectory(string $path): array
    {
        if (is_dir($path)) {
            return ['success' => true, 'result' => 'Directory already exists'];
        }
        mkdir($path, 0755, true);
        return ['success' => true, 'result' => 'Directory created'];
    }

    private function deleteDirectory(string $path): void
    {
        foreach (scandir($path) as $item) {
            if ($item === '.' || $item === '..') continue;
            $itemPath = $path . DIRECTORY_SEPARATOR . $item;
            is_dir($itemPath) ? $this->deleteDirectory($itemPath) : unlink($itemPath);
        }
        rmdir($path);
    }
}
