<?php

namespace Ajility\LaravelAutodoc\Tests\Helpers;

trait FileSystemHelper
{
    private const DEFAULT_DIRECTORY_PERMISSIONS = 0755;

    protected function deleteDirectory(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        $items = scandir($dir);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $dir.DIRECTORY_SEPARATOR.$item;

            if (is_dir($path)) {
                $this->deleteDirectory($path);
            } else {
                unlink($path);
            }
        }

        rmdir($dir);
    }

    protected function createTempDirectory(string $prefix = 'test_'): string
    {
        $tempDir = sys_get_temp_dir().'/'.$prefix.uniqid();
        mkdir($tempDir, self::DEFAULT_DIRECTORY_PERMISSIONS, true);

        return $tempDir;
    }
}
