<?php

namespace Ajility\LaravelAutodoc\Tests\Feature;

use Ajility\LaravelAutodoc\Tests\TestCase;

class GenerateExampleDocsTest extends TestCase
{
    protected function getEnvironmentSetUp($app): void
    {
        // Override parent to use examples directory
        config()->set('autodoc.output_path', __DIR__.'/../../examples/docs');
        config()->set('autodoc.source_directories', [__DIR__.'/../../examples/SampleApp']);
        config()->set('autodoc.excluded_directories', ['vendor', 'node_modules']);
        config()->set('autodoc.file_extensions', ['php']);
        config()->set('autodoc.include_protected_methods', true);
        config()->set('autodoc.include_private_methods', false);
        config()->set('autodoc.title', 'Laravel Documentation');
    }

    public function test_generates_example_documentation(): void
    {
        $this->artisan('autodoc:generate')
            ->expectsOutput('Starting documentation generation...')
            ->expectsOutput('Scanning files...')
            ->expectsOutput('Documentation generation completed!')
            ->assertExitCode(0);

        $outputPath = __DIR__.'/../../examples/docs';
        $this->assertFileExists($outputPath.'/README.md');
    }

    protected function tearDown(): void
    {
        // Don't clean up the examples/docs directory
        parent::tearDown();
    }
}
