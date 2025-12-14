<?php

namespace Ajility\LaravelAutodoc\Tests\Unit;

use Ajility\LaravelAutodoc\Services\DirectoryScanner;
use PHPUnit\Framework\TestCase;

class DirectoryScannerTest extends TestCase
{
    private DirectoryScanner $scanner;

    private string $testDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->scanner = new DirectoryScanner;
        $this->testDir = __DIR__.'/../fixtures';
    }

    public function test_it_scans_directory_for_php_files(): void
    {
        $files = $this->scanner->scan([$this->testDir.'/src']);

        $this->assertNotEmpty($files);
        $this->assertContainsOnly(\SplFileInfo::class, $files);

        // Should find our test fixtures
        $fileNames = array_map(fn ($file) => $file->getFilename(), $files);
        $this->assertContains('SampleClass.php', $fileNames);
        $this->assertContains('SampleInterface.php', $fileNames);
        $this->assertContains('SampleTrait.php', $fileNames);
        $this->assertContains('ExtendedClass.php', $fileNames);
    }

    public function test_it_excludes_specified_paths(): void
    {
        $testDir = sys_get_temp_dir().'/autodoc_test_'.uniqid();
        mkdir($testDir);
        mkdir($testDir.'/src');
        mkdir($testDir.'/vendor');

        file_put_contents($testDir.'/src/Test.php', '<?php class Test {}');
        file_put_contents($testDir.'/vendor/Vendor.php', '<?php class Vendor {}');

        $files = $this->scanner->scan([$testDir], ['vendor']);

        $fileNames = array_map(fn ($file) => $file->getFilename(), $files);

        $this->assertContains('Test.php', $fileNames);
        $this->assertNotContains('Vendor.php', $fileNames);

        // Cleanup
        unlink($testDir.'/src/Test.php');
        unlink($testDir.'/vendor/Vendor.php');
        rmdir($testDir.'/src');
        rmdir($testDir.'/vendor');
        rmdir($testDir);
    }

    public function test_it_filters_by_file_extension(): void
    {
        $testDir = sys_get_temp_dir().'/autodoc_test_'.uniqid();
        mkdir($testDir);

        file_put_contents($testDir.'/Test.php', '<?php class Test {}');
        file_put_contents($testDir.'/Test.txt', 'text file');
        file_put_contents($testDir.'/Test.md', '# Markdown');

        $files = $this->scanner->scan([$testDir], [], ['php']);

        $this->assertCount(1, $files);
        $this->assertEquals('Test.php', $files[0]->getFilename());

        // Cleanup
        unlink($testDir.'/Test.php');
        unlink($testDir.'/Test.txt');
        unlink($testDir.'/Test.md');
        rmdir($testDir);
    }

    public function test_it_handles_non_existent_directory(): void
    {
        $files = $this->scanner->scan(['/non/existent/path']);

        $this->assertEmpty($files);
    }

    public function test_it_gets_relative_path(): void
    {
        $basePath = '/var/www/project';
        $filePath = '/var/www/project/src/Services/Test.php';

        $relativePath = $this->scanner->getRelativePath($filePath, $basePath);

        $this->assertEquals('src/Services/Test.php', $relativePath);
    }

    public function test_it_returns_full_path_when_not_within_base_path(): void
    {
        $basePath = '/var/www/project';
        $filePath = '/different/path/Test.php';

        $relativePath = $this->scanner->getRelativePath($filePath, $basePath);

        $this->assertEquals($filePath, $relativePath);
    }

    public function test_it_handles_trailing_slashes_in_base_path(): void
    {
        $basePath = '/var/www/project/';
        $filePath = '/var/www/project/src/Test.php';

        $relativePath = $this->scanner->getRelativePath($filePath, $basePath);

        $this->assertEquals('src/Test.php', $relativePath);
    }
}
