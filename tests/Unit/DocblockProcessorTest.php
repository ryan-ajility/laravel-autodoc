<?php

namespace Ajility\LaravelAutodoc\Tests\Unit;

use Ajility\LaravelAutodoc\Services\DocblockProcessor;
use PHPUnit\Framework\TestCase;

class DocblockProcessorTest extends TestCase
{
    private DocblockProcessor $processor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->processor = new DocblockProcessor;
    }

    public function test_it_formats_basic_docblock(): void
    {
        $docblock = '/** * This is a test description */';

        $result = $this->processor->format($docblock);

        $this->assertEquals('This is a test description', $result);
    }

    public function test_it_removes_leading_asterisks(): void
    {
        $docblock = "/**\n * Line one\n * Line two\n * Line three\n */";

        $result = $this->processor->format($docblock);

        $this->assertEquals("Line one\nLine two\nLine three", $result);
    }

    public function test_it_removes_annotation_tags(): void
    {
        $docblock = "/**\n * Description here\n * @param string \$test Test param\n * @return bool Return value\n */";

        $result = $this->processor->format($docblock);

        $this->assertEquals('Description here', trim($result));
        $this->assertStringNotContainsString('@param', $result);
        $this->assertStringNotContainsString('@return', $result);
    }

    public function test_it_handles_multiline_descriptions(): void
    {
        $docblock = "/**\n * This is a multiline\n * description that spans\n * several lines\n */";

        $result = $this->processor->format($docblock);

        $expected = "This is a multiline\ndescription that spans\nseveral lines";
        $this->assertEquals($expected, $result);
    }

    public function test_it_handles_null_docblock(): void
    {
        $result = $this->processor->format(null);

        $this->assertEquals('', $result);
    }

    public function test_it_handles_empty_docblock(): void
    {
        $docblock = '/** */';

        $result = $this->processor->format($docblock);

        $this->assertEquals('', $result);
    }

    public function test_it_extracts_param_description(): void
    {
        $docblock = "/**\n * Method description\n * @param string \$name The name parameter\n * @param int \$age The age parameter\n */";

        $result = $this->processor->extractParamDescription($docblock, 'name');

        $this->assertEquals('The name parameter', $result);
    }

    public function test_it_extracts_param_description_with_type_union(): void
    {
        $docblock = "/**\n * @param string|int \$id The identifier\n */";

        $result = $this->processor->extractParamDescription($docblock, 'id');

        $this->assertEquals('The identifier', $result);
    }

    public function test_it_extracts_param_description_with_nullable_type(): void
    {
        $docblock = "/**\n * @param ?string \$name Optional name parameter\n */";

        $result = $this->processor->extractParamDescription($docblock, 'name');

        $this->assertEquals('Optional name parameter', $result);
    }

    public function test_it_extracts_param_description_with_namespace(): void
    {
        $docblock = "/**\n * @param \\App\\Models\\User \$user The user model\n */";

        $result = $this->processor->extractParamDescription($docblock, 'user');

        $this->assertEquals('The user model', $result);
    }

    public function test_it_returns_empty_when_param_not_found(): void
    {
        $docblock = "/**\n * @param string \$name The name\n */";

        $result = $this->processor->extractParamDescription($docblock, 'nonexistent');

        $this->assertEquals('', $result);
    }

    public function test_it_returns_empty_when_docblock_is_null_for_param(): void
    {
        $result = $this->processor->extractParamDescription(null, 'name');

        $this->assertEquals('', $result);
    }

    public function test_it_extracts_return_description(): void
    {
        $docblock = "/**\n * Method description\n * @return bool True on success\n */";

        $result = $this->processor->extractReturnDescription($docblock);

        $this->assertEquals('True on success', $result);
    }

    public function test_it_extracts_return_description_with_complex_type(): void
    {
        $docblock = "/**\n * @return array<string, mixed> The configuration array\n */";

        $result = $this->processor->extractReturnDescription($docblock);

        $this->assertEquals('The configuration array', $result);
    }

    public function test_it_extracts_return_description_with_union_type(): void
    {
        $docblock = "/**\n * @return string|int|null The result or null\n */";

        $result = $this->processor->extractReturnDescription($docblock);

        $this->assertEquals('The result or null', $result);
    }

    public function test_it_returns_empty_when_no_return_tag(): void
    {
        $docblock = "/**\n * Method description\n * @param string \$name Name\n */";

        $result = $this->processor->extractReturnDescription($docblock);

        $this->assertEquals('', $result);
    }

    public function test_it_returns_empty_when_docblock_is_null_for_return(): void
    {
        $result = $this->processor->extractReturnDescription(null);

        $this->assertEquals('', $result);
    }

    public function test_it_handles_docblock_with_multiple_annotations(): void
    {
        $docblock = "/**\n * Complex method\n * @param string \$a First param\n * @param int \$b Second param\n * @throws \\Exception When error occurs\n * @return bool Success status\n * @deprecated Use newMethod instead\n */";

        $formatted = $this->processor->format($docblock);
        $paramDesc = $this->processor->extractParamDescription($docblock, 'a');
        $returnDesc = $this->processor->extractReturnDescription($docblock);

        $this->assertEquals('Complex method', trim($formatted));
        $this->assertEquals('First param', $paramDesc);
        $this->assertEquals('Success status', $returnDesc);
    }

    public function test_it_trims_whitespace_from_descriptions(): void
    {
        $docblock = "/**\n * @param string \$name   The name with extra spaces   \n */";

        $result = $this->processor->extractParamDescription($docblock, 'name');

        $this->assertEquals('The name with extra spaces', $result);
    }

    public function test_it_handles_docblock_with_no_description_only_tags(): void
    {
        $docblock = "/**\n * @param string \$name\n * @return bool\n */";

        $result = $this->processor->format($docblock);

        $this->assertEquals('', $result);
    }

    public function test_it_preserves_markdown_in_docblock(): void
    {
        $docblock = "/**\n * This method accepts **bold** and *italic* text\n * - Item 1\n * - Item 2\n */";

        $result = $this->processor->format($docblock);

        $this->assertStringContainsString('**bold**', $result);
        $this->assertStringContainsString('*italic*', $result);
        $this->assertStringContainsString('- Item 1', $result);
    }

    public function test_it_handles_param_with_array_type(): void
    {
        $docblock = "/**\n * @param array \$options Configuration options\n */";

        $result = $this->processor->extractParamDescription($docblock, 'options');

        $this->assertEquals('Configuration options', $result);
    }

    public function test_it_handles_return_with_namespaced_class(): void
    {
        $docblock = "/**\n * @return \\App\\Models\\User The authenticated user\n */";

        $result = $this->processor->extractReturnDescription($docblock);

        $this->assertEquals('The authenticated user', $result);
    }
}
