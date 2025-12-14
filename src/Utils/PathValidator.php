<?php

namespace Ajility\LaravelAutodoc\Utils;

use InvalidArgumentException;

/**
 * Validates paths to prevent directory traversal attacks and ensure paths are within allowed boundaries.
 */
class PathValidator
{
    /**
     * Validate an output path to ensure it's within the allowed project boundaries.
     *
     * This method prevents path traversal attacks by:
     * - Resolving symlinks using realpath()
     * - Normalizing paths to remove .. and . segments
     * - Ensuring the resolved path is within the project root
     * - Checking for invalid characters like null bytes
     *
     * @param  string  $path  The path to validate (can be relative or absolute)
     * @param  string  $projectRoot  The allowed project root directory
     *
     * @throws InvalidArgumentException If the path is invalid or outside allowed boundaries
     */
    public function validateOutputPath(string $path, string $projectRoot): void
    {
        $this->validatePath($path, $projectRoot, 'Output path');
    }

    /**
     * Validate a source directory to ensure it's within the allowed project boundaries.
     *
     * @param  string  $path  The source directory path to validate
     * @param  string  $projectRoot  The allowed project root directory
     *
     * @throws InvalidArgumentException If the path is invalid or outside allowed boundaries
     */
    public function validateSourceDirectory(string $path, string $projectRoot): void
    {
        $this->validatePath($path, $projectRoot, 'Source directory');
    }

    /**
     * Core validation logic for any path.
     *
     * @param  string  $path  The path to validate
     * @param  string  $projectRoot  The allowed project root directory
     * @param  string  $pathType  Descriptive name for error messages (e.g., "Output path", "Source directory")
     *
     * @throws InvalidArgumentException If the path is invalid or outside allowed boundaries
     */
    private function validatePath(string $path, string $projectRoot, string $pathType): void
    {
        // Check for empty path
        if (trim($path) === '') {
            throw new InvalidArgumentException(
                "{$pathType} cannot be empty. Please provide a valid directory path."
            );
        }

        // Check for null bytes (security issue - can bypass extension checks)
        if (str_contains($path, "\0")) {
            throw new InvalidArgumentException(
                "{$pathType} contains invalid characters (null byte). This is a potential security issue."
            );
        }

        // Normalize the project root
        $normalizedProjectRoot = $this->normalizePath($projectRoot);
        if ($normalizedProjectRoot === null) {
            throw new InvalidArgumentException(
                "Project root directory does not exist or is not accessible: {$projectRoot}"
            );
        }

        // Convert relative path to absolute if needed
        $absolutePath = $this->resolveAbsolutePath($path, $normalizedProjectRoot);

        // Normalize and resolve the target path
        // For paths that don't exist yet, we need to validate parent directories
        $normalizedPath = $this->normalizePath($absolutePath);

        // If the exact path doesn't exist, validate the parent directory chain
        if ($normalizedPath === null) {
            $normalizedPath = $this->validateNonExistentPath($absolutePath, $normalizedProjectRoot, $pathType);
        }

        // Check if the normalized path is within the project root
        if (! $this->isPathWithinRoot($normalizedPath, $normalizedProjectRoot)) {
            throw new InvalidArgumentException(
                "{$pathType} '{$path}' is outside the allowed project root.\n".
                "Resolved to: {$normalizedPath}\n".
                "Project root: {$normalizedProjectRoot}\n".
                "Please use a path within your project directory, such as 'storage/docs' or 'public/documentation'."
            );
        }
    }

