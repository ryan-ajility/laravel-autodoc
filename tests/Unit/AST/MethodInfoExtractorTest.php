<?php

namespace Ajility\LaravelAutodoc\Tests\Unit\AST;

use Ajility\LaravelAutodoc\Services\AST\DocblockExtractor;
use Ajility\LaravelAutodoc\Services\AST\MethodInfoExtractor;
use Ajility\LaravelAutodoc\Services\AST\TypeStringConverter;
use PhpParser\ParserFactory;
use PHPUnit\Framework\TestCase;

class MethodInfoExtractorTest extends TestCase
{
    private MethodInfoExtractor $extractor;

    protected function setUp(): void
    {
        parent::setUp();
        $typeConverter = new TypeStringConverter;
        $docblockExtractor = new DocblockExtractor;
        $this->extractor = new MethodInfoExtractor($typeConverter, $docblockExtractor);
    }

    public function test_extracts_public_method(): void
    {
        $code = <<<'PHP'
<?php
class TestClass
{
    public function testMethod(): void {}
}
PHP;

        $parser = (new ParserFactory)->createForNewestSupportedVersion();
        $ast = $parser->parse($code);
        $classNode = $ast[0];
        $methodNode = $classNode->stmts[0];

        $result = $this->extractor->extract($methodNode);

        $this->assertEquals('testMethod', $result['name']);
        $this->assertEquals('public', $result['visibility']);
        $this->assertEquals('void', $result['returnType']);
        $this->assertFalse($result['isStatic']);
        $this->assertFalse($result['isAbstract']);
        $this->assertFalse($result['isFinal']);
        $this->assertEmpty($result['parameters']);
    }

    public function test_extracts_private_method(): void
    {
        $code = <<<'PHP'
<?php
class TestClass
{
    private function privateMethod(): void {}
}
PHP;

        $parser = (new ParserFactory)->createForNewestSupportedVersion();
        $ast = $parser->parse($code);
        $classNode = $ast[0];
        $methodNode = $classNode->stmts[0];

        $result = $this->extractor->extract($methodNode);

        $this->assertEquals('privateMethod', $result['name']);
        $this->assertEquals('private', $result['visibility']);
    }

    public function test_extracts_protected_method(): void
    {
        $code = <<<'PHP'
<?php
class TestClass
{
    protected function protectedMethod(): void {}
}
PHP;

        $parser = (new ParserFactory)->createForNewestSupportedVersion();
        $ast = $parser->parse($code);
        $classNode = $ast[0];
        $methodNode = $classNode->stmts[0];

        $result = $this->extractor->extract($methodNode);

        $this->assertEquals('protectedMethod', $result['name']);
        $this->assertEquals('protected', $result['visibility']);
    }

    public function test_extracts_static_method(): void
    {
        $code = <<<'PHP'
<?php
class TestClass
{
    public static function staticMethod(): void {}
}
PHP;

        $parser = (new ParserFactory)->createForNewestSupportedVersion();
        $ast = $parser->parse($code);
        $classNode = $ast[0];
        $methodNode = $classNode->stmts[0];

        $result = $this->extractor->extract($methodNode);

        $this->assertEquals('staticMethod', $result['name']);
        $this->assertTrue($result['isStatic']);
    }

    public function test_extracts_abstract_method(): void
    {
        $code = <<<'PHP'
<?php
abstract class TestClass
{
    abstract public function abstractMethod(): void;
}
PHP;

        $parser = (new ParserFactory)->createForNewestSupportedVersion();
        $ast = $parser->parse($code);
        $classNode = $ast[0];
        $methodNode = $classNode->stmts[0];

        $result = $this->extractor->extract($methodNode);

        $this->assertEquals('abstractMethod', $result['name']);
        $this->assertTrue($result['isAbstract']);
    }

    public function test_extracts_final_method(): void
    {
        $code = <<<'PHP'
<?php
class TestClass
{
    final public function finalMethod(): void {}
}
PHP;

        $parser = (new ParserFactory)->createForNewestSupportedVersion();
        $ast = $parser->parse($code);
        $classNode = $ast[0];
        $methodNode = $classNode->stmts[0];

        $result = $this->extractor->extract($methodNode);

        $this->assertEquals('finalMethod', $result['name']);
        $this->assertTrue($result['isFinal']);
    }

