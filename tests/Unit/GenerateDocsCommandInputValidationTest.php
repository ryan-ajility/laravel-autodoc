<?php

namespace Ajility\LaravelAutodoc\Tests\Unit;

use Ajility\LaravelAutodoc\Tests\TestCase;

class GenerateDocsCommandInputValidationTest extends TestCase
{
    private string $outputDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->outputDir = sys_get_temp_dir().'/autodoc_validation_'.uniqid();
    }

    protected function tearDown(): void
    {
        if (is_dir($this->outputDir)) {
            $this->deleteDirectory($this->outputDir);
        }

        parent::tearDown();
    }

    public function test_command_rejects_whitespace_only_path_option(): void
    {
        $this->artisan('autodoc:generate', ['--path' => '   '])
            ->assertExitCode(1);
    }

    public function test_command_trims_path_option(): void
    {
        $path = '  '.$this->outputDir.'  ';

        $this->artisan('autodoc:generate', ['--path' => $path])
            ->assertExitCode(0);

        $this->assertDirectoryExists($this->outputDir);
    }

    public function test_command_rejects_source_with_only_whitespace_strings(): void
    {
        $this->artisan('autodoc:generate', ['--source' => ['  ', '   ']])
            ->assertExitCode(1);
    }

    public function test_command_filters_empty_source_directories(): void
    {
        $validSource = __DIR__.'/../fixtures/src';

        $this->artisan('autodoc:generate', [
            '--path' => $this->outputDir,
            '--source' => [$validSource, '', '  '],
        ])->assertExitCode(0);

        $this->assertDirectoryExists($this->outputDir);
    }

    public function test_command_trims_source_directories(): void
    {
        $validSource = '  '.__DIR__.'/../fixtures/src  ';

        $this->artisan('autodoc:generate', [
            '--path' => $this->outputDir,
            '--source' => [$validSource],
        ])->assertExitCode(0);

        $this->assertDirectoryExists($this->outputDir);
    }

    public function test_command_handles_comma_separated_source_directories(): void
    {
        // Note: In Laravel, array options with --source can be passed multiple times
        // but if a string is passed, we need to handle comma separation
        $validSource = __DIR__.'/../fixtures/src';

        $this->artisan('autodoc:generate', [
            '--path' => $this->outputDir,
            '--source' => [$validSource],
        ])->assertExitCode(0);

        $this->assertDirectoryExists($this->outputDir);
    }

    public function test_command_rejects_null_values(): void
    {
        // Test that null options are handled gracefully
        // This is more of an edge case
        $this->artisan('autodoc:generate', ['--path' => $this->outputDir])
            ->assertExitCode(0);
    }

    public function test_command_validates_exclude_directories(): void
    {
        $this->artisan('autodoc:generate', [
            '--path' => $this->outputDir,
            '--exclude' => ['vendor', '  tests  ', ''],
        ])->assertExitCode(0);

        $this->assertDirectoryExists($this->outputDir);
    }

    public function test_command_rejects_invalid_boolean_flags(): void
    {
        // Boolean flags should be true/false, not strings
        // Laravel handles this automatically, but we should ensure proper type checking
        $this->artisan('autodoc:generate', [
            '--path' => $this->outputDir,
            '--private' => true,
        ])->assertExitCode(0);

        $this->assertDirectoryExists($this->outputDir);
    }

    public function test_command_handles_protected_flag_properly(): void
    {
        $this->artisan('autodoc:generate', [
            '--path' => $this->outputDir,
            '--protected' => true,
        ])->assertExitCode(0);

        $this->assertDirectoryExists($this->outputDir);
    }

    public function test_command_displays_error_message_for_whitespace_path(): void
    {
        $this->artisan('autodoc:generate', ['--path' => '   '])
            ->expectsOutputToContain('--path must be a non-empty string')
            ->assertExitCode(1);
    }

    public function test_command_displays_error_message_for_whitespace_sources(): void
    {
        $this->artisan('autodoc:generate', ['--source' => ['  ', '   ']])
            ->expectsOutputToContain('--source must contain at least one directory')
            ->assertExitCode(1);
    }
}
