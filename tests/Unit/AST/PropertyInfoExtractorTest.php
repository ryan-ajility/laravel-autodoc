<?php

namespace Ajility\LaravelAutodoc\Tests\Unit\AST;

use Ajility\LaravelAutodoc\Services\AST\DocblockExtractor;
use Ajility\LaravelAutodoc\Services\AST\PropertyInfoExtractor;
use Ajility\LaravelAutodoc\Services\AST\TypeStringConverter;
use PhpParser\ParserFactory;
use PHPUnit\Framework\TestCase;

class PropertyInfoExtractorTest extends TestCase
{
    private PropertyInfoExtractor $extractor;

    protected function setUp(): void
    {
        parent::setUp();
        $typeConverter = new TypeStringConverter;
        $docblockExtractor = new DocblockExtractor;
        $this->extractor = new PropertyInfoExtractor($typeConverter, $docblockExtractor);
    }

    public function test_extracts_public_property(): void
    {
        $code = <<<'PHP'
<?php
class TestClass
{
    public string $name;
}
PHP;

        $parser = (new ParserFactory)->createForNewestSupportedVersion();
        $ast = $parser->parse($code);
        $classNode = $ast[0];
        $propertyNode = $classNode->stmts[0];

        $result = $this->extractor->extract($propertyNode);

        $this->assertCount(1, $result);
        $this->assertEquals('name', $result[0]['name']);
        $this->assertEquals('public', $result[0]['visibility']);
        $this->assertEquals('string', $result[0]['type']);
        $this->assertFalse($result[0]['isStatic']);
        $this->assertNull($result[0]['default']);
    }

    public function test_extracts_private_property(): void
    {
        $code = <<<'PHP'
<?php
class TestClass
{
    private int $count;
}
PHP;

        $parser = (new ParserFactory)->createForNewestSupportedVersion();
        $ast = $parser->parse($code);
        $classNode = $ast[0];
        $propertyNode = $classNode->stmts[0];

        $result = $this->extractor->extract($propertyNode);

        $this->assertEquals('count', $result[0]['name']);
        $this->assertEquals('private', $result[0]['visibility']);
        $this->assertEquals('int', $result[0]['type']);
    }

    public function test_extracts_protected_property(): void
    {
        $code = <<<'PHP'
<?php
class TestClass
{
    protected bool $active;
}
PHP;

        $parser = (new ParserFactory)->createForNewestSupportedVersion();
        $ast = $parser->parse($code);
        $classNode = $ast[0];
        $propertyNode = $classNode->stmts[0];

        $result = $this->extractor->extract($propertyNode);

        $this->assertEquals('active', $result[0]['name']);
        $this->assertEquals('protected', $result[0]['visibility']);
        $this->assertEquals('bool', $result[0]['type']);
    }

    public function test_extracts_static_property(): void
    {
        $code = <<<'PHP'
<?php
class TestClass
{
    public static string $instance;
}
PHP;

        $parser = (new ParserFactory)->createForNewestSupportedVersion();
        $ast = $parser->parse($code);
        $classNode = $ast[0];
        $propertyNode = $classNode->stmts[0];

        $result = $this->extractor->extract($propertyNode);

        $this->assertEquals('instance', $result[0]['name']);
        $this->assertTrue($result[0]['isStatic']);
    }

    public function test_extracts_property_with_default(): void
    {
        $code = <<<'PHP'
<?php
class TestClass
{
    public string $name = 'default';
}
PHP;

        $parser = (new ParserFactory)->createForNewestSupportedVersion();
        $ast = $parser->parse($code);
        $classNode = $ast[0];
        $propertyNode = $classNode->stmts[0];

        $result = $this->extractor->extract($propertyNode);

        $this->assertEquals('name', $result[0]['name']);
        $this->assertEquals("'default'", $result[0]['default']);
    }

    public function test_extracts_property_with_docblock(): void
    {
        $code = <<<'PHP'
<?php
class TestClass
{
    /**
     * The user's name.
     * @var string
     */
    public string $name;
}
PHP;

        $parser = (new ParserFactory)->createForNewestSupportedVersion();
        $ast = $parser->parse($code);
        $classNode = $ast[0];
        $propertyNode = $classNode->stmts[0];

        $result = $this->extractor->extract($propertyNode);

        $this->assertEquals('name', $result[0]['name']);
        $this->assertNotNull($result[0]['docblock']);
        $this->assertStringContainsString("The user's name.", $result[0]['docblock']);
    }

    public function test_extracts_multiple_properties_in_one_declaration(): void
    {
        $code = <<<'PHP'
<?php
class TestClass
{
    public string $firstName, $lastName;
}
PHP;

        $parser = (new ParserFactory)->createForNewestSupportedVersion();
        $ast = $parser->parse($code);
        $classNode = $ast[0];
        $propertyNode = $classNode->stmts[0];

        $result = $this->extractor->extract($propertyNode);

        $this->assertCount(2, $result);
        $this->assertEquals('firstName', $result[0]['name']);
        $this->assertEquals('lastName', $result[1]['name']);
        $this->assertEquals('string', $result[0]['type']);
        $this->assertEquals('string', $result[1]['type']);
    }

    public function test_extracts_property_without_type(): void
    {
        $code = <<<'PHP'
<?php
class TestClass
{
    public $data;
}
PHP;

        $parser = (new ParserFactory)->createForNewestSupportedVersion();
        $ast = $parser->parse($code);
        $classNode = $ast[0];
        $propertyNode = $classNode->stmts[0];

        $result = $this->extractor->extract($propertyNode);

        $this->assertEquals('data', $result[0]['name']);
        $this->assertNull($result[0]['type']);
    }

    public function test_extracts_property_with_array_default(): void
    {
        $code = <<<'PHP'
<?php
class TestClass
{
    public array $items = [];
}
PHP;

        $parser = (new ParserFactory)->createForNewestSupportedVersion();
        $ast = $parser->parse($code);
        $classNode = $ast[0];
        $propertyNode = $classNode->stmts[0];

        $result = $this->extractor->extract($propertyNode);

        $this->assertEquals('items', $result[0]['name']);
        $this->assertEquals('[]', $result[0]['default']);
    }
}