    public function test_extracts_method_with_docblock(): void
    {
        $code = <<<'PHP'
<?php
class TestClass
{
    /**
     * This is a test method.
     * @return void
     */
    public function testMethod(): void {}
}
PHP;

        $parser = (new ParserFactory)->createForNewestSupportedVersion();
        $ast = $parser->parse($code);
        $classNode = $ast[0];
        $methodNode = $classNode->stmts[0];

        $result = $this->extractor->extract($methodNode);

        $this->assertNotNull($result['docblock']);
        $this->assertStringContainsString('This is a test method.', $result['docblock']);
    }

    public function test_extracts_method_with_parameters(): void
    {
        $code = <<<'PHP'
<?php
class TestClass
{
    public function testMethod(string $name, int $age): void {}
}
PHP;

        $parser = (new ParserFactory)->createForNewestSupportedVersion();
        $ast = $parser->parse($code);
        $classNode = $ast[0];
        $methodNode = $classNode->stmts[0];

        $result = $this->extractor->extract($methodNode);

        $this->assertCount(2, $result['parameters']);
        $this->assertEquals('name', $result['parameters'][0]['name']);
        $this->assertEquals('string', $result['parameters'][0]['type']);
        $this->assertEquals('age', $result['parameters'][1]['name']);
        $this->assertEquals('int', $result['parameters'][1]['type']);
    }

    public function test_extracts_parameter_with_default(): void
    {
        $code = <<<'PHP'
<?php
class TestClass
{
    public function testMethod(string $name = 'default'): void {}
}
PHP;

        $parser = (new ParserFactory)->createForNewestSupportedVersion();
        $ast = $parser->parse($code);
        $classNode = $ast[0];
        $methodNode = $classNode->stmts[0];

        $result = $this->extractor->extract($methodNode);

        $this->assertEquals('name', $result['parameters'][0]['name']);
        $this->assertEquals("'default'", $result['parameters'][0]['default']);
    }

    public function test_extracts_parameter_by_ref(): void
    {
        $code = <<<'PHP'
<?php
class TestClass
{
    public function testMethod(&$param): void {}
}
PHP;

        $parser = (new ParserFactory)->createForNewestSupportedVersion();
        $ast = $parser->parse($code);
        $classNode = $ast[0];
        $methodNode = $classNode->stmts[0];

        $result = $this->extractor->extract($methodNode);

        $this->assertEquals('param', $result['parameters'][0]['name']);
        $this->assertTrue($result['parameters'][0]['byRef']);
    }

    public function test_extracts_variadic_parameter(): void
    {
        $code = <<<'PHP'
<?php
class TestClass
{
    public function testMethod(...$args): void {}
}
PHP;

        $parser = (new ParserFactory)->createForNewestSupportedVersion();
        $ast = $parser->parse($code);
        $classNode = $ast[0];
        $methodNode = $classNode->stmts[0];

        $result = $this->extractor->extract($methodNode);

        $this->assertEquals('args', $result['parameters'][0]['name']);
        $this->assertTrue($result['parameters'][0]['variadic']);
    }

    public function test_extracts_method_with_complex_return_type(): void
    {
        $code = <<<'PHP'
<?php
class TestClass
{
    public function testMethod(): string|int|null {}
}
PHP;

        $parser = (new ParserFactory)->createForNewestSupportedVersion();
        $ast = $parser->parse($code);
        $classNode = $ast[0];
        $methodNode = $classNode->stmts[0];

        $result = $this->extractor->extract($methodNode);

        $this->assertEquals('string|int|null', $result['returnType']);
    }

    public function test_extracts_method_without_return_type(): void
    {
        $code = <<<'PHP'
<?php
class TestClass
{
    public function testMethod() {}
}
PHP;

        $parser = (new ParserFactory)->createForNewestSupportedVersion();
        $ast = $parser->parse($code);
        $classNode = $ast[0];
        $methodNode = $classNode->stmts[0];

        $result = $this->extractor->extract($methodNode);

        $this->assertNull($result['returnType']);
    }
}
