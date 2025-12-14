<?php

namespace Ajility\LaravelAutodoc\Tests;

use Ajility\LaravelAutodoc\AutodocServiceProvider;
use Ajility\LaravelAutodoc\Tests\Helpers\FileSystemHelper;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    use FileSystemHelper;

    protected function getPackageProviders($app): array
    {
        return [
            AutodocServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        // Setup test-specific config to use test directories
        config()->set('autodoc.output_path', __DIR__.'/output');
        config()->set('autodoc.source_directories', [__DIR__.'/fixtures/src']);
        config()->set('autodoc.excluded_directories', ['vendor', 'node_modules']);
        config()->set('autodoc.file_extensions', ['php']);
        config()->set('autodoc.include_protected_methods', false);
        config()->set('autodoc.include_private_methods', false);
        config()->set('autodoc.title', 'Test Documentation');
        config()->set('autodoc._skip_path_validation', true); // Allow temp dirs in tests
    }

    protected function tearDown(): void
    {
        // Clean up any generated output
        $outputPath = __DIR__.'/output';
        if (is_dir($outputPath)) {
            $this->deleteDirectory($outputPath);
        }

        parent::tearDown();
    }

    /**
     * Create a ClassParser instance with all dependencies.
     */
    protected function createClassParser(): \Ajility\LaravelAutodoc\Services\ClassParser
    {
        return app(\Ajility\LaravelAutodoc\Services\ClassParser::class);
    }
}
