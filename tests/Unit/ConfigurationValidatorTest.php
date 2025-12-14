<?php

namespace Ajility\LaravelAutodoc\Tests\Unit;

use Ajility\LaravelAutodoc\Utils\ConfigurationValidator;
use PHPUnit\Framework\TestCase;

class ConfigurationValidatorTest extends TestCase
{
    private ConfigurationValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new ConfigurationValidator;
    }

    public function test_accepts_valid_configuration(): void
    {
        $config = [
            'output_path' => '/some/valid/path',
            'source_directories' => ['app', 'src'],
            'excluded_directories' => ['vendor', 'node_modules'],
            'file_extensions' => ['php'],
            'include_protected_methods' => true,
            'include_private_methods' => false,
            'title' => 'My Documentation',
        ];

        $errors = $this->validator->validate($config);

        $this->assertEmpty($errors, 'Valid configuration should not produce errors');
    }

    public function test_accepts_minimal_configuration(): void
    {
        $config = [
            'output_path' => '/docs',
            'source_directories' => ['app'],
        ];

        $errors = $this->validator->validate($config);

        $this->assertEmpty($errors, 'Minimal valid configuration should not produce errors');
    }

    public function test_rejects_missing_output_path(): void
    {
        $config = [
            'source_directories' => ['app'],
        ];

        $errors = $this->validator->validate($config);

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('output_path', implode(' ', $errors));
        $this->assertStringContainsString('required', strtolower(implode(' ', $errors)));
    }

    public function test_rejects_missing_source_directories(): void
    {
        $config = [
            'output_path' => '/docs',
        ];

        $errors = $this->validator->validate($config);

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('source_directories', implode(' ', $errors));
        $this->assertStringContainsString('required', strtolower(implode(' ', $errors)));
    }

    public function test_rejects_empty_output_path(): void
    {
        $config = [
            'output_path' => '',
            'source_directories' => ['app'],
        ];

        $errors = $this->validator->validate($config);

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('output_path', implode(' ', $errors));
        $this->assertStringContainsString('non-empty', strtolower(implode(' ', $errors)));
    }

    public function test_rejects_whitespace_only_output_path(): void
    {
        $config = [
            'output_path' => '   ',
            'source_directories' => ['app'],
        ];

        $errors = $this->validator->validate($config);

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('output_path', implode(' ', $errors));
    }

    public function test_rejects_non_string_output_path(): void
    {
        $config = [
            'output_path' => ['docs'],
            'source_directories' => ['app'],
        ];

        $errors = $this->validator->validate($config);

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('output_path', implode(' ', $errors));
        $this->assertStringContainsString('string', strtolower(implode(' ', $errors)));
    }

    public function test_rejects_null_output_path(): void
    {
        $config = [
            'output_path' => null,
            'source_directories' => ['app'],
        ];

        $errors = $this->validator->validate($config);

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('output_path', implode(' ', $errors));
    }

    public function test_rejects_numeric_output_path(): void
    {
        $config = [
            'output_path' => 123,
            'source_directories' => ['app'],
        ];

        $errors = $this->validator->validate($config);

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('output_path', implode(' ', $errors));
        $this->assertStringContainsString('string', strtolower(implode(' ', $errors)));
    }

    public function test_rejects_non_array_source_directories(): void
    {
        $config = [
            'output_path' => '/docs',
            'source_directories' => 'app',
        ];

        $errors = $this->validator->validate($config);

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('source_directories', implode(' ', $errors));
        $this->assertStringContainsString('array', strtolower(implode(' ', $errors)));
    }

    public function test_rejects_empty_source_directories_array(): void
    {
        $config = [
            'output_path' => '/docs',
            'source_directories' => [],
        ];

        $errors = $this->validator->validate($config);

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('source_directories', implode(' ', $errors));
        $this->assertStringContainsString('at least one', strtolower(implode(' ', $errors)));
    }

    public function test_rejects_source_directories_with_non_string_elements(): void
    {
        $config = [
            'output_path' => '/docs',
            'source_directories' => ['app', 123, 'src'],
        ];

        $errors = $this->validator->validate($config);

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('source_directories', implode(' ', $errors));
        $this->assertStringContainsString('string', strtolower(implode(' ', $errors)));
    }

    public function test_rejects_source_directories_with_empty_strings(): void
    {
        $config = [
            'output_path' => '/docs',
            'source_directories' => ['app', '', 'src'],
        ];

        $errors = $this->validator->validate($config);

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('source_directories', implode(' ', $errors));
    }

    public function test_rejects_non_array_excluded_directories(): void
    {
        $config = [
            'output_path' => '/docs',
            'source_directories' => ['app'],
            'excluded_directories' => 'vendor',
        ];

        $errors = $this->validator->validate($config);

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('excluded_directories', implode(' ', $errors));
        $this->assertStringContainsString('array', strtolower(implode(' ', $errors)));
    }

    public function test_accepts_empty_excluded_directories(): void
    {
        $config = [
            'output_path' => '/docs',
            'source_directories' => ['app'],
            'excluded_directories' => [],
        ];

        $errors = $this->validator->validate($config);

        $this->assertEmpty($errors, 'Empty excluded_directories should be acceptable');
    }

    public function test_rejects_excluded_directories_with_non_string_elements(): void
    {
        $config = [
            'output_path' => '/docs',
            'source_directories' => ['app'],
            'excluded_directories' => ['vendor', 123],
        ];

        $errors = $this->validator->validate($config);

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('excluded_directories', implode(' ', $errors));
    }

    public function test_rejects_non_array_file_extensions(): void
    {
        $config = [
            'output_path' => '/docs',
            'source_directories' => ['app'],
            'file_extensions' => 'php',
        ];

        $errors = $this->validator->validate($config);

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('file_extensions', implode(' ', $errors));
        $this->assertStringContainsString('array', strtolower(implode(' ', $errors)));
    }

    public function test_rejects_file_extensions_with_non_string_elements(): void
    {
        $config = [
            'output_path' => '/docs',
            'source_directories' => ['app'],
            'file_extensions' => ['php', 456],
        ];

        $errors = $this->validator->validate($config);

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('file_extensions', implode(' ', $errors));
    }

    public function test_rejects_non_boolean_include_protected_methods(): void
    {
        $config = [
            'output_path' => '/docs',
            'source_directories' => ['app'],
            'include_protected_methods' => 'yes',
        ];

        $errors = $this->validator->validate($config);

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('include_protected_methods', implode(' ', $errors));
        $this->assertStringContainsString('boolean', strtolower(implode(' ', $errors)));
    }

    public function test_rejects_non_boolean_include_private_methods(): void
    {
        $config = [
            'output_path' => '/docs',
            'source_directories' => ['app'],
            'include_private_methods' => 1,
        ];

        $errors = $this->validator->validate($config);

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('include_private_methods', implode(' ', $errors));
        $this->assertStringContainsString('boolean', strtolower(implode(' ', $errors)));
    }

    public function test_rejects_non_string_title(): void
    {
        $config = [
            'output_path' => '/docs',
            'source_directories' => ['app'],
            'title' => 123,
        ];

        $errors = $this->validator->validate($config);

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('title', implode(' ', $errors));
        $this->assertStringContainsString('string', strtolower(implode(' ', $errors)));
    }

    public function test_accepts_empty_title(): void
    {
        $config = [
            'output_path' => '/docs',
            'source_directories' => ['app'],
            'title' => '',
        ];

        $errors = $this->validator->validate($config);

        $this->assertEmpty($errors, 'Empty title should be acceptable as it\'s optional');
    }

    public function test_reports_multiple_errors(): void
    {
        $config = [
            'output_path' => 123,
            'source_directories' => 'app',
            'include_private_methods' => 'yes',
        ];

        $errors = $this->validator->validate($config);

        $this->assertCount(3, $errors);
        $this->assertStringContainsString('output_path', implode(' ', $errors));
        $this->assertStringContainsString('source_directories', implode(' ', $errors));
        $this->assertStringContainsString('include_private_methods', implode(' ', $errors));
    }

    public function test_ignores_unknown_keys(): void
    {
        $config = [
            'output_path' => '/docs',
            'source_directories' => ['app'],
            'unknown_key' => 'some value',
            'another_unknown' => 123,
        ];

        $errors = $this->validator->validate($config);

        $this->assertEmpty($errors, 'Unknown keys should be ignored without error');
    }

    public function test_validates_all_known_fields(): void
    {
        $config = [
            'output_path' => 123,
            'source_directories' => 'not-array',
            'excluded_directories' => 'not-array',
            'file_extensions' => 'not-array',
            'include_protected_methods' => 'not-boolean',
            'include_private_methods' => 'not-boolean',
            'title' => 456,
        ];

        $errors = $this->validator->validate($config);

        $this->assertGreaterThanOrEqual(7, count($errors));
    }

    public function test_accepts_boolean_false_values(): void
    {
        $config = [
            'output_path' => '/docs',
            'source_directories' => ['app'],
            'include_protected_methods' => false,
            'include_private_methods' => false,
        ];

        $errors = $this->validator->validate($config);

        $this->assertEmpty($errors);
    }

    public function test_accepts_boolean_true_values(): void
    {
        $config = [
            'output_path' => '/docs',
            'source_directories' => ['app'],
            'include_protected_methods' => true,
            'include_private_methods' => true,
        ];

        $errors = $this->validator->validate($config);

        $this->assertEmpty($errors);
    }
}
