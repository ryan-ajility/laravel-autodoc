<?php

namespace Ajility\LaravelAutodoc\Tests\Unit;

use Ajility\LaravelAutodoc\Utils\ConfigurationValidator;
use PHPUnit\Framework\TestCase;

/**
 * Comprehensive security tests for input validation.
 *
 * These tests verify that all user inputs are properly validated
 * to prevent type confusion, injection attacks, and other security issues.
 */
class InputValidationSecurityTest extends TestCase
{
    private ConfigurationValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new ConfigurationValidator;
    }

    /**
     * Test that null output path is rejected.
     */
    public function test_rejects_null_output_path(): void
    {
        $errors = $this->validator->validate([
            'output_path' => null,
            'source_directories' => ['src'],
        ]);

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('output_path', implode('', $errors));
    }

    /**
     * Test that array as output path is rejected.
     */
    public function test_rejects_array_as_output_path(): void
    {
        $errors = $this->validator->validate([
            'output_path' => ['docs'],
            'source_directories' => ['src'],
        ]);

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('output_path', implode('', $errors));
    }

    /**
     * Test that integer as output path is rejected.
     */
    public function test_rejects_integer_as_output_path(): void
    {
        $errors = $this->validator->validate([
            'output_path' => 123,
            'source_directories' => ['src'],
        ]);

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('output_path', implode('', $errors));
    }

    /**
     * Test that boolean as output path is rejected.
     */
    public function test_rejects_boolean_as_output_path(): void
    {
        $errors = $this->validator->validate([
            'output_path' => true,
            'source_directories' => ['src'],
        ]);

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('output_path', implode('', $errors));
    }

    /**
     * Test that object as output path is rejected.
     */
    public function test_rejects_object_as_output_path(): void
    {
        $errors = $this->validator->validate([
            'output_path' => new \stdClass,
            'source_directories' => ['src'],
        ]);

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('output_path', implode('', $errors));
    }

    /**
     * Test that empty string output path is rejected.
     */
    public function test_rejects_empty_string_output_path(): void
    {
        $errors = $this->validator->validate([
            'output_path' => '',
            'source_directories' => ['src'],
        ]);

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('non-empty', implode('', $errors));
    }

    /**
     * Test that whitespace-only output path is rejected.
     */
    public function test_rejects_whitespace_only_output_path(): void
    {
        $errors = $this->validator->validate([
            'output_path' => '   ',
            'source_directories' => ['src'],
        ]);

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('non-empty', implode('', $errors));
    }

    /**
     * Test that missing output path is rejected.
     */
    public function test_rejects_missing_output_path(): void
    {
        $errors = $this->validator->validate([
            'source_directories' => ['src'],
        ]);

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('output_path', implode('', $errors));
        $this->assertStringContainsString('required', implode('', $errors));
    }

    /**
     * Test that null source directories is rejected.
     */
    public function test_rejects_null_source_directories(): void
    {
        $errors = $this->validator->validate([
            'output_path' => 'docs',
            'source_directories' => null,
        ]);

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('source_directories', implode('', $errors));
    }

    /**
     * Test that string as source directories is rejected.
     */
    public function test_rejects_string_as_source_directories(): void
    {
        $errors = $this->validator->validate([
            'output_path' => 'docs',
            'source_directories' => 'src',
        ]);

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('source_directories', implode('', $errors));
        $this->assertStringContainsString('array', implode('', $errors));
    }

    /**
     * Test that empty array source directories is rejected.
     */
    public function test_rejects_empty_source_directories(): void
    {
        $errors = $this->validator->validate([
            'output_path' => 'docs',
            'source_directories' => [],
        ]);

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('source_directories', implode('', $errors));
        $this->assertStringContainsString('at least one', implode('', $errors));
    }

    /**
     * Test that source directories with non-string elements is rejected.
     */
    public function test_rejects_source_directories_with_non_string_elements(): void
    {
        $errors = $this->validator->validate([
            'output_path' => 'docs',
            'source_directories' => ['src', 123, 'app'],
        ]);

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('source_directories', implode('', $errors));
        $this->assertStringContainsString('string', implode('', $errors));
    }

    /**
     * Test that source directories with empty string elements is rejected.
     */
    public function test_rejects_source_directories_with_empty_string_elements(): void
    {
        $errors = $this->validator->validate([
            'output_path' => 'docs',
            'source_directories' => ['src', '', 'app'],
        ]);

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('non-empty', implode('', $errors));
    }

    /**
     * Test that source directories with whitespace-only elements is rejected.
     */
    public function test_rejects_source_directories_with_whitespace_only_elements(): void
    {
        $errors = $this->validator->validate([
            'output_path' => 'docs',
            'source_directories' => ['src', '   ', 'app'],
        ]);

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('non-empty', implode('', $errors));
    }

    /**
     * Test that missing source directories is rejected.
     */
    public function test_rejects_missing_source_directories(): void
    {
        $errors = $this->validator->validate([
            'output_path' => 'docs',
        ]);

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('source_directories', implode('', $errors));
        $this->assertStringContainsString('required', implode('', $errors));
    }

    /**
     * Test that non-array excluded directories is rejected.
     */
    public function test_rejects_non_array_excluded_directories(): void
    {
        $errors = $this->validator->validate([
            'output_path' => 'docs',
            'source_directories' => ['src'],
            'excluded_directories' => 'vendor',
        ]);

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('excluded_directories', implode('', $errors));
        $this->assertStringContainsString('array', implode('', $errors));
    }

    /**
     * Test that excluded directories with non-string elements is rejected.
     */
    public function test_rejects_excluded_directories_with_non_string_elements(): void
    {
        $errors = $this->validator->validate([
            'output_path' => 'docs',
            'source_directories' => ['src'],
            'excluded_directories' => ['vendor', 123],
        ]);

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('excluded_directories', implode('', $errors));
        $this->assertStringContainsString('string', implode('', $errors));
    }

    /**
     * Test that non-array file extensions is rejected.
     */
    public function test_rejects_non_array_file_extensions(): void
    {
        $errors = $this->validator->validate([
            'output_path' => 'docs',
            'source_directories' => ['src'],
            'file_extensions' => 'php',
        ]);

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('file_extensions', implode('', $errors));
        $this->assertStringContainsString('array', implode('', $errors));
    }

    /**
     * Test that file extensions with non-string elements is rejected.
     */
    public function test_rejects_file_extensions_with_non_string_elements(): void
    {
        $errors = $this->validator->validate([
            'output_path' => 'docs',
            'source_directories' => ['src'],
            'file_extensions' => ['php', true],
        ]);

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('file_extensions', implode('', $errors));
        $this->assertStringContainsString('string', implode('', $errors));
    }

    /**
     * Test that non-boolean include_private_methods is rejected.
     */
    public function test_rejects_non_boolean_include_private_methods(): void
    {
        $errors = $this->validator->validate([
            'output_path' => 'docs',
            'source_directories' => ['src'],
            'include_private_methods' => 'yes',
        ]);

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('include_private_methods', implode('', $errors));
        $this->assertStringContainsString('boolean', implode('', $errors));
    }

    /**
     * Test that integer as include_private_methods is rejected (common mistake: 0/1 instead of false/true).
     */
    public function test_rejects_integer_as_include_private_methods(): void
    {
        $errors = $this->validator->validate([
            'output_path' => 'docs',
            'source_directories' => ['src'],
            'include_private_methods' => 1,
        ]);

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('include_private_methods', implode('', $errors));
        $this->assertStringContainsString('boolean', implode('', $errors));
    }

    /**
     * Test that non-boolean include_protected_methods is rejected.
     */
    public function test_rejects_non_boolean_include_protected_methods(): void
    {
        $errors = $this->validator->validate([
            'output_path' => 'docs',
            'source_directories' => ['src'],
            'include_protected_methods' => 'no',
        ]);

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('include_protected_methods', implode('', $errors));
        $this->assertStringContainsString('boolean', implode('', $errors));
    }

    /**
     * Test that non-string title is rejected.
     */
    public function test_rejects_non_string_title(): void
    {
        $errors = $this->validator->validate([
            'output_path' => 'docs',
            'source_directories' => ['src'],
            'title' => 123,
        ]);

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('title', implode('', $errors));
        $this->assertStringContainsString('string', implode('', $errors));
    }

    /**
     * Test that array as title is rejected.
     */
    public function test_rejects_array_as_title(): void
    {
        $errors = $this->validator->validate([
            'output_path' => 'docs',
            'source_directories' => ['src'],
            'title' => ['My', 'Documentation'],
        ]);

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('title', implode('', $errors));
        $this->assertStringContainsString('string', implode('', $errors));
    }

    /**
     * Test that valid configuration passes validation.
     */
    public function test_accepts_valid_configuration(): void
    {
        $errors = $this->validator->validate([
            'output_path' => 'docs',
            'source_directories' => ['src', 'app'],
        ]);

        $this->assertEmpty($errors);
    }

    /**
     * Test that valid configuration with all optional fields passes validation.
     */
    public function test_accepts_valid_configuration_with_optional_fields(): void
    {
        $errors = $this->validator->validate([
            'output_path' => 'docs/api',
            'source_directories' => ['src', 'app'],
            'excluded_directories' => ['vendor', 'tests'],
            'file_extensions' => ['php'],
            'include_private_methods' => true,
            'include_protected_methods' => false,
            'title' => 'My API Documentation',
        ]);

        $this->assertEmpty($errors);
    }

    /**
     * Test that multiple validation errors are all reported together.
     */
    public function test_reports_multiple_errors(): void
    {
        $errors = $this->validator->validate([
            'output_path' => 123,  // Invalid: should be string
            'source_directories' => 'src',  // Invalid: should be array
            'excluded_directories' => 'vendor',  // Invalid: should be array
            'include_private_methods' => 'yes',  // Invalid: should be boolean
        ]);

        $this->assertCount(4, $errors);
        $this->assertStringContainsString('output_path', implode('', $errors));
        $this->assertStringContainsString('source_directories', implode('', $errors));
        $this->assertStringContainsString('excluded_directories', implode('', $errors));
        $this->assertStringContainsString('include_private_methods', implode('', $errors));
    }

    /**
     * Test that empty array excluded directories is accepted (it's optional and empty is valid).
     */
    public function test_accepts_empty_excluded_directories(): void
    {
        $errors = $this->validator->validate([
            'output_path' => 'docs',
            'source_directories' => ['src'],
            'excluded_directories' => [],
        ]);

        $this->assertEmpty($errors);
    }

    /**
     * Test that empty array file extensions is accepted (will use defaults).
     */
    public function test_accepts_empty_file_extensions(): void
    {
        $errors = $this->validator->validate([
            'output_path' => 'docs',
            'source_directories' => ['src'],
            'file_extensions' => [],
        ]);

        $this->assertEmpty($errors);
    }

    /**
     * Test that empty string title is accepted (not ideal but not a security issue).
     */
    public function test_accepts_empty_string_title(): void
    {
        $errors = $this->validator->validate([
            'output_path' => 'docs',
            'source_directories' => ['src'],
            'title' => '',
        ]);

        $this->assertEmpty($errors);
    }
}
