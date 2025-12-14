<?php

namespace Ajility\LaravelAutodoc\Services;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

class DirectoryScanner
{
    /**
     * Scan directories and return all PHP files.
     *
     * @param  array  $directories  The directories to scan
     * @param  array  $excludedPaths  Paths to exclude from scanning
     * @param  array  $extensions  File extensions to include
     * @return array Array of SplFileInfo objects
     */
    public function scan(array $directories, array $excludedPaths = [], array $extensions = ['php']): array
    {
        $files = [];

        foreach ($directories as $directory) {
            if (! is_dir($directory)) {
                continue;
            }

            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            );

            foreach ($iterator as $file) {
                if (! $file->isFile()) {
                    continue;
                }

                // Check if file has the correct extension
                if (! in_array($file->getExtension(), $extensions)) {
                    continue;
                }

                // Check if file is in excluded path
                if ($this->isExcluded($file->getPathname(), $excludedPaths)) {
                    continue;
                }

                $files[] = $file;
            }
        }

        return $files;
    }

    /**
     * Check if a path should be excluded.
     *
     * @param  string  $path  The path to check
     * @param  array  $excludedPaths  Paths to exclude
     */
    private function isExcluded(string $path, array $excludedPaths): bool
    {
        foreach ($excludedPaths as $excludedPath) {
            if (str_contains($path, $excludedPath)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the relative path from a base directory.
     *
     * @param  string  $filePath  The full file path
     * @param  string  $basePath  The base directory path
     */
    public function getRelativePath(string $filePath, string $basePath): string
    {
        $basePath = rtrim($basePath, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;

        if (str_starts_with($filePath, $basePath)) {
            return substr($filePath, strlen($basePath));
        }

        return $filePath;
    }
}
