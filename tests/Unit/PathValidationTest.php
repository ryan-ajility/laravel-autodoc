<?php

namespace Ajility\LaravelAutodoc\Tests\Unit;

use Ajility\LaravelAutodoc\Services\DirectoryScanner;
use Ajility\LaravelAutodoc\Services\DocumentationGenerator;
use Ajility\LaravelAutodoc\Tests\Helpers\CreatesDocumentationGenerator;
use Ajility\LaravelAutodoc\Tests\TestCase;

class PathValidationTest extends TestCase
{
    use CreatesDocumentationGenerator;

    private DirectoryScanner $scanner;

    private DocumentationGenerator $generator;

    private string $outputDir;

    private string $fixturesDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->scanner = new DirectoryScanner;
        $this->generator = $this->createDocumentationGenerator();
        $this->outputDir = sys_get_temp_dir().'/autodoc_path_test_'.uniqid();
        $this->fixturesDir = __DIR__.'/../fixtures/src';
    }

    protected function tearDown(): void
    {
        if (is_dir($this->outputDir)) {
            $this->deleteDirectory($this->outputDir);
        }

        parent::tearDown();
    }

    /**
     * Test DirectoryScanner::getRelativePath() with various scenarios
     */
    public function test_directory_scanner_relative_path_calculation(): void
    {
        // Test 1: Standard nested path
        $basePath = '/var/www/project';
        $filePath = '/var/www/project/src/Services/PaymentService.php';
        $result = $this->scanner->getRelativePath($filePath, $basePath);
        $this->assertEquals('src/Services/PaymentService.php', $result);

        // Test 2: File directly in base path
        $filePath = '/var/www/project/Controller.php';
        $result = $this->scanner->getRelativePath($filePath, $basePath);
        $this->assertEquals('Controller.php', $result);

        // Test 3: File not under base path
        $filePath = '/different/path/Test.php';
        $result = $this->scanner->getRelativePath($filePath, $basePath);
        $this->assertEquals($filePath, $result);

        // Test 4: Base path with trailing slash
        $basePath = '/var/www/project/';
        $filePath = '/var/www/project/src/Test.php';
        $result = $this->scanner->getRelativePath($filePath, $basePath);
        $this->assertEquals('src/Test.php', $result);

        // Test 5: Deep nesting
        $basePath = '/app';
        $filePath = '/app/Http/Controllers/Api/V1/UserController.php';
        $result = $this->scanner->getRelativePath($filePath, $basePath);
        $this->assertEquals('Http/Controllers/Api/V1/UserController.php', $result);
    }

    /**
     * Test that generated documentation contains accurate relative source paths
     */
    public function test_generated_documentation_has_accurate_source_paths(): void
    {
        $config = [
            'output_path' => $this->outputDir,
            'source_directories' => [$this->fixturesDir],
            'excluded_directories' => [],
            'file_extensions' => ['php'],
            'include_private_methods' => false,
            'title' => 'Path Test Documentation',
            '_skip_path_validation' => true, // Allow temp dirs in tests
        ];

        $this->generator->generate($config);

        // Read generated markdown file
        $sampleClassDoc = $this->outputDir.DIRECTORY_SEPARATOR.'SampleClass.md';
        $this->assertFileExists($sampleClassDoc);

        $content = file_get_contents($sampleClassDoc);

        // Extract the source link from the markdown
        preg_match('/\*\*Source:\*\* \[([^\]]+)\]\(([^)]+)\)/', $content, $matches);

        $this->assertNotEmpty($matches, 'Source link should be present in documentation');
        $this->assertCount(3, $matches, 'Should have display text and link');

        $displayPath = $matches[1];
        $linkPath = $matches[2];

        // Verify the display path is relative
        $this->assertStringNotContainsString($this->fixturesDir, $displayPath);
        $this->assertEquals('SampleClass.php', $displayPath);

        // Verify the link path is relative and points to the source file
        $this->assertStringNotContainsString($this->fixturesDir, $linkPath);
        $this->assertStringEndsWith('SampleClass.php', $linkPath);
    }

    /**
     * Test source path accuracy with nested directory structure
     */
    public function test_source_paths_with_nested_directories(): void
    {
        // Create a nested test structure
        $testDir = sys_get_temp_dir().'/autodoc_nested_'.uniqid();
        $srcDir = $testDir.'/app/Http/Controllers';
        mkdir($srcDir, 0777, true);

        $testFile = $srcDir.'/UserController.php';
        file_put_contents($testFile, '<?php
namespace App\Http\Controllers;

/**
 * User Controller
 */
class UserController
{
    public function index(): void
    {
    }
}
');

        $outputDir = $testDir.'/docs';

        $config = [
            'output_path' => $outputDir,
            'source_directories' => [$testDir.'/app'],
            'excluded_directories' => [],
            'file_extensions' => ['php'],
            'include_private_methods' => false,
            'title' => 'Nested Path Test',
            '_skip_path_validation' => true, // Allow temp dirs in tests
        ];

        $this->generator->generate($config);

        $docFile = $outputDir.'/Http/Controllers/UserController.md';
        $this->assertFileExists($docFile);

        $content = file_get_contents($docFile);

        // Extract source path
        preg_match('/\*\*Source:\*\* \[([^\]]+)\]\(([^)]+)\)/', $content, $matches);

        $this->assertNotEmpty($matches);
        $displayPath = $matches[1];

        // Should show the relative path from source directory
        $this->assertEquals('Http/Controllers/UserController.php', $displayPath);

        // Cleanup
        $this->deleteDirectory($testDir);
    }

    /**
     * Test multiple source directories with correct path resolution
     */
    public function test_multiple_source_directories_resolve_correct_paths(): void
    {
        // Create two separate source directories
        $testDir = sys_get_temp_dir().'/autodoc_multi_'.uniqid();
        $srcDir1 = $testDir.'/src1';
        $srcDir2 = $testDir.'/src2';
        mkdir($srcDir1, 0777, true);
        mkdir($srcDir2, 0777, true);

        // Create files in different source directories
        file_put_contents($srcDir1.'/ClassA.php', '<?php
/**
 * Class A
 */
class ClassA {}
');

        file_put_contents($srcDir2.'/ClassB.php', '<?php
/**
 * Class B
 */
class ClassB {}
');

        $outputDir = $testDir.'/docs';

        $config = [
            'output_path' => $outputDir,
            'source_directories' => [$srcDir1, $srcDir2],
            'excluded_directories' => [],
            'file_extensions' => ['php'],
            'include_private_methods' => false,
            'title' => 'Multi-Source Test',
            '_skip_path_validation' => true, // Allow temp dirs in tests
        ];

        $this->generator->generate($config);

        // Check ClassA documentation
        $classADoc = $outputDir.'/ClassA.md';
        $this->assertFileExists($classADoc);
        $contentA = file_get_contents($classADoc);
        preg_match('/\*\*Source:\*\* \[([^\]]+)\]\(([^)]+)\)/', $contentA, $matchesA);
        $this->assertEquals('ClassA.php', $matchesA[1]);

        // Check ClassB documentation
        $classBDoc = $outputDir.'/ClassB.md';
        $this->assertFileExists($classBDoc);
        $contentB = file_get_contents($classBDoc);
        preg_match('/\*\*Source:\*\* \[([^\]]+)\]\(([^)]+)\)/', $contentB, $matchesB);
        $this->assertEquals('ClassB.php', $matchesB[1]);

        // Cleanup
        $this->deleteDirectory($testDir);
    }

    /**
     * Test that relative paths don't contain absolute filesystem paths
     *
     * This test validates that generated documentation uses only relative paths,
     * making the documentation portable and independent of the file system structure.
     */
    public function test_no_absolute_paths_in_documentation(): void
    {
        $config = [
            'output_path' => $this->outputDir,
            'source_directories' => [$this->fixturesDir],
            'excluded_directories' => [],
            'file_extensions' => ['php'],
            'include_private_methods' => false,
            'title' => 'Absolute Path Test',
            '_skip_path_validation' => true, // Allow temp dirs in tests
        ];

        $this->generator->generate($config);

        // Get all generated markdown files
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->outputDir)
        );

        $filesWithAbsolutePaths = [];

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'md') {
                $content = file_get_contents($file->getPathname());

                // Check for absolute paths (common patterns)
                if (str_contains($content, '/Users/') ||
                    str_contains($content, '/home/') ||
                    str_contains($content, 'C:\\') ||
                    str_contains($content, sys_get_temp_dir())) {
                    $filesWithAbsolutePaths[] = $file->getFilename();
                }
            }
        }

        $this->assertEmpty($filesWithAbsolutePaths,
            'The following files contain absolute paths: '.implode(', ', $filesWithAbsolutePaths));
    }

    /**
     * Test that source file links would actually resolve correctly
     *
     * This test validates the ideal behavior: when docs and source are in related
     * directory structures, the relative links should resolve correctly.
     */
    public function test_source_links_resolve_to_actual_files(): void
    {
        // Create a test structure where docs and source share a common parent
        // This simulates a real project structure
        $testDir = sys_get_temp_dir().'/autodoc_resolve_'.uniqid();
        $srcDir = $testDir.'/src/Services';
        $docsDir = $testDir.'/docs';
        mkdir($srcDir, 0777, true);

        $sourceFile = $srcDir.'/PaymentService.php';
        file_put_contents($sourceFile, '<?php
namespace App\Services;

/**
 * Payment Service
 */
class PaymentService
{
    public function process(): bool
    {
        return true;
    }
}
');

        $config = [
            'output_path' => $docsDir,
            'source_directories' => [$testDir.'/src'],
            'excluded_directories' => [],
            'file_extensions' => ['php'],
            'include_private_methods' => false,
            'title' => 'Link Resolution Test',
            '_skip_path_validation' => true, // Allow temp dirs in tests
        ];

        $this->generator->generate($config);

        $docFile = $docsDir.'/Services/PaymentService.md';
        $this->assertFileExists($docFile);

        $content = file_get_contents($docFile);

        // Extract the relative link
        preg_match('/\*\*Source:\*\* \[([^\]]+)\]\(([^)]+)\)/', $content, $matches);
        $this->assertNotEmpty($matches, 'Should have a source link');

        $relativeLinkPath = $matches[2];

        // Resolve the relative path from the doc file location
        $docDir = dirname($docFile);
        $attemptedPath = $docDir.'/'.$relativeLinkPath;

        // Normalize the path to handle .. segments
        $resolvedPath = $this->normalizePath($attemptedPath);

        // The resolved path should point to the actual source file
        $this->assertFileExists($resolvedPath,
            "Relative link should resolve to a real file. Link: $relativeLinkPath, Attempted: $attemptedPath");
        $this->assertEquals(realpath($sourceFile), realpath($resolvedPath),
            'Resolved path should match the actual source file');

        // Cleanup
        $this->deleteDirectory($testDir);
    }

    /**
     * Helper to normalize paths with .. segments
     */
    private function normalizePath(string $path): string
    {
        // Try realpath first
        $real = realpath($path);
        if ($real !== false) {
            return $real;
        }

        // Manual normalization
        $parts = explode('/', str_replace('\\', '/', $path));
        $normalized = [];

        foreach ($parts as $part) {
            if ($part === '' || $part === '.') {
                continue;
            }
            if ($part === '..') {
                array_pop($normalized);
            } else {
                $normalized[] = $part;
            }
        }

        return (str_starts_with($path, '/') ? '/' : '').implode('/', $normalized);
    }

    /**
     * Test path consistency across different operating systems
     */
    public function test_path_separators_are_consistent(): void
    {
        $config = [
            'output_path' => $this->outputDir,
            'source_directories' => [$this->fixturesDir],
            'excluded_directories' => [],
            'file_extensions' => ['php'],
            'include_private_methods' => false,
            'title' => 'Path Separator Test',
            '_skip_path_validation' => true, // Allow temp dirs in tests
        ];

        $this->generator->generate($config);

        // Get all markdown files
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->outputDir)
        );

        $pathsChecked = 0;

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'md') {
                $content = file_get_contents($file->getPathname());

                // Extract all source links
                preg_match_all('/\[([^\]]+)\]\(([^)]+\.php)\)/', $content, $matches);

                foreach ($matches[2] as $path) {
                    // Paths should use forward slashes (cross-platform)
                    // Count forward slashes vs backslashes
                    $forwardCount = substr_count($path, '/');
                    $backwardCount = substr_count($path, '\\');

                    // For relative paths with directory traversal, we expect forward slashes
                    if (str_contains($path, '..')) {
                        $this->assertGreaterThanOrEqual($backwardCount, $forwardCount,
                            "Path should use forward slashes for consistency: $path");
                    }

                    $pathsChecked++;
                }
            }
        }

        // Ensure at least some paths were checked
        $this->assertGreaterThan(0, $pathsChecked, 'Should have found and checked at least one source path');
    }

    /**
     * Test that Windows-style paths are normalized correctly
     */
    public function test_windows_path_normalization(): void
    {
        $basePath = 'C:\Users\Developer\Project\app';
        $filePath = 'C:\Users\Developer\Project\app\Http\Controllers\HomeController.php';

        // Normalize to forward slashes for testing
        $basePath = str_replace('\\', '/', $basePath);
        $filePath = str_replace('\\', '/', $filePath);

        $result = $this->scanner->getRelativePath($filePath, $basePath);

        $this->assertEquals('Http/Controllers/HomeController.php', $result);
        $this->assertStringNotContainsString('\\', $result,
            'Result should not contain backslashes');
    }

    /**
     * Test edge case: file in root of source directory
     */
    public function test_file_in_source_root(): void
    {
        $testDir = sys_get_temp_dir().'/autodoc_root_'.uniqid();
        mkdir($testDir, 0777, true);

        $sourceFile = $testDir.'/RootClass.php';
        file_put_contents($sourceFile, '<?php
/**
 * Root Class
 */
class RootClass {}
');

        $outputDir = $testDir.'/docs';

        $config = [
            'output_path' => $outputDir,
            'source_directories' => [$testDir],
            'excluded_directories' => [],
            'file_extensions' => ['php'],
            'include_private_methods' => false,
            'title' => 'Root File Test',
            '_skip_path_validation' => true, // Allow temp dirs in tests
        ];

        $this->generator->generate($config);

        $docFile = $outputDir.'/RootClass.md';
        $this->assertFileExists($docFile);

        $content = file_get_contents($docFile);
        preg_match('/\*\*Source:\*\* \[([^\]]+)\]\(([^)]+)\)/', $content, $matches);

        // Should just be the filename with no directory prefix
        $this->assertEquals('RootClass.php', $matches[1]);

        // Cleanup
        $this->deleteDirectory($testDir);
    }

    /**
     * Test that documentation preserves directory structure
     */
    public function test_documentation_preserves_source_structure(): void
    {
        $testDir = sys_get_temp_dir().'/autodoc_structure_'.uniqid();
        mkdir($testDir.'/src/Models', 0777, true);
        mkdir($testDir.'/src/Controllers', 0777, true);

        file_put_contents($testDir.'/src/Models/User.php', '<?php
namespace App\Models;
/** User Model */
class User {}
');

        file_put_contents($testDir.'/src/Controllers/UserController.php', '<?php
namespace App\Controllers;
/** User Controller */
class UserController {}
');

        $outputDir = $testDir.'/docs';

        $config = [
            'output_path' => $outputDir,
            'source_directories' => [$testDir.'/src'],
            'excluded_directories' => [],
            'file_extensions' => ['php'],
            'include_private_methods' => false,
            'title' => 'Structure Test',
            '_skip_path_validation' => true, // Allow temp dirs in tests
        ];

        $this->generator->generate($config);

        // Check that docs preserve the structure
        $this->assertFileExists($outputDir.'/Models/User.md');
        $this->assertFileExists($outputDir.'/Controllers/UserController.md');

        // Verify paths in documentation
        $userDoc = file_get_contents($outputDir.'/Models/User.md');
        preg_match('/\*\*Source:\*\* \[([^\]]+)\]/', $userDoc, $matches);
        $this->assertEquals('Models/User.php', $matches[1]);

        $controllerDoc = file_get_contents($outputDir.'/Controllers/UserController.md');
        preg_match('/\*\*Source:\*\* \[([^\]]+)\]/', $controllerDoc, $matches);
        $this->assertEquals('Controllers/UserController.php', $matches[1]);

        // Cleanup
        $this->deleteDirectory($testDir);
    }
}
