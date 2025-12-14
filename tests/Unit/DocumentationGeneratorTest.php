<?php

namespace Ajility\LaravelAutodoc\Tests\Unit;

use Ajility\LaravelAutodoc\Services\ClassParser;
use Ajility\LaravelAutodoc\Services\DirectoryScanner;
use Ajility\LaravelAutodoc\Services\DocumentationGenerator;
use Ajility\LaravelAutodoc\Services\Markdown\ClassMarkdownGenerator;
use Ajility\LaravelAutodoc\Services\Markdown\IndexMarkdownGenerator;
use Ajility\LaravelAutodoc\Services\PathResolver;
use Ajility\LaravelAutodoc\Tests\TestCase;
use Ajility\LaravelAutodoc\Utils\PathValidator;

class DocumentationGeneratorTest extends TestCase
{
    private string $outputDir;

    private string $fixturesDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->outputDir = sys_get_temp_dir().'/autodoc_output_'.uniqid();
        $this->fixturesDir = __DIR__.'/../fixtures/src';
    }

    protected function tearDown(): void
    {
        if (is_dir($this->outputDir)) {
            $this->deleteDirectory($this->outputDir);
        }

        parent::tearDown();
    }

    private function createGenerator(): DocumentationGenerator
    {
        $scanner = new DirectoryScanner;
        $parser = app(ClassParser::class);
        $pathResolver = app(PathResolver::class);
        $classMarkdownGenerator = app(ClassMarkdownGenerator::class);
        $indexMarkdownGenerator = app(IndexMarkdownGenerator::class);
        $pathValidator = new PathValidator;

        return new DocumentationGenerator(
            $scanner,
            $parser,
            $classMarkdownGenerator,
            $indexMarkdownGenerator,
            $pathResolver,
            $pathValidator
        );
    }

    public function test_it_generates_documentation(): void
    {
        $generator = $this->createGenerator();

        $config = [
            'output_path' => $this->outputDir,
            'source_directories' => [$this->fixturesDir],
            'excluded_directories' => ['vendor'],
            'file_extensions' => ['php'],
            'include_private_methods' => false,
            'title' => 'Test Documentation',
            '_skip_path_validation' => true, // Allow temp dirs in tests
        ];

        $stats = $generator->generate($config);

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('files_scanned', $stats);
        $this->assertArrayHasKey('classes_documented', $stats);
        $this->assertArrayHasKey('methods_documented', $stats);
        $this->assertArrayHasKey('properties_documented', $stats);

        $this->assertGreaterThan(0, $stats['files_scanned']);
        $this->assertGreaterThan(0, $stats['classes_documented']);
    }

    public function test_it_creates_output_directory(): void
    {
        $generator = $this->createGenerator();

        $config = [
            'output_path' => $this->outputDir,
            'source_directories' => [$this->fixturesDir],
            'excluded_directories' => [],
            'file_extensions' => ['php'],
            'include_private_methods' => false,
            'title' => 'Test Documentation',
            '_skip_path_validation' => true, // Allow temp dirs in tests
        ];

        $generator->generate($config);

        $this->assertDirectoryExists($this->outputDir);
    }

    public function test_it_generates_index_file(): void
    {
        $generator = $this->createGenerator();

        $config = [
            'output_path' => $this->outputDir,
            'source_directories' => [$this->fixturesDir],
            'excluded_directories' => [],
            'file_extensions' => ['php'],
            'include_private_methods' => false,
            'title' => 'Test Documentation',
            '_skip_path_validation' => true, // Allow temp dirs in tests
        ];

        $generator->generate($config);

        $readmePath = $this->outputDir.DIRECTORY_SEPARATOR.'README.md';
        $this->assertFileExists($readmePath);

        $content = file_get_contents($readmePath);
        $this->assertStringContainsString('# Test Documentation', $content);
    }

    public function test_it_generates_markdown_files_for_classes(): void
    {
        $generator = $this->createGenerator();

        $config = [
            'output_path' => $this->outputDir,
            'source_directories' => [$this->fixturesDir],
            'excluded_directories' => [],
            'file_extensions' => ['php'],
            'include_private_methods' => false,
            'title' => 'Test Documentation',
            '_skip_path_validation' => true, // Allow temp dirs in tests
        ];

        $generator->generate($config);

        $sampleClassDoc = $this->outputDir.DIRECTORY_SEPARATOR.'SampleClass.md';
        $this->assertFileExists($sampleClassDoc);

        $content = file_get_contents($sampleClassDoc);
        $this->assertStringContainsString('# SampleClass', $content);
        $this->assertStringContainsString('## Description', $content);
    }

    public function test_it_respects_include_private_methods_setting(): void
    {
        $generator = $this->createGenerator();

        $config = [
            'output_path' => $this->outputDir,
            'source_directories' => [$this->fixturesDir],
            'excluded_directories' => [],
            'file_extensions' => ['php'],
            'include_private_methods' => true,
            'title' => 'Test Documentation',
            '_skip_path_validation' => true, // Allow temp dirs in tests
        ];

        $stats = $generator->generate($config);

        $sampleClassDoc = $this->outputDir.DIRECTORY_SEPARATOR.'SampleClass.md';
        $content = file_get_contents($sampleClassDoc);

        $this->assertStringContainsString('privateMethod', $content);
        $this->assertStringContainsString('protectedMethod', $content);
        $this->assertGreaterThan(2, $stats['methods_documented']);
    }

    public function test_it_excludes_private_methods_by_default(): void
    {
        $generator = $this->createGenerator();

        $config = [
            'output_path' => $this->outputDir,
            'source_directories' => [$this->fixturesDir],
            'excluded_directories' => [],
            'file_extensions' => ['php'],
            'include_private_methods' => false,
            'title' => 'Test Documentation',
            '_skip_path_validation' => true, // Allow temp dirs in tests
        ];

        $generator->generate($config);

        $sampleClassDoc = $this->outputDir.DIRECTORY_SEPARATOR.'SampleClass.md';
        $content = file_get_contents($sampleClassDoc);

        $this->assertStringNotContainsString('privateMethod', $content);
        $this->assertStringNotContainsString('protectedMethod', $content);
    }

    public function test_it_cleans_existing_output_directory(): void
    {
        // Create output directory with existing file
        mkdir($this->outputDir);
        $existingFile = $this->outputDir.DIRECTORY_SEPARATOR.'old_file.md';
        file_put_contents($existingFile, 'old content');

        $generator = $this->createGenerator();

        $config = [
            'output_path' => $this->outputDir,
            'source_directories' => [$this->fixturesDir],
            'excluded_directories' => [],
            'file_extensions' => ['php'],
            'include_private_methods' => false,
            'title' => 'Test Documentation',
            '_skip_path_validation' => true, // Allow temp dirs in tests
        ];

        $generator->generate($config);

        $this->assertFileDoesNotExist($existingFile);
        $this->assertFileExists($this->outputDir.DIRECTORY_SEPARATOR.'README.md');
    }

    public function test_it_counts_properties_correctly(): void
    {
        $generator = $this->createGenerator();

        $config = [
            'output_path' => $this->outputDir,
            'source_directories' => [$this->fixturesDir],
            'excluded_directories' => [],
            'file_extensions' => ['php'],
            'include_private_methods' => false,
            'title' => 'Test Documentation',
            '_skip_path_validation' => true, // Allow temp dirs in tests
        ];

        $stats = $generator->generate($config);

        $this->assertGreaterThan(0, $stats['properties_documented']);
    }
}
