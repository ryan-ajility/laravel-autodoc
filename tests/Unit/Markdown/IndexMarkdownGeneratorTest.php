<?php

namespace Ajility\LaravelAutodoc\Tests\Unit\Markdown;

use Ajility\LaravelAutodoc\Services\Markdown\IndexMarkdownGenerator;
use PHPUnit\Framework\TestCase;

class IndexMarkdownGeneratorTest extends TestCase
{
    private IndexMarkdownGenerator $generator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->generator = new IndexMarkdownGenerator;
    }

    public function test_it_generates_index_with_title(): void
    {
        $title = 'API Documentation';

        $result = $this->generator->generate($title);

        $this->assertStringContainsString('# API Documentation', $result);
    }

    public function test_it_includes_generation_timestamp(): void
    {
        $title = 'Test Documentation';

        $result = $this->generator->generate($title);

        $this->assertStringContainsString('Generated on:', $result);
        $this->assertMatchesRegularExpression('/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/', $result);
    }

    public function test_it_includes_overview_section(): void
    {
        $title = 'Documentation';

        $result = $this->generator->generate($title);

        $this->assertStringContainsString('## Overview', $result);
        $this->assertStringContainsString('Laravel Autodoc', $result);
    }

    public function test_it_includes_whats_included_section(): void
    {
        $title = 'Documentation';

        $result = $this->generator->generate($title);

        $this->assertStringContainsString("## What's Included", $result);
        $this->assertStringContainsString('All Classes, Interfaces, and Traits', $result);
        $this->assertStringContainsString('Method Signatures', $result);
    }

    public function test_it_includes_navigation_section(): void
    {
        $title = 'Documentation';

        $result = $this->generator->generate($title);

        $this->assertStringContainsString('## Navigation', $result);
    }

    public function test_it_includes_regeneration_instructions(): void
    {
        $title = 'Documentation';

        $result = $this->generator->generate($title);

        $this->assertStringContainsString('## How to Regenerate', $result);
        $this->assertStringContainsString('php artisan autodoc:generate', $result);
    }

    public function test_it_includes_regeneration_options(): void
    {
        $title = 'Documentation';

        $result = $this->generator->generate($title);

        $this->assertStringContainsString('### Regeneration Options', $result);
        $this->assertStringContainsString('--private', $result);
        $this->assertStringContainsString('--protected', $result);
        $this->assertStringContainsString('--source', $result);
        $this->assertStringContainsString('--exclude', $result);
    }

    public function test_it_includes_when_to_regenerate_section(): void
    {
        $title = 'Documentation';

        $result = $this->generator->generate($title);

        $this->assertStringContainsString('### When to Regenerate', $result);
        $this->assertStringContainsString('add new classes', $result);
    }

    public function test_it_includes_configuration_section(): void
    {
        $title = 'Documentation';

        $result = $this->generator->generate($title);

        $this->assertStringContainsString('## Configuration', $result);
        $this->assertStringContainsString('autodoc.json', $result);
        $this->assertStringContainsString('config/autodoc.php', $result);
    }

    public function test_it_includes_understanding_format_section(): void
    {
        $title = 'Documentation';

        $result = $this->generator->generate($title);

        $this->assertStringContainsString('## Understanding the Documentation Format', $result);
        $this->assertStringContainsString('### Header Section', $result);
        $this->assertStringContainsString('### Description', $result);
        $this->assertStringContainsString('### Properties', $result);
        $this->assertStringContainsString('### Methods', $result);
    }

    public function test_it_includes_important_notes(): void
    {
        $title = 'Documentation';

        $result = $this->generator->generate($title);

        $this->assertStringContainsString('## Important Notes', $result);
        $this->assertStringContainsString('Do Not Edit Directly', $result);
        $this->assertStringContainsString('Source of Truth', $result);
    }

    public function test_it_includes_workflow_integration_section(): void
    {
        $title = 'Documentation';

        $result = $this->generator->generate($title);

        $this->assertStringContainsString('## Integration with Development Workflow', $result);
        $this->assertStringContainsString('### In CI/CD', $result);
        $this->assertStringContainsString('### Git Hooks', $result);
        $this->assertStringContainsString('### Documentation Reviews', $result);
    }

    public function test_it_includes_troubleshooting_section(): void
    {
        $title = 'Documentation';

        $result = $this->generator->generate($title);

        $this->assertStringContainsString('## Troubleshooting', $result);
        $this->assertStringContainsString('Documentation is missing classes', $result);
        $this->assertStringContainsString('Private methods not showing', $result);
    }

    public function test_it_includes_package_information(): void
    {
        $title = 'Documentation';

        $result = $this->generator->generate($title);

        $this->assertStringContainsString('## Package Information', $result);
        $this->assertStringContainsString('ajility/laravel-autodoc', $result);
        $this->assertStringContainsString('PHP 8.2+', $result);
        $this->assertStringContainsString('Laravel 12+', $result);
    }

    public function test_it_includes_code_examples(): void
    {
        $title = 'Documentation';

        $result = $this->generator->generate($title);

        $this->assertStringContainsString('```bash', $result);
        $this->assertStringContainsString('```yaml', $result);
    }

    public function test_it_includes_github_actions_example(): void
    {
        $title = 'Documentation';

        $result = $this->generator->generate($title);

        $this->assertStringContainsString('Example GitHub Actions', $result);
        $this->assertStringContainsString('- name: Generate Documentation', $result);
    }

    public function test_it_includes_git_hook_example(): void
    {
        $title = 'Documentation';

        $result = $this->generator->generate($title);

        $this->assertStringContainsString('#!/bin/sh', $result);
        $this->assertStringContainsString('git add storage/app/docs/', $result);
    }

    public function test_it_mentions_php_parser(): void
    {
        $title = 'Documentation';

        $result = $this->generator->generate($title);

        $this->assertStringContainsString('nikic/php-parser', $result);
    }

    public function test_generated_markdown_is_valid(): void
    {
        $title = 'Test Documentation';

        $result = $this->generator->generate($title);

        // Should start with H1
        $this->assertStringStartsWith('# ', $result);

        // Should contain multiple sections
        $this->assertGreaterThan(10, substr_count($result, '##'));

        // Should not have syntax errors (basic check)
        $this->assertStringNotContainsString('```bash```', $result);
        $this->assertStringNotContainsString('```yaml```', $result);
    }

    public function test_it_includes_warning_emojis(): void
    {
        $title = 'Documentation';

        $result = $this->generator->generate($title);

        // Check for warning emoji in important notes
        $this->assertStringContainsString('⚠️', $result);
    }

    public function test_it_has_consistent_markdown_formatting(): void
    {
        $title = 'Documentation';

        $result = $this->generator->generate($title);

        // Headers should have consistent spacing
        $this->assertMatchesRegularExpression('/\n## [A-Z]/', $result);
        $this->assertMatchesRegularExpression('/\n### [A-Z]/', $result);
    }
}
