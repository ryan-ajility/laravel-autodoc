<?php

namespace Ajility\LaravelAutodoc\Tests\Unit;

use Ajility\LaravelAutodoc\Tests\TestCase;
use Ajility\LaravelAutodoc\Utils\PathValidator;

class PathValidatorTest extends TestCase
{
    private const DEFAULT_DIRECTORY_PERMISSIONS = 0755;

    private PathValidator $validator;

    private string $projectRoot;

    private string $tempTestDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new PathValidator;

        // Use the actual project root (parent of vendor directory)
        $this->projectRoot = dirname(__DIR__, 2);

        // Create a temporary test directory for our tests
        $this->tempTestDir = sys_get_temp_dir().'/autodoc_pathvalidator_'.uniqid();
        mkdir($this->tempTestDir, self::DEFAULT_DIRECTORY_PERMISSIONS, true);
    }

    protected function tearDown(): void
    {

        if (is_dir($this->tempTestDir)) {
            $this->deleteDirectory($this->tempTestDir);

            parent::tearDown();

        }

    }

    /**
     * Test that valid paths within project root are accepted
     */
    public function test_accepts_valid_path_within_project_root(): void
    {
        // Test with relative path
        $validPath = $this->projectRoot.'/storage/docs';

        $this->validator->validateOutputPath($validPath, $this->projectRoot);

        $this->assertTrue(true); // If no exception thrown, test passes
    }

    /**
     * Test that valid relative paths are accepted
     */
    public function test_accepts_valid_relative_path(): void
    {
        $relativePath = 'storage/app/docs';

        // Should not throw exception
        $this->validator->validateOutputPath($relativePath, $this->projectRoot);

        $this->assertTrue(true);
    }

    /**
     * Test that paths outside project root are rejected
     */
    public function test_rejects_path_outside_project_root(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('outside the allowed project root');

        $outsidePath = '/etc/passwd';

        $this->validator->validateOutputPath($outsidePath, $this->projectRoot);
    }

    /**
     * Test that path traversal attempts with ../ are blocked
     */
    public function test_blocks_simple_path_traversal(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('outside the allowed project root');

        // Try to escape to parent directory
        $traversalPath = $this->projectRoot.'/../etc/important';

        $this->validator->validateOutputPath($traversalPath, $this->projectRoot);
    }

    /**
     * Test that multiple path traversal segments are blocked
     */
    public function test_blocks_multiple_path_traversal_segments(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        // Multiple traversals to escape project root
        $traversalPath = '../../../../../../etc/passwd';

        $this->validator->validateOutputPath($traversalPath, $this->projectRoot);
    }

    /**
     * Test that relative path traversal is blocked
     */
    public function test_blocks_relative_path_traversal(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $traversalPath = 'docs/../../etc/important';

        $this->validator->validateOutputPath($traversalPath, $this->projectRoot);
    }

    /**
     * Test that symlink attacks are prevented
     */
    public function test_prevents_symlink_attack(): void
    {
        // Create a symlink pointing to /etc
        $symlinkPath = $this->tempTestDir.'/evil_symlink';

        // Skip test if we can't create symlinks (Windows without admin rights)
        if (! @symlink('/etc', $symlinkPath)) {
            $this->markTestSkipped('Cannot create symlinks on this system');

            return;
        }

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('outside the allowed project root');

        try {
            $this->validator->validateOutputPath($symlinkPath, $this->projectRoot);
        } finally {
            // Clean up symlink
            if (is_link($symlinkPath)) {
                unlink($symlinkPath);
            }
        }
    }

    /**
     * Test that symlinks within allowed boundaries are accepted
     */
    public function test_accepts_symlink_within_project(): void
    {
        // Create a directory and symlink within temp dir
        $realDir = $this->tempTestDir.'/real_docs';
        mkdir($realDir, self::DEFAULT_DIRECTORY_PERMISSIONS, true);

        $symlinkPath = $this->tempTestDir.'/linked_docs';

        // Skip test if we can't create symlinks
        if (! @symlink($realDir, $symlinkPath)) {
            $this->markTestSkipped('Cannot create symlinks on this system');

            return;
        }

        try {
            // This should work because both symlink and target are within tempTestDir
            $this->validator->validateOutputPath($symlinkPath, $this->tempTestDir);
            $this->assertTrue(true);
        } finally {
            // Clean up
            if (is_link($symlinkPath)) {
                unlink($symlinkPath);
            }
        }
    }

    /**
     * Test that absolute paths outside project are rejected
     */
    public function test_rejects_absolute_path_outside_project(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        // Try various sensitive directories
        $sensitivePaths = [
            '/etc/cron.d',
            '/var/www',
            '/usr/local',
        ];

        $this->validator->validateOutputPath($sensitivePaths[0], $this->projectRoot);
    }

    /**
     * Test source directory validation - accepts valid directory
     */
    public function test_validates_source_directory_within_project(): void
    {
        $validSourceDir = $this->projectRoot.'/src';

        $this->validator->validateSourceDirectory($validSourceDir, $this->projectRoot);

        $this->assertTrue(true);
    }

    /**
     * Test source directory validation - rejects directory outside project
     */
    public function test_rejects_source_directory_outside_project(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('outside the allowed project root');

        $this->validator->validateSourceDirectory('/etc', $this->projectRoot);
    }

    /**
     * Test source directory validation - blocks traversal attempts
     */
    public function test_blocks_source_directory_traversal(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $traversalPath = $this->projectRoot.'/../../../etc';

        $this->validator->validateSourceDirectory($traversalPath, $this->projectRoot);
    }

    /**
     * Test that empty paths are rejected
     */
    public function test_rejects_empty_path(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('cannot be empty');

        $this->validator->validateOutputPath('', $this->projectRoot);
    }

    /**
     * Test that whitespace-only paths are rejected
     */
    public function test_rejects_whitespace_only_path(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('cannot be empty');

        $this->validator->validateOutputPath('   ', $this->projectRoot);
    }

    /**
     * Test that null bytes in path are rejected (security issue)
     */
    public function test_rejects_null_bytes_in_path(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('contains invalid characters');

        $pathWithNullByte = "storage/docs\0.php";

        $this->validator->validateOutputPath($pathWithNullByte, $this->projectRoot);
    }

    /**
     * Test edge case: path equals project root
     */
    public function test_accepts_path_equal_to_project_root(): void
    {
        // Documenting at project root should be allowed (though not recommended)
        $this->validator->validateOutputPath($this->projectRoot, $this->projectRoot);

        $this->assertTrue(true);
    }

    /**
     * Test edge case: path with trailing slashes
     */
    public function test_handles_trailing_slashes(): void
    {
        $pathWithTrailingSlash = $this->projectRoot.'/storage/docs/';

        $this->validator->validateOutputPath($pathWithTrailingSlash, $this->projectRoot);

        $this->assertTrue(true);
    }

    /**
     * Test that . and .. are properly resolved
     */
    public function test_resolves_dot_segments(): void
    {
        // ./storage/./docs should work
        $pathWithDots = $this->projectRoot.'/./storage/./docs';

        $this->validator->validateOutputPath($pathWithDots, $this->projectRoot);

        $this->assertTrue(true);
    }

    /**
     * Test Windows-style paths are handled correctly
     */
    public function test_handles_windows_style_paths(): void
    {
        if (DIRECTORY_SEPARATOR !== '\\') {
            $this->markTestSkipped('This test is only relevant on Windows');
        }

        $windowsPath = $this->projectRoot.'\\storage\\docs';

        $this->validator->validateOutputPath($windowsPath, $this->projectRoot);

        $this->assertTrue(true);
    }

    /**
     * Test that UNC paths are handled appropriately (Windows)
     */
    public function test_handles_unc_paths(): void
    {
        if (DIRECTORY_SEPARATOR !== '\\') {
            $this->markTestSkipped('This test is only relevant on Windows');
        }

        $this->expectException(\InvalidArgumentException::class);

        $uncPath = '\\\\server\\share\\folder';

        $this->validator->validateOutputPath($uncPath, $this->projectRoot);
    }

    /**
     * Test array of source directories
     */
    public function test_validates_multiple_source_directories(): void
    {
        $sourceDirectories = [
            $this->projectRoot.'/src',
            $this->projectRoot.'/app',
            $this->projectRoot.'/lib',
        ];

        foreach ($sourceDirectories as $dir) {
            $this->validator->validateSourceDirectory($dir, $this->projectRoot);
        }

        $this->assertTrue(true);
    }

    /**
     * Test that one invalid directory in array is caught
     */
    public function test_catches_invalid_directory_in_array(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $sourceDirectories = [
            $this->projectRoot.'/src',
            '/etc',  // Invalid!
            $this->projectRoot.'/app',
        ];

        foreach ($sourceDirectories as $dir) {
            $this->validator->validateSourceDirectory($dir, $this->projectRoot);
        }
    }

    /**
     * Test case sensitivity on case-sensitive filesystems
     */
    public function test_path_case_sensitivity(): void
    {
        // This test documents behavior, actual validation depends on filesystem
        $lowerPath = $this->projectRoot.'/storage/docs';
        $upperPath = $this->projectRoot.'/STORAGE/DOCS';

        // Both should validate without throwing (actual file existence is separate concern)
        $this->validator->validateOutputPath($lowerPath, $this->projectRoot);
        $this->validator->validateOutputPath($upperPath, $this->projectRoot);

        $this->assertTrue(true);
    }

    /**
     * Test error message quality - should provide actionable information
     */
    public function test_error_messages_are_descriptive(): void
    {
        try {
            $this->validator->validateOutputPath('/etc/passwd', $this->projectRoot);
            $this->fail('Expected exception was not thrown');
        } catch (\InvalidArgumentException $e) {
            // Error message should contain helpful information
            $message = $e->getMessage();
            $this->assertStringContainsString('outside', $message);
            $this->assertStringContainsString('project root', $message);
        }
    }

    /**
     * Test that validator provides clear guidance for valid alternatives
     */
    public function test_error_message_suggests_valid_path(): void
    {
        try {
            $this->validator->validateOutputPath('../../etc/passwd', $this->projectRoot);
            $this->fail('Expected exception was not thrown');
        } catch (\InvalidArgumentException $e) {
            $message = $e->getMessage();
            // Should give users a hint about what would be valid
            $this->assertNotEmpty($message);
            $this->assertGreaterThan(50, strlen($message), 'Error message should be descriptive');
        }
    }
}
