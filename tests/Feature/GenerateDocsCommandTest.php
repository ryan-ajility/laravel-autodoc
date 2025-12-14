<?php

namespace Ajility\LaravelAutodoc\Tests\Feature;

use Ajility\LaravelAutodoc\Tests\TestCase;
use Illuminate\Support\Facades\File;

class GenerateDocsCommandTest extends TestCase
{
    public function test_command_generates_documentation_successfully(): void
    {
        $this->artisan('autodoc:generate')
            ->expectsOutput('Starting documentation generation...')
            ->expectsOutput('Scanning files...')
            ->expectsOutput('Documentation generation completed!')
            ->assertExitCode(0);
    }

    public function test_command_creates_output_directory(): void
    {
        $outputPath = __DIR__.'/../output';

        $this->artisan('autodoc:generate')
            ->assertExitCode(0);

        $this->assertDirectoryExists($outputPath);
        $this->assertFileExists($outputPath.'/README.md');
    }

    public function test_command_accepts_custom_output_path(): void
    {
        $customPath = sys_get_temp_dir().'/custom_docs_'.uniqid();

        $this->artisan('autodoc:generate', ['--path' => $customPath])
            ->assertExitCode(0);

        $this->assertDirectoryExists($customPath);
        $this->assertFileExists($customPath.'/README.md');

        // Cleanup
        if (is_dir($customPath)) {
            File::deleteDirectory($customPath);
        }
    }

    public function test_command_accepts_additional_source_directories(): void
    {
        $additionalSource = __DIR__.'/../fixtures/src';

        $this->artisan('autodoc:generate', ['--source' => [$additionalSource]])
            ->assertExitCode(0);

        $outputPath = __DIR__.'/../output';
        $this->assertFileExists($outputPath.'/README.md');

        $readmeContent = file_get_contents($outputPath.'/README.md');
        $this->assertNotEmpty($readmeContent);
    }

    public function test_command_accepts_exclude_directories_option(): void
    {
        $this->artisan('autodoc:generate', ['--exclude' => ['vendor', 'tests']])
            ->assertExitCode(0);

        $outputPath = __DIR__.'/../output';
        $this->assertFileExists($outputPath.'/README.md');
    }

    public function test_command_accepts_private_methods_option(): void
    {
        $this->artisan('autodoc:generate', ['--private' => true])
            ->assertExitCode(0);

        $outputPath = __DIR__.'/../output';
        $this->assertFileExists($outputPath.'/README.md');

        $sampleClassDoc = $outputPath.'/SampleClass.md';
        if (file_exists($sampleClassDoc)) {
            $content = file_get_contents($sampleClassDoc);
            $this->assertStringContainsString('privateMethod', $content);
        }
    }

    public function test_command_displays_configuration(): void
    {
        $this->artisan('autodoc:generate')
            ->expectsOutputToContain('Configuration:')
            ->expectsOutputToContain('Output Path:')
            ->expectsOutputToContain('Source Directories:')
            ->expectsOutputToContain('Include Private Methods:')
            ->assertExitCode(0);
    }

    public function test_command_displays_statistics(): void
    {
        $this->artisan('autodoc:generate')
            ->expectsOutputToContain('Files scanned')
            ->expectsOutputToContain('Classes documented')
            ->expectsOutputToContain('Methods documented')
            ->expectsOutputToContain('Properties documented')
            ->assertExitCode(0);
    }

    public function test_command_generates_markdown_files_for_fixtures(): void
    {
        $this->artisan('autodoc:generate')
            ->assertExitCode(0);

        $outputPath = __DIR__.'/../output';

        // Check that markdown files were created for our fixtures
        $sampleClassDoc = $outputPath.'/SampleClass.md';
        $sampleInterfaceDoc = $outputPath.'/SampleInterface.md';
        $sampleTraitDoc = $outputPath.'/SampleTrait.md';
        $extendedClassDoc = $outputPath.'/ExtendedClass.md';

        $this->assertFileExists($sampleClassDoc);
        $this->assertFileExists($sampleInterfaceDoc);
        $this->assertFileExists($sampleTraitDoc);
        $this->assertFileExists($extendedClassDoc);
    }

    public function test_generated_documentation_contains_correct_content(): void
    {
        $this->artisan('autodoc:generate')
            ->assertExitCode(0);

        $outputPath = __DIR__.'/../output';
        $sampleClassDoc = $outputPath.'/SampleClass.md';

        $this->assertFileExists($sampleClassDoc);

        $content = file_get_contents($sampleClassDoc);

        $this->assertStringContainsString('# SampleClass', $content);
        $this->assertStringContainsString('## Description', $content);
        $this->assertStringContainsString('## Properties', $content);
        $this->assertStringContainsString('## Methods', $content);
        $this->assertStringContainsString('publicMethod', $content);
        $this->assertStringContainsString('staticMethod', $content);
    }

    public function test_readme_file_contains_documentation_overview(): void
    {
        $this->artisan('autodoc:generate')
            ->assertExitCode(0);

        $outputPath = __DIR__.'/../output';
        $readmePath = $outputPath.'/README.md';

        $this->assertFileExists($readmePath);

        $content = file_get_contents($readmePath);

        $this->assertStringContainsString('# Test Documentation', $content);
        $this->assertStringContainsString('Generated on:', $content);
        $this->assertStringContainsString('Overview', $content);
    }

    public function test_command_works_with_multiple_options_combined(): void
    {
        $customPath = sys_get_temp_dir().'/combined_test_'.uniqid();
        $additionalSource = __DIR__.'/../fixtures/src';

        $this->artisan('autodoc:generate', [
            '--path' => $customPath,
            '--source' => [$additionalSource],
            '--exclude' => ['vendor'],
            '--private' => true,
        ])->assertExitCode(0);

        $this->assertDirectoryExists($customPath);
        $this->assertFileExists($customPath.'/README.md');

        // Cleanup
        if (is_dir($customPath)) {
            File::deleteDirectory($customPath);
        }
    }

    public function test_command_displays_final_output_path(): void
    {
        $outputPath = config('autodoc.output_path');

        $this->artisan('autodoc:generate')
            ->expectsOutputToContain("Documentation saved to: {$outputPath}")
            ->expectsOutputToContain("Index file: {$outputPath}/README.md")
            ->assertExitCode(0);
    }
}
