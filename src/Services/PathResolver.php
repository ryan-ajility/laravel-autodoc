<?php

namespace Ajility\LaravelAutodoc\Services;

class PathResolver
{
    private const MAX_UPWARD_PATH_LEVELS = 5;

    /**
     * Calculate relative path from current file to target file.
     *
     * @param  string  $from  The current file path (e.g., "Services/PaymentService.md")
     * @param  string  $to  The target file path (e.g., "Contracts/PaymentProcessorInterface.md")
     * @return string The relative path
     */
    public function getRelativePath(string $from, string $to): string
    {
        // Convert paths to arrays
        $from = str_replace('.md', '', $from);
        $to = str_replace('.md', '', $to);

        $fromParts = explode('/', dirname($from));
        $toParts = explode('/', dirname($to));

        // Remove common path prefix
        while (count($fromParts) > 0 && count($toParts) > 0 && $fromParts[0] === $toParts[0]) {
            array_shift($fromParts);
            array_shift($toParts);
        }

        // Build relative path
        $relativePath = '';

        // Add ../ for each remaining directory in from path
        if (count($fromParts) > 0 && $fromParts[0] !== '.') {
            $relativePath = str_repeat('../', count($fromParts));
        }

        // Add the remaining to path
        if (count($toParts) > 0 && $toParts[0] !== '.') {
            $relativePath .= implode('/', $toParts).'/';
        }

        // Add the target filename
        $relativePath .= basename($to).'.md';

        return $relativePath;
    }

    /**
     * Calculate the relative path from the documentation file to the source file.
     *
     * @param  string  $relativeDocPath  The relative path of the documentation file (e.g., "Services/PaymentService.md")
     * @param  string  $absoluteSourcePath  The absolute path to the source file
     * @param  string  $outputPath  The absolute path to the documentation output directory
     * @return string The relative path from the documentation file to the source file
     */
    public function calculateRelativePathToSource(
        string $relativeDocPath,
        string $absoluteSourcePath,
        string $outputPath
    ): string {
        // Get the absolute path of the documentation file
        $docFilePath = $outputPath.DIRECTORY_SEPARATOR.$relativeDocPath;
        $docDir = dirname($docFilePath);

        // Normalize paths by converting to forward slashes and using realpath
        $docDir = $this->normalizePath($docDir);
        $absoluteSourcePath = $this->normalizePath($absoluteSourcePath);

        // Convert both paths to arrays using forward slashes
        $docParts = explode('/', $docDir);
        $sourceParts = explode('/', dirname($absoluteSourcePath));

        // Find common prefix
        $commonPrefixLength = 0;
        $minLength = min(count($docParts), count($sourceParts));

        for ($i = 0; $i < $minLength; $i++) {
            if ($docParts[$i] === $sourceParts[$i]) {
                $commonPrefixLength++;
            } else {
                break;
            }
        }

        // Remove common prefix
        $docPartsRelative = array_slice($docParts, $commonPrefixLength);
        $sourcePartsRelative = array_slice($sourceParts, $commonPrefixLength);

        // Calculate how many "../" we would need
        $upwardLevels = count($docPartsRelative);

        // If there's no common ancestor, or if the relative path would be excessively long
        // (more than 5 levels up), fall back to using the source-relative path as a reference.
        // This prevents generating paths like ../../../../../../../Users/...
        // Reasonable relative paths should not need to go up more than a few levels.
        // Also fall back if common prefix is just the root slash (effectively no common path)
        if ($commonPrefixLength === 0 || $commonPrefixLength === 1 && $docParts[0] === '' || $upwardLevels > self::MAX_UPWARD_PATH_LEVELS) {
            // Use the relativeDocPath as a template since it mirrors the source structure
            // Just convert .md to .php
            return str_replace('.md', '.php', $relativeDocPath);
        }

        // Build relative path
        $relativePath = '';

        // Add ../ for each remaining directory level in doc path
        if ($upwardLevels > 0) {
            $relativePath = str_repeat('../', $upwardLevels);
        }

        // Add the source path
        if (count($sourcePartsRelative) > 0) {
            $relativePath .= implode('/', $sourcePartsRelative).'/';
        }

        // Add the filename
        $relativePath .= basename($absoluteSourcePath);

        return $relativePath;
    }

    /**
     * Normalize a file path by resolving it to absolute and converting to forward slashes.
     *
     * @param  string  $path  The path to normalize
     * @return string The normalized path
     */
    public function normalizePath(string $path): string
    {
        // Try realpath first, but if it fails (e.g., path doesn't exist yet), normalize manually
        $normalized = realpath($path);

        if ($normalized === false) {
            // Path doesn't exist, manually normalize
            $normalized = $path;

            // Convert backslashes to forward slashes
            $normalized = str_replace('\\', '/', $normalized);

            // Remove redundant slashes
            $normalized = preg_replace('#/+#', '/', $normalized);

            // Resolve . and .. segments
            $parts = explode('/', $normalized);
            $resolved = [];

            foreach ($parts as $part) {
                if ($part === '.' || $part === '') {
                    // Skip current directory references and empty parts (except for leading slash)
                    if ($part === '' && count($resolved) === 0) {
                        // Keep the leading empty string for absolute paths
                        $resolved[] = $part;
                    }

                    continue;
                } elseif ($part === '..') {
                    // Go up one directory
                    if (count($resolved) > 1 || (count($resolved) === 1 && $resolved[0] !== '')) {
                        array_pop($resolved);
                    }
                } else {
                    $resolved[] = $part;
                }
            }

            $normalized = implode('/', $resolved);

            // On macOS, /var is a symlink to /private/var
            // Normalize /var to /private/var for consistent path comparison
            if (str_starts_with($normalized, '/var/')) {
                $normalized = '/private'.$normalized;
            }
        } else {
            // Convert to forward slashes for consistency
            $normalized = str_replace('\\', '/', $normalized);
        }

        return $normalized;
    }
}
