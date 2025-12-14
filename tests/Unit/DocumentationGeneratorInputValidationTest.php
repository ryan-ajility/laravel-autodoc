<?php

namespace Ajility\LaravelAutodoc\Tests\Unit;

use Ajility\LaravelAutodoc\Tests\Helpers\CreatesDocumentationGenerator;
use Ajility\LaravelAutodoc\Tests\TestCase;

class DocumentationGeneratorInputValidationTest extends TestCase
{
    use CreatesDocumentationGenerator;

    private string $outputDir;

    private string $fixturesDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->outputDir = sys_get_temp_dir().'/autodoc_validation_'.uniqid();
        $this->fixturesDir = __DIR__.'/../fixtures/src';
    }

    protected function tearDown(): void
    {
        if (is_dir($this->outputDir)) {
            $this->deleteDirectory($this->outputDir);
        }

        parent::tearDown();
    }

    private function createGenerator()
    {
        return $this->createDocumentationGenerator();
    }

    public function test_it_throws_exception_when_output_path_is_missing(): void
    {
        $generator = $this->createGenerator();

        $config = [
            'source_directories' => [$this->fixturesDir],
            'excluded_directories' => [],
            'file_extensions' => ['php'],
            'include_private_methods' => false,
            'title' => 'Test Documentation',
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing required configuration: output_path');

        $generator->generate($config);
    }

    public function test_it_throws_exception_when_source_directories_is_missing(): void
    {
        $generator = $this->createGenerator();

        $config = [
            'output_path' => $this->outputDir,
            'excluded_directories' => [],
            'file_extensions' => ['php'],
            'include_private_methods' => false,
            'title' => 'Test Documentation',
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing required configuration: source_directories');

        $generator->generate($config);
    }

    public function test_it_throws_exception_when_output_path_is_not_string(): void
    {
        $generator = $this->createGenerator();

        $config = [
            'output_path' => ['not', 'a', 'string'],
            'source_directories' => [$this->fixturesDir],
            'excluded_directories' => [],
            'file_extensions' => ['php'],
            'include_private_methods' => false,
            'title' => 'Test Documentation',
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('output_path must be a non-empty string');

        $generator->generate($config);
    }

    public function test_it_throws_exception_when_output_path_is_empty_string(): void
    {
        $generator = $this->createGenerator();

        $config = [
            'output_path' => '',
            'source_directories' => [$this->fixturesDir],
            'excluded_directories' => [],
            'file_extensions' => ['php'],
            'include_private_methods' => false,
            'title' => 'Test Documentation',
            '_skip_path_validation' => true,
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('output_path must be a non-empty string');

        $generator->generate($config);
    }

    public function test_it_throws_exception_when_output_path_is_whitespace_only(): void
    {
        $generator = $this->createGenerator();

        $config = [
            'output_path' => '   ',
            'source_directories' => [$this->fixturesDir],
            'excluded_directories' => [],
            'file_extensions' => ['php'],
            'include_private_methods' => false,
            'title' => 'Test Documentation',
            '_skip_path_validation' => true,
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('output_path must be a non-empty string');

        $generator->generate($config);
    }

    public function test_it_throws_exception_when_source_directories_is_not_array(): void
    {
        $generator = $this->createGenerator();

        $config = [
            'output_path' => $this->outputDir,
            'source_directories' => 'not an array',
            'excluded_directories' => [],
            'file_extensions' => ['php'],
            'include_private_methods' => false,
            'title' => 'Test Documentation',
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('source_directories must be a non-empty array');

        $generator->generate($config);
    }

    public function test_it_throws_exception_when_source_directories_is_empty_array(): void
    {
        $generator = $this->createGenerator();

        $config = [
            'output_path' => $this->outputDir,
            'source_directories' => [],
            'excluded_directories' => [],
            'file_extensions' => ['php'],
            'include_private_methods' => false,
            'title' => 'Test Documentation',
            '_skip_path_validation' => true,
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('source_directories must be a non-empty array');

        $generator->generate($config);
    }

    public function test_it_throws_exception_when_output_path_is_null(): void
    {
        $generator = $this->createGenerator();

        $config = [
            'output_path' => null,
            'source_directories' => [$this->fixturesDir],
            'excluded_directories' => [],
            'file_extensions' => ['php'],
            'include_private_methods' => false,
            'title' => 'Test Documentation',
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/output_path/');

        $generator->generate($config);
    }

    public function test_it_throws_exception_when_source_directories_is_null(): void
    {
        $generator = $this->createGenerator();

        $config = [
            'output_path' => $this->outputDir,
            'source_directories' => null,
            'excluded_directories' => [],
            'file_extensions' => ['php'],
            'include_private_methods' => false,
            'title' => 'Test Documentation',
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/source_directories/');

        $generator->generate($config);
    }

    public function test_it_accepts_valid_configuration(): void
    {
        $generator = $this->createGenerator();

        $config = [
            'output_path' => $this->outputDir,
            'source_directories' => [$this->fixturesDir],
            'excluded_directories' => [],
            'file_extensions' => ['php'],
            'include_private_methods' => false,
            'title' => 'Test Documentation',
            '_skip_path_validation' => true,
        ];

        $stats = $generator->generate($config);

        $this->assertIsArray($stats);
        $this->assertDirectoryExists($this->outputDir);
    }

    public function test_it_trims_output_path(): void
    {
        $generator = $this->createGenerator();

        $config = [
            'output_path' => '  '.$this->outputDir.'  ',
            'source_directories' => [$this->fixturesDir],
            'excluded_directories' => [],
            'file_extensions' => ['php'],
            'include_private_methods' => false,
            'title' => 'Test Documentation',
            '_skip_path_validation' => true,
        ];

        $stats = $generator->generate($config);

        $this->assertDirectoryExists($this->outputDir);
    }
}
