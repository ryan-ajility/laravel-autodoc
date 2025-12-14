<?php

namespace Ajility\LaravelAutodoc\Tests\Unit;

use Ajility\LaravelAutodoc\Services\PathResolver;
use PHPUnit\Framework\TestCase;

class PathResolverTest extends TestCase
{
    private PathResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resolver = new PathResolver;
    }

    public function test_it_calculates_relative_path_between_files_in_same_directory(): void
    {
        $from = 'Services/PaymentService.md';
        $to = 'Services/NotificationService.md';

        $result = $this->resolver->getRelativePath($from, $to);

        $this->assertEquals('NotificationService.md', $result);
    }

    public function test_it_calculates_relative_path_from_subdirectory_to_parent(): void
    {
        $from = 'Services/Payment/Stripe.md';
        $to = 'Services/PaymentService.md';

        $result = $this->resolver->getRelativePath($from, $to);

        $this->assertEquals('../PaymentService.md', $result);
    }

    public function test_it_calculates_relative_path_from_parent_to_subdirectory(): void
    {
        $from = 'Services/PaymentService.md';
        $to = 'Services/Payment/Stripe.md';

        $result = $this->resolver->getRelativePath($from, $to);

        $this->assertEquals('Payment/Stripe.md', $result);
    }

    public function test_it_calculates_relative_path_between_different_branches(): void
    {
        $from = 'Services/PaymentService.md';
        $to = 'Contracts/PaymentProcessorInterface.md';

        $result = $this->resolver->getRelativePath($from, $to);

        $this->assertEquals('../Contracts/PaymentProcessorInterface.md', $result);
    }

    public function test_it_calculates_relative_path_with_deep_nesting(): void
    {
        $from = 'Services/Payment/Processors/Stripe/StripeProcessor.md';
        $to = 'Contracts/PaymentProcessorInterface.md';

        $result = $this->resolver->getRelativePath($from, $to);

        $this->assertEquals('../../../../Contracts/PaymentProcessorInterface.md', $result);
    }

    public function test_it_handles_paths_with_leading_directories(): void
    {
        $from = 'app/Services/PaymentService.md';
        $to = 'app/Contracts/PaymentInterface.md';

        $result = $this->resolver->getRelativePath($from, $to);

        $this->assertEquals('../Contracts/PaymentInterface.md', $result);
    }

    public function test_calculate_relative_path_to_source_with_simple_structure(): void
    {
        $relativeDocPath = 'Services/PaymentService.md';
        $absoluteSourcePath = '/Users/test/project/src/Services/PaymentService.php';
        $outputPath = '/Users/test/project/docs';

        $result = $this->resolver->calculateRelativePathToSource(
            $relativeDocPath,
            $absoluteSourcePath,
            $outputPath
        );

        // Should navigate up from docs/Services to docs, then to project root,
        // then down into src/Services
        $this->assertEquals('../../src/Services/PaymentService.php', $result);
    }

    public function test_calculate_relative_path_to_source_falls_back_for_deep_paths(): void
    {
        $relativeDocPath = 'Services/PaymentService.md';
        // Absolute path that would require > 5 levels up
        $absoluteSourcePath = '/a/b/c/d/e/f/g/h/Services/PaymentService.php';
        $outputPath = '/Users/test/project/docs';

        $result = $this->resolver->calculateRelativePathToSource(
            $relativeDocPath,
            $absoluteSourcePath,
            $outputPath
        );

        // Since there's no common prefix (starts with different roots), should fall back
        $this->assertEquals('Services/PaymentService.php', $result);
    }

    public function test_calculate_relative_path_to_source_handles_nested_docs(): void
    {
        $relativeDocPath = 'App/Services/Payment/StripeService.md';
        $absoluteSourcePath = '/Users/test/project/src/App/Services/Payment/StripeService.php';
        $outputPath = '/Users/test/project/docs';

        $result = $this->resolver->calculateRelativePathToSource(
            $relativeDocPath,
            $absoluteSourcePath,
            $outputPath
        );

        // Navigate up from docs/App/Services/Payment (4 levels) to project root, then to src
        $this->assertEquals('../../../../src/App/Services/Payment/StripeService.php', $result);
    }

    public function test_normalize_path_converts_backslashes_to_forward_slashes(): void
    {
        $path = 'C:\Users\Test\project\src';

        $result = $this->resolver->normalizePath($path);

        $this->assertStringNotContainsString('\\', $result);
        $this->assertStringContainsString('/', $result);
    }

    public function test_normalize_path_resolves_dot_segments(): void
    {
        $path = '/Users/test/./project/../project/src';

        $result = $this->resolver->normalizePath($path);

        $this->assertStringNotContainsString('/./', $result);
        $this->assertStringNotContainsString('/..', $result);
        $this->assertEquals('/Users/test/project/src', $result);
    }

    public function test_normalize_path_removes_redundant_slashes(): void
    {
        $path = '/Users//test///project////src';

        $result = $this->resolver->normalizePath($path);

        $this->assertEquals('/Users/test/project/src', $result);
    }

    public function test_normalize_path_handles_nonexistent_paths(): void
    {
        $path = '/Users/test/nonexistent/path/to/file.php';

        $result = $this->resolver->normalizePath($path);

        // Should normalize even if path doesn't exist
        $this->assertEquals('/Users/test/nonexistent/path/to/file.php', $result);
    }

    public function test_normalize_path_handles_relative_paths(): void
    {
        $path = 'relative/path/to/file.php';

        $result = $this->resolver->normalizePath($path);

        $this->assertEquals('relative/path/to/file.php', $result);
    }

    public function test_normalize_path_handles_paths_with_trailing_slash(): void
    {
        $path = '/Users/test/project/';

        $result = $this->resolver->normalizePath($path);

        // May or may not have trailing slash depending on filesystem
        $this->assertStringStartsWith('/Users/test/project', $result);
    }

    public function test_get_relative_path_handles_root_level_files(): void
    {
        $from = 'ClassA.md';
        $to = 'ClassB.md';

        $result = $this->resolver->getRelativePath($from, $to);

        $this->assertEquals('ClassB.md', $result);
    }

    public function test_calculate_relative_path_to_source_with_no_common_prefix(): void
    {
        $relativeDocPath = 'Services/PaymentService.md';
        $absoluteSourcePath = '/completely/different/root/Services/PaymentService.php';
        $outputPath = '/Users/test/project/docs';

        $result = $this->resolver->calculateRelativePathToSource(
            $relativeDocPath,
            $absoluteSourcePath,
            $outputPath
        );

        // Should fall back to template-based path
        $this->assertEquals('Services/PaymentService.php', $result);
    }

    public function test_normalize_path_on_macos_handles_var_symlink(): void
    {
        // On macOS, /var is a symlink to /private/var
        $path = '/var/folders/test/file.php';

        $result = $this->resolver->normalizePath($path);

        // Should normalize to /private/var for consistency
        $this->assertStringStartsWith('/private/var', $result);
    }
}
