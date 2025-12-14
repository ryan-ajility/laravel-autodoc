<?php

namespace Ajility\LaravelAutodoc\Tests\Feature;

use Ajility\LaravelAutodoc\Services\DocumentationGenerator;
use Ajility\LaravelAutodoc\Tests\Helpers\FileSystemHelper;
use Ajility\LaravelAutodoc\Tests\TestCase;
use InvalidArgumentException;

/**
 * Comprehensive security tests for path traversal vulnerabilities.
 */
class PathTraversalTest extends TestCase
{
    use FileSystemHelper;

    private string $testDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->testDir = $this->createTempDirectory('path_traversal_test_');
    }

    protected function tearDown(): void
    {
        $this->deleteDirectory($this->testDir);
        parent::tearDown();
    }

    /**
     * Test that absolute paths outside project root are rejected for output path.
     */
    public function test_rejects_output_path_outside_allowed_directory(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Output path');
        $this->expectExceptionMessage('outside the allowed project root');

        // Create a valid source directory within test dir
        $sourceDir = $this->testDir.'/src';
        mkdir($sourceDir, 0755, true);

        $config = [
            'output_path' => '/etc/passwd',  // Absolute path outside project
            'source_directories' => [$sourceDir],
            'excluded_directories' => [],
            'file_extensions' => ['php'],
            'include_private_methods' => false,
            'title' => 'Test Documentation',
        ];

        $generator = app(DocumentationGenerator::class);
        $generator->generate($config);
    }

    /**
     * Test that relative path traversal with ../ is rejected for output path.
     */
    public function test_rejects_relative_path_traversal(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('outside the allowed project root');

        // Create a valid source directory
        $sourceDir = $this->testDir.'/src';
        mkdir($sourceDir, 0755, true);

        // Try to traverse up and out of project root
        // The number of ../ depends on how deep we are in the directory structure
        $traversalPath = str_repeat('../', 10).'etc/important';

        $config = [
            'output_path' => $traversalPath,
            'source_directories' => [$sourceDir],
            'excluded_directories' => [],
            'file_extensions' => ['php'],
            'include_private_methods' => false,
            'title' => 'Test Documentation',
        ];

        $generator = app(DocumentationGenerator::class);
        $generator->generate($config);
    }

    /**
     * Test that symlink attacks are prevented for output path.
     */
    public function test_rejects_symlink_attack_on_output_path(): void
    {
        // Create a symlink pointing outside the project
        $symlinkPath = $this->testDir.'/evil_link';
        $targetPath = '/tmp';

        // Create symlink to /tmp (which is outside project root)
        if (! symlink($targetPath, $symlinkPath)) {
            $this->markTestSkipped('Unable to create symlink for testing');
        }

        // Create a valid source directory
        $sourceDir = $this->testDir.'/src';
        mkdir($sourceDir, 0755, true);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('outside the allowed project root');

        try {
            $config = [
                'output_path' => $symlinkPath,
                'source_directories' => [$sourceDir],
                'excluded_directories' => [],
                'file_extensions' => ['php'],
                'include_private_methods' => false,
                'title' => 'Test Documentation',
            ];

            $generator = app(DocumentationGenerator::class);
            $generator->generate($config);
        } finally {
            if (is_link($symlinkPath)) {
                unlink($symlinkPath);
            }
        }
    }

    /**
     * Test that source directories outside project are rejected.
     */
    public function test_rejects_source_directory_outside_project(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Source directory');
        $this->expectExceptionMessage('outside the allowed project root');

        // Valid output path within test dir
        $outputPath = $this->testDir.'/docs';

        $config = [
            'output_path' => $outputPath,
            'source_directories' => ['/etc'],  // System directory outside project
            'excluded_directories' => [],
            'file_extensions' => ['php'],
            'include_private_methods' => false,
            'title' => 'Test Documentation',
        ];

        $generator = app(DocumentationGenerator::class);
        $generator->generate($config);
    }

    /**
     * Test that relative path traversal in source directory is rejected.
     */
    public function test_rejects_relative_path_traversal_in_source_directory(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('outside the allowed project root');

        // Valid output path
        $outputPath = $this->testDir.'/docs';

        // Try to traverse outside project root with source directory
        $traversalPath = str_repeat('../', 10).'etc';

        $config = [
            'output_path' => $outputPath,
            'source_directories' => [$traversalPath],
            'excluded_directories' => [],
            'file_extensions' => ['php'],
            'include_private_methods' => false,
            'title' => 'Test Documentation',
        ];

        $generator = app(DocumentationGenerator::class);
        $generator->generate($config);
    }

    /**
     * Test that symlink attacks on source directories are prevented.
     */
    public function test_rejects_symlink_attack_on_source_directory(): void
    {
        // Create a symlink pointing outside the project
        $symlinkPath = $this->testDir.'/source_link';
        $targetPath = '/tmp';

        if (! symlink($targetPath, $symlinkPath)) {
            $this->markTestSkipped('Unable to create symlink for testing');
        }

        // Valid output path
        $outputPath = $this->testDir.'/docs';

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('outside the allowed project root');

        try {
            $config = [
                'output_path' => $outputPath,
                'source_directories' => [$symlinkPath],
                'excluded_directories' => [],
                'file_extensions' => ['php'],
                'include_private_methods' => false,
                'title' => 'Test Documentation',
            ];

            $generator = app(DocumentationGenerator::class);
            $generator->generate($config);
        } finally {
            if (is_link($symlinkPath)) {
                unlink($symlinkPath);
            }
        }
    }

    /**
     * Test that valid paths within project boundaries are accepted.
     */
    public function test_accepts_valid_paths_within_project_root(): void
    {
        // Use directories within the actual project root for this test
        $projectRoot = base_path();
        $sourceDir = $projectRoot.'/tests/fixtures';
        $outputPath = $projectRoot.'/storage/test_docs_'.uniqid();

        // Create directories if they don't exist
        if (! is_dir($sourceDir)) {
            mkdir($sourceDir, 0755, true);
        }

        // Create a test PHP file
        $testFile = $sourceDir.'/TestClass.php';
        file_put_contents($testFile, '<?php class TestClass {}');

        try {
            $config = [
                'output_path' => $outputPath,
                'source_directories' => [$sourceDir],
                'excluded_directories' => [],
                'file_extensions' => ['php'],
                'include_private_methods' => false,
                'title' => 'Test Documentation',
            ];

            $generator = app(DocumentationGenerator::class);
            $stats = $generator->generate($config);

            // Should complete successfully
            $this->assertIsArray($stats);
            $this->assertArrayHasKey('files_scanned', $stats);
        } finally {
            // Clean up
            if (file_exists($testFile)) {
                unlink($testFile);
            }
            if (is_dir($outputPath)) {
                $this->deleteDirectory($outputPath);
            }
        }
    }

    /**
     * Test that paths with null bytes are rejected.
     */
    public function test_rejects_paths_with_null_bytes(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('null byte');

        // Create valid source directory
        $sourceDir = $this->testDir.'/src';
        mkdir($sourceDir, 0755, true);

        // Try to use null byte in path (security attack vector)
        $config = [
            'output_path' => $this->testDir."/docs\0/evil",
            'source_directories' => [$sourceDir],
            'excluded_directories' => [],
            'file_extensions' => ['php'],
            'include_private_methods' => false,
            'title' => 'Test Documentation',
        ];

        $generator = app(DocumentationGenerator::class);
        $generator->generate($config);
    }

    /**
     * Test that empty paths are rejected.
     */
    public function test_rejects_empty_output_path(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('non-empty');

        // Create valid source directory
        $sourceDir = $this->testDir.'/src';
        mkdir($sourceDir, 0755, true);

        $config = [
            'output_path' => '',  // Empty path
            'source_directories' => [$sourceDir],
            'excluded_directories' => [],
            'file_extensions' => ['php'],
            'include_private_methods' => false,
            'title' => 'Test Documentation',
        ];

        $generator = app(DocumentationGenerator::class);
        $generator->generate($config);
    }

    /**
     * Test that whitespace-only paths are rejected.
     */
    public function test_rejects_whitespace_only_output_path(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('non-empty');

        // Create valid source directory
        $sourceDir = $this->testDir.'/src';
        mkdir($sourceDir, 0755, true);

        $config = [
            'output_path' => '   ',  // Whitespace only
            'source_directories' => [$sourceDir],
            'excluded_directories' => [],
            'file_extensions' => ['php'],
            'include_private_methods' => false,
            'title' => 'Test Documentation',
        ];

        $generator = app(DocumentationGenerator::class);
        $generator->generate($config);
    }

    /**
     * Test that paths attempting to escape via multiple ../ sequences are blocked.
     */
    public function test_rejects_multiple_parent_directory_traversal(): void
    {
        $this->expectException(InvalidArgumentException::class);

        // Create valid source directory
        $sourceDir = $this->testDir.'/src';
        mkdir($sourceDir, 0755, true);

        // Create a deeply nested path then try to escape
        $deepPath = $this->testDir.'/a/b/c/d';
        mkdir($deepPath, 0755, true);

        // From the deep path, try to traverse way out of project
        $traversalPath = $deepPath.'/'.str_repeat('../', 20).'etc';

        $config = [
            'output_path' => $traversalPath,
            'source_directories' => [$sourceDir],
            'excluded_directories' => [],
            'file_extensions' => ['php'],
            'include_private_methods' => false,
            'title' => 'Test Documentation',
        ];

        $generator = app(DocumentationGenerator::class);
        $generator->generate($config);
    }

    /**
     * Test that mixed path separators in traversal attempts are blocked.
     */
    public function test_rejects_mixed_separator_traversal(): void
    {
        $this->expectException(InvalidArgumentException::class);

        // Create valid source directory
        $sourceDir = $this->testDir.'/src';
        mkdir($sourceDir, 0755, true);

        // Try path traversal with mixed separators
        $traversalPath = $this->testDir.'/docs/../../../etc';

        $config = [
            'output_path' => $traversalPath,
            'source_directories' => [$sourceDir],
            'excluded_directories' => [],
            'file_extensions' => ['php'],
            'include_private_methods' => false,
            'title' => 'Test Documentation',
        ];

        $generator = app(DocumentationGenerator::class);
        $generator->generate($config);
    }
}