    /**
     * Validate a path that doesn't exist yet by checking its parent directories.
     *
     * @param  string  $path  The path to validate
     * @param  string  $projectRoot  The normalized project root
     * @param  string  $pathType  Description for error messages
     * @return string The normalized path
     *
     * @throws InvalidArgumentException If the path would be outside project root
     */
    private function validateNonExistentPath(string $path, string $projectRoot, string $pathType): string
    {
        // Manually normalize the path by resolving .. and . segments
        $manuallyNormalized = $this->manuallyNormalizePath($path);

        // Check if manually normalized path is within root
        if (! $this->isPathWithinRoot($manuallyNormalized, $projectRoot)) {
            throw new InvalidArgumentException(
                "{$pathType} '{$path}' would be created outside the allowed project root.\n".
                "Resolved to: {$manuallyNormalized}\n".
                "Project root: {$projectRoot}\n".
                'Please use a path within your project directory.'
            );
        }

        // For non-existent paths, we can't use realpath, so we validate the structure
        // Check if any existing parent directory is outside project root
        $currentPath = $path;
        while ($currentPath !== '' && $currentPath !== '/' && $currentPath !== dirname($currentPath)) {
            $parentDir = dirname($currentPath);

            // Try to resolve parent
            $resolvedParent = realpath($parentDir);
            if ($resolvedParent !== false) {
                // Found an existing parent - check if it's within project root
                if (! $this->isPathWithinRoot($resolvedParent, $projectRoot)) {
                    throw new InvalidArgumentException(
                        "{$pathType} '{$path}' would be created outside the allowed project root.\n".
                        "Parent directory: {$resolvedParent}\n".
                        "Project root: {$projectRoot}\n".
                        'Please use a path within your project directory.'
                    );
                }

                // Parent is valid, reconstruct the full path
                $remainingPath = substr($path, strlen($parentDir));
                $remainingPath = ltrim($remainingPath, '/\\');

                if ($remainingPath !== '') {
                    return rtrim($resolvedParent, '/\\').DIRECTORY_SEPARATOR.$remainingPath;
                }

                return $resolvedParent;
            }

            $currentPath = $parentDir;
        }

        // No existing parents found, use manual normalization
        return $manuallyNormalized;
    }

    /**
     * Manually normalize a path by resolving . and .. segments.
     *
     * @param  string  $path  The path to normalize
     * @return string The normalized path
     */
    private function manuallyNormalizePath(string $path): string
    {
        // Convert backslashes to forward slashes
        $path = str_replace('\\', '/', $path);

        // Split into segments
        $segments = explode('/', $path);
        $normalized = [];

        // Track if this is an absolute path
        $isAbsolute = isset($segments[0]) && $segments[0] === '';

        foreach ($segments as $segment) {
            if ($segment === '' || $segment === '.') {
                continue;
            }

            if ($segment === '..') {
                // Only pop if we have segments and aren't at root
                if (! empty($normalized)) {
                    array_pop($normalized);
                } elseif (! $isAbsolute) {
                    // For relative paths, we can't go above root - this is suspicious
                    throw new InvalidArgumentException(
                        "Path attempts to traverse above the starting directory: {$path}"
                    );
                }
            } else {
                $normalized[] = $segment;
            }
        }

        $result = ($isAbsolute ? '/' : '').implode('/', $normalized);

        return $result ?: ($isAbsolute ? '/' : '.');
    }

    /**
     * Resolve a potentially relative path to an absolute path.
     *
     * @param  string  $path  The path to resolve
     * @param  string  $basePath  The base path to resolve relative paths against
     * @return string The absolute path
     */
    private function resolveAbsolutePath(string $path, string $basePath): string
    {
        // Check if path is already absolute
        if ($this->isAbsolutePath($path)) {
            return $path;
        }

        // It's relative, so resolve against base path
        return rtrim($basePath, '/\\').DIRECTORY_SEPARATOR.ltrim($path, '/\\');
    }

    /**
     * Check if a path is absolute.
     *
     * @param  string  $path  The path to check
     * @return bool True if absolute, false if relative
     */
    private function isAbsolutePath(string $path): bool
    {
        // Unix/Linux absolute paths start with /
        if (str_starts_with($path, '/')) {
            return true;
        }

        // Windows absolute paths (C:\, D:\, etc.)
        if (preg_match('/^[a-zA-Z]:\\\\/', $path)) {
            return true;
        }

        // Windows UNC paths (\\server\share)
        if (str_starts_with($path, '\\\\')) {
            return true;
        }

        return false;
    }

    /**
     * Normalize a path using realpath and handle the result.
     *
     * @param  string  $path  The path to normalize
     * @return string|null The normalized path, or null if it doesn't exist
     */
    private function normalizePath(string $path): ?string
    {
        // realpath resolves symlinks and returns false if path doesn't exist
        $realPath = realpath($path);

        if ($realPath === false) {
            return null;
        }

        return $realPath;
    }

    /**
     * Check if a path is within the allowed root directory.
     *
     * @param  string  $path  The normalized path to check
     * @param  string  $root  The normalized root directory
     * @return bool True if path is within root, false otherwise
     */
    private function isPathWithinRoot(string $path, string $root): bool
    {
        // Normalize separators for comparison
        $path = str_replace('\\', '/', $path);
        $root = str_replace('\\', '/', $root);

        // Ensure root doesn't have trailing slash for consistent comparison
        $root = rtrim($root, '/');

        // Path must either equal root or start with root followed by a separator
        return $path === $root || str_starts_with($path, $root.'/');
    }
}
