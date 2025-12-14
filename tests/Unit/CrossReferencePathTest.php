<?php

namespace Ajility\LaravelAutodoc\Tests\Unit;

use Ajility\LaravelAutodoc\Services\DocumentationGenerator;
use Ajility\LaravelAutodoc\Tests\Helpers\CreatesDocumentationGenerator;
use Ajility\LaravelAutodoc\Tests\TestCase;

/**
 * Tests for validating cross-reference paths between documented classes
 */
class CrossReferencePathTest extends TestCase
{
    use CreatesDocumentationGenerator;

    private DocumentationGenerator $generator;

    private string $outputDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->generator = $this->createDocumentationGenerator();
        $this->outputDir = sys_get_temp_dir().'/autodoc_crossref_'.uniqid();
    }

    protected function tearDown(): void
    {
        if (is_dir($this->outputDir)) {
            $this->deleteDirectory($this->outputDir);
        }

        parent::tearDown();
    }

    /**
     * Test cross-reference paths between classes in same directory
     */
    public function test_cross_reference_same_directory(): void
    {
        $testDir = sys_get_temp_dir().'/autodoc_same_dir_'.uniqid();
        mkdir($testDir.'/src', 0777, true);

        // Create interface and implementing class
        file_put_contents($testDir.'/src/PaymentInterface.php', '<?php
namespace App;

interface PaymentInterface
{
    public function process(): bool;
}
');

        file_put_contents($testDir.'/src/PaymentService.php', '<?php
namespace App;

/**
 * Payment Service
 */
class PaymentService implements PaymentInterface
{
    public function process(): bool
    {
        return true;
    }
}
');

        $outputDir = $testDir.'/docs';

        $config = [
            'output_path' => $outputDir,
            'source_directories' => [$testDir.'/src'],
            'excluded_directories' => [],
            'file_extensions' => ['php'],
            'include_private_methods' => false,
            'title' => 'Same Dir Test',
            '_skip_path_validation' => true, // Allow temp dirs in tests
        ];

        $this->generator->generate($config);

        // Check PaymentService documentation
        $serviceDoc = $outputDir.'/PaymentService.md';
        $this->assertFileExists($serviceDoc);

        $content = file_get_contents($serviceDoc);

        // Should contain a link to PaymentInterface
        $this->assertStringContainsString('PaymentInterface', $content);

        // Extract the link to verify it's relative
        if (preg_match('/\[PaymentInterface\]\(([^)]+)\)/', $content, $matches)) {
            $linkPath = $matches[1];

            // Should be a simple relative path since they're in the same directory
            $this->assertEquals('PaymentInterface.md', $linkPath);

            // Verify the link resolves correctly
            $targetFile = dirname($serviceDoc).'/'.$linkPath;
            $this->assertFileExists($targetFile);
        }

        // Cleanup
        $this->deleteDirectory($testDir);
    }

    /**
     * Test cross-reference paths between classes in different directories
     */
    public function test_cross_reference_different_directories(): void
    {
        $testDir = sys_get_temp_dir().'/autodoc_diff_dir_'.uniqid();
        mkdir($testDir.'/src/Contracts', 0777, true);
        mkdir($testDir.'/src/Services', 0777, true);

        // Create interface in Contracts
        file_put_contents($testDir.'/src/Contracts/PaymentInterface.php', '<?php
namespace App\Contracts;

interface PaymentInterface
{
    public function process(): bool;
}
');

        // Create implementing class in Services
        file_put_contents($testDir.'/src/Services/PaymentService.php', '<?php
namespace App\Services;

use App\Contracts\PaymentInterface;

/**
 * Payment Service
 */
class PaymentService implements PaymentInterface
{
    public function process(): bool
    {
        return true;
    }
}
');

        $outputDir = $testDir.'/docs';

        $config = [
            'output_path' => $outputDir,
            'source_directories' => [$testDir.'/src'],
            'excluded_directories' => [],
            'file_extensions' => ['php'],
            'include_private_methods' => false,
            'title' => 'Different Dir Test',
            '_skip_path_validation' => true, // Allow temp dirs in tests
        ];

        $this->generator->generate($config);

        // Check PaymentService documentation
        $serviceDoc = $outputDir.'/Services/PaymentService.md';
        $this->assertFileExists($serviceDoc);

        $content = file_get_contents($serviceDoc);

        // Extract the link to PaymentInterface
        if (preg_match('/\[PaymentInterface\]\(([^)]+)\)/', $content, $matches)) {
            $linkPath = $matches[1];

            // Should use ../ to go up from Services to root, then into Contracts
            $this->assertStringContainsString('..', $linkPath);
            $this->assertStringContainsString('Contracts', $linkPath);

            // Verify the link resolves correctly
            $serviceDocDir = dirname($serviceDoc);
            $targetFile = realpath($serviceDocDir.'/'.$linkPath);

            $this->assertNotFalse($targetFile, 'Cross-reference link should resolve to a real file');
            $this->assertStringEndsWith('PaymentInterface.md', $targetFile);
        }

        // Cleanup
        $this->deleteDirectory($testDir);
    }

    /**
     * Test cross-reference with class inheritance
     */
    public function test_cross_reference_with_inheritance(): void
    {
        $testDir = sys_get_temp_dir().'/autodoc_inheritance_'.uniqid();
        mkdir($testDir.'/src/Controllers', 0777, true);

        // Create base controller
        file_put_contents($testDir.'/src/Controllers/BaseController.php', '<?php
namespace App\Controllers;

/**
 * Base Controller
 */
abstract class BaseController
{
    public function __construct()
    {
    }
}
');

        // Create child controller
        file_put_contents($testDir.'/src/Controllers/UserController.php', '<?php
namespace App\Controllers;

/**
 * User Controller
 */
class UserController extends BaseController
{
    public function index(): void
    {
    }
}
');

        $outputDir = $testDir.'/docs';

        $config = [
            'output_path' => $outputDir,
            'source_directories' => [$testDir.'/src'],
            'excluded_directories' => [],
            'file_extensions' => ['php'],
            'include_private_methods' => false,
            'title' => 'Inheritance Test',
            '_skip_path_validation' => true, // Allow temp dirs in tests
        ];

        $this->generator->generate($config);

        // Check UserController documentation
        $userControllerDoc = $outputDir.'/Controllers/UserController.md';
        $this->assertFileExists($userControllerDoc);

        $content = file_get_contents($userControllerDoc);

        // Should contain a link to BaseController
        $this->assertStringContainsString('BaseController', $content);

        // Extract and validate the link
        if (preg_match('/\[BaseController\]\(([^)]+)\)/', $content, $matches)) {
            $linkPath = $matches[1];

            // Should be in the same directory
            $this->assertEquals('BaseController.md', $linkPath);

            // Verify the link resolves
            $targetFile = dirname($userControllerDoc).'/'.$linkPath;
            $this->assertFileExists($targetFile);
        }

        // Cleanup
        $this->deleteDirectory($testDir);
    }

    /**
     * Test cross-reference with deeply nested structures
     */
    public function test_cross_reference_deeply_nested(): void
    {
        $testDir = sys_get_temp_dir().'/autodoc_nested_ref_'.uniqid();
        mkdir($testDir.'/src/Domain/Contracts', 0777, true);
        mkdir($testDir.'/src/Infrastructure/Services/Payment', 0777, true);

        // Create interface deeply nested
        file_put_contents($testDir.'/src/Domain/Contracts/PaymentGateway.php', '<?php
namespace App\Domain\Contracts;

interface PaymentGateway
{
    public function charge(float $amount): bool;
}
');

        // Create implementing class in different deep path
        file_put_contents(
            $testDir.'/src/Infrastructure/Services/Payment/StripeGateway.php',
            '<?php
namespace App\Infrastructure\Services\Payment;

use App\Domain\Contracts\PaymentGateway;

/**
 * Stripe Payment Gateway
 */
class StripeGateway implements PaymentGateway
{
    public function charge(float $amount): bool
    {
        return true;
    }
}
'
        );

        $outputDir = $testDir.'/docs';

        $config = [
            'output_path' => $outputDir,
            'source_directories' => [$testDir.'/src'],
            'excluded_directories' => [],
            'file_extensions' => ['php'],
            'include_private_methods' => false,
            'title' => 'Deep Nesting Test',
            '_skip_path_validation' => true, // Allow temp dirs in tests
        ];

        $this->generator->generate($config);

        // Check StripeGateway documentation
        $stripeDoc = $outputDir.'/Infrastructure/Services/Payment/StripeGateway.md';
        $this->assertFileExists($stripeDoc);

        $content = file_get_contents($stripeDoc);

        // Should contain reference to PaymentGateway
        $this->assertStringContainsString('PaymentGateway', $content);

        // Extract and validate the link
        if (preg_match('/\[PaymentGateway\]\(([^)]+)\)/', $content, $matches)) {
            $linkPath = $matches[1];

            // Should navigate up multiple levels
            $this->assertStringStartsWith('../../../', $linkPath);

            // Verify the link resolves correctly
            $stripeDocDir = dirname($stripeDoc);
            $targetFile = realpath($stripeDocDir.'/'.$linkPath);

            $this->assertNotFalse($targetFile,
                "Cross-reference should resolve: $linkPath from $stripeDocDir");
            $this->assertStringEndsWith('PaymentGateway.md', $targetFile);
        }

        // Cleanup
        $this->deleteDirectory($testDir);
    }

    /**
     * Test multiple interface implementations
     */
    public function test_multiple_interface_references(): void
    {
        $testDir = sys_get_temp_dir().'/autodoc_multi_interface_'.uniqid();
        mkdir($testDir.'/src/Contracts', 0777, true);
        mkdir($testDir.'/src/Services', 0777, true);

        // Create multiple interfaces
        file_put_contents($testDir.'/src/Contracts/Loggable.php', '<?php
namespace App\Contracts;

interface Loggable
{
    public function log(string $message): void;
}
');

        file_put_contents($testDir.'/src/Contracts/Cacheable.php', '<?php
namespace App\Contracts;

interface Cacheable
{
    public function cache(string $key, mixed $value): void;
}
');

        // Create class implementing both
        file_put_contents($testDir.'/src/Services/DataService.php', '<?php
namespace App\Services;

use App\Contracts\Loggable;
use App\Contracts\Cacheable;

/**
 * Data Service
 */
class DataService implements Loggable, Cacheable
{
    public function log(string $message): void
    {
    }

    public function cache(string $key, mixed $value): void
    {
    }
}
');

        $outputDir = $testDir.'/docs';

        $config = [
            'output_path' => $outputDir,
            'source_directories' => [$testDir.'/src'],
            'excluded_directories' => [],
            'file_extensions' => ['php'],
            'include_private_methods' => false,
            'title' => 'Multi Interface Test',
            '_skip_path_validation' => true, // Allow temp dirs in tests
        ];

        $this->generator->generate($config);

        // Check DataService documentation
        $dataServiceDoc = $outputDir.'/Services/DataService.md';
        $this->assertFileExists($dataServiceDoc);

        $content = file_get_contents($dataServiceDoc);

        // Should contain links to both interfaces
        $this->assertStringContainsString('Loggable', $content);
        $this->assertStringContainsString('Cacheable', $content);

        // Extract all interface links - they might be in backticks or as links
        // The format could be: [`Loggable`](path) or [Loggable](path)
        preg_match_all('/\[`?(Loggable|Cacheable)`?\]\(([^)]+)\)/', $content, $matches);

        $this->assertGreaterThanOrEqual(2, count($matches[0]),
            'Should have links to both interfaces');

        // Verify each link resolves
        foreach ($matches[2] as $linkPath) {
            $dataServiceDir = dirname($dataServiceDoc);
            $targetFile = realpath($dataServiceDir.'/'.$linkPath);

            $this->assertNotFalse($targetFile,
                "Interface link should resolve: $linkPath");
        }

        // Cleanup
        $this->deleteDirectory($testDir);
    }

    /**
     * Test that all cross-reference links in generated docs are valid
     */
    public function test_all_cross_references_resolve_correctly(): void
    {
        // Use the fixture files
        $fixturesDir = __DIR__.'/../fixtures/src';

        $config = [
            'output_path' => $this->outputDir,
            'source_directories' => [$fixturesDir],
            'excluded_directories' => [],
            'file_extensions' => ['php'],
            'include_private_methods' => false,
            'title' => 'Cross Reference Validation',
            '_skip_path_validation' => true, // Allow temp dirs in tests
        ];

        $this->generator->generate($config);

        // Get all markdown files
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->outputDir)
        );

        $brokenLinks = [];

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'md' && $file->getFilename() !== 'README.md') {
                $content = file_get_contents($file->getPathname());
                $docDir = dirname($file->getPathname());

                // Find all markdown links to other classes
                preg_match_all('/\[([^\]]+)\]\(([^)]+\.md)\)/', $content, $matches);

                foreach ($matches[2] as $linkPath) {
                    // Resolve the link relative to the current doc file
                    $targetPath = $docDir.'/'.$linkPath;
                    $resolvedPath = realpath($targetPath);

                    if ($resolvedPath === false || ! file_exists($resolvedPath)) {
                        $brokenLinks[] = [
                            'from' => $file->getFilename(),
                            'link' => $linkPath,
                            'attempted_path' => $targetPath,
                        ];
                    }
                }
            }
        }

        $this->assertEmpty($brokenLinks,
            'All cross-reference links should resolve to existing files. Broken links: '.
            print_r($brokenLinks, true));
    }

    /**
     * Test cross-references don't contain absolute paths
     */
    public function test_cross_references_are_relative_not_absolute(): void
    {
        $testDir = sys_get_temp_dir().'/autodoc_relative_ref_'.uniqid();
        mkdir($testDir.'/src', 0777, true);

        file_put_contents($testDir.'/src/BaseClass.php', '<?php
/** Base */
class BaseClass {}
');

        file_put_contents($testDir.'/src/ChildClass.php', '<?php
/** Child */
class ChildClass extends BaseClass {}
');

        $outputDir = $testDir.'/docs';

        $config = [
            'output_path' => $outputDir,
            'source_directories' => [$testDir.'/src'],
            'excluded_directories' => [],
            'file_extensions' => ['php'],
            'include_private_methods' => false,
            'title' => 'Relative Reference Test',
            '_skip_path_validation' => true, // Allow temp dirs in tests
        ];

        $this->generator->generate($config);

        $childDoc = $outputDir.'/ChildClass.md';
        $content = file_get_contents($childDoc);

        // Find cross-reference links
        preg_match_all('/\[([^\]]+)\]\(([^)]+\.md)\)/', $content, $matches);

        foreach ($matches[2] as $linkPath) {
            // Should not contain absolute path indicators
            $this->assertStringNotContainsString($testDir, $linkPath);
            $this->assertStringNotContainsString('/Users/', $linkPath);
            $this->assertStringNotContainsString('/home/', $linkPath);
            $this->assertStringNotContainsString('C:\\', $linkPath);
            $this->assertStringNotContainsString(sys_get_temp_dir(), $linkPath);

            // Should be a relative path or just a filename
            $this->assertTrue(
                ! str_starts_with($linkPath, '/') || str_starts_with($linkPath, '../'),
                "Link should be relative: $linkPath"
            );
        }

        // Cleanup
        $this->deleteDirectory($testDir);
    }
}
