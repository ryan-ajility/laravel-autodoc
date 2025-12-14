<?php

namespace Ajility\LaravelAutodoc\Tests\Unit\AST;

use Ajility\LaravelAutodoc\Services\AST\DocblockExtractor;
use PhpParser\ParserFactory;
use PHPUnit\Framework\TestCase;

class DocblockExtractorTest extends TestCase
{
    private DocblockExtractor $extractor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->extractor = new DocblockExtractor;
    }

    public function test_extracts_doc_comment_from_node(): void
    {
        $code = <<<'PHP'
<?php
/**
 * This is a class docblock.
 */
class TestClass
{
}
PHP;

        $parser = (new ParserFactory)->createForNewestSupportedVersion();
        $ast = $parser->parse($code);
        $classNode = $ast[0];

        $docblock = $this->extractor->extract($classNode);

        $this->assertNotNull($docblock);
        $this->assertStringContainsString('This is a class docblock.', $docblock);
    }

    public function test_returns_null_when_no_doc_comment(): void
    {
        $code = <<<'PHP'
<?php
class TestClass
{
}
PHP;

        $parser = (new ParserFactory)->createForNewestSupportedVersion();
        $ast = $parser->parse($code);
        $classNode = $ast[0];

        $docblock = $this->extractor->extract($classNode);

        $this->assertNull($docblock);
    }

    public function test_extracts_method_doc_comment(): void
    {
        $code = <<<'PHP'
<?php
class TestClass
{
    /**
     * This is a method docblock.
     * @param string $name
     * @return void
     */
    public function testMethod(string $name): void
    {
    }
}
PHP;

        $parser = (new ParserFactory)->createForNewestSupportedVersion();
        $ast = $parser->parse($code);
        $classNode = $ast[0];
        $methodNode = $classNode->stmts[0];

        $docblock = $this->extractor->extract($methodNode);

        $this->assertNotNull($docblock);
        $this->assertStringContainsString('This is a method docblock.', $docblock);
        $this->assertStringContainsString('@param string $name', $docblock);
        $this->assertStringContainsString('@return void', $docblock);
    }

    public function test_extracts_property_doc_comment(): void
    {
        $code = <<<'PHP'
<?php
class TestClass
{
    /**
     * This is a property docblock.
     * @var string
     */
    private string $name;
}
PHP;

        $parser = (new ParserFactory)->createForNewestSupportedVersion();
        $ast = $parser->parse($code);
        $classNode = $ast[0];
        $propertyNode = $classNode->stmts[0];

        $docblock = $this->extractor->extract($propertyNode);

        $this->assertNotNull($docblock);
        $this->assertStringContainsString('This is a property docblock.', $docblock);
        $this->assertStringContainsString('@var string', $docblock);
    }

    public function test_extracts_multiline_doc_comment(): void
    {
        $code = <<<'PHP'
<?php
/**
 * This is a multi-line
 * docblock that spans
 * several lines.
 *
 * @param string $param1 First parameter
 * @param int $param2 Second parameter
 * @return bool
 */
function testFunction(string $param1, int $param2): bool
{
    return true;
}
PHP;

        $parser = (new ParserFactory)->createForNewestSupportedVersion();
        $ast = $parser->parse($code);
        $functionNode = $ast[0];

        $docblock = $this->extractor->extract($functionNode);

        $this->assertNotNull($docblock);
        $this->assertStringContainsString('multi-line', $docblock);
        $this->assertStringContainsString('several lines', $docblock);
        $this->assertStringContainsString('@param string $param1', $docblock);
        $this->assertStringContainsString('@return bool', $docblock);
    }
}
