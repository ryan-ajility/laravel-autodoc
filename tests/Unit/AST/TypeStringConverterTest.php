<?php

namespace Ajility\LaravelAutodoc\Tests\Unit\AST;

use Ajility\LaravelAutodoc\Services\AST\TypeStringConverter;
use PhpParser\ParserFactory;
use PHPUnit\Framework\TestCase;

class TypeStringConverterTest extends TestCase
{
    private TypeStringConverter $converter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->converter = new TypeStringConverter;
    }

    public function test_converts_null_type_to_null(): void
    {
        $result = $this->converter->convert(null);
        $this->assertNull($result);
    }

    public function test_converts_simple_type(): void
    {
        $code = '<?php function test(string $param): void {}';
        $parser = (new ParserFactory)->createForNewestSupportedVersion();
        $ast = $parser->parse($code);
        $functionNode = $ast[0];
        $paramType = $functionNode->params[0]->type;

        $result = $this->converter->convert($paramType);
        $this->assertEquals('string', $result);
    }

    public function test_converts_return_type(): void
    {
        $code = '<?php function test(): int { return 1; }';
        $parser = (new ParserFactory)->createForNewestSupportedVersion();
        $ast = $parser->parse($code);
        $functionNode = $ast[0];
        $returnType = $functionNode->getReturnType();

        $result = $this->converter->convert($returnType);
        $this->assertEquals('int', $result);
    }

    public function test_converts_nullable_type(): void
    {
        $code = '<?php function test(?string $param): void {}';
        $parser = (new ParserFactory)->createForNewestSupportedVersion();
        $ast = $parser->parse($code);
        $functionNode = $ast[0];
        $paramType = $functionNode->params[0]->type;

        $result = $this->converter->convert($paramType);
        $this->assertEquals('?string', $result);
    }

    public function test_converts_union_type(): void
    {
        $code = '<?php function test(string|int $param): void {}';
        $parser = (new ParserFactory)->createForNewestSupportedVersion();
        $ast = $parser->parse($code);
        $functionNode = $ast[0];
        $paramType = $functionNode->params[0]->type;

        $result = $this->converter->convert($paramType);
        $this->assertEquals('string|int', $result);
    }

    public function test_converts_intersection_type(): void
    {
        $code = '<?php
        interface A {}
        interface B {}
        function test(A&B $param): void {}';
        $parser = (new ParserFactory)->createForNewestSupportedVersion();
        $ast = $parser->parse($code);
        $functionNode = $ast[2];
        $paramType = $functionNode->params[0]->type;

        $result = $this->converter->convert($paramType);
        $this->assertEquals('A&B', $result);
    }

    public function test_converts_array_type(): void
    {
        $code = '<?php function test(array $param): void {}';
        $parser = (new ParserFactory)->createForNewestSupportedVersion();
        $ast = $parser->parse($code);
        $functionNode = $ast[0];
        $paramType = $functionNode->params[0]->type;

        $result = $this->converter->convert($paramType);
        $this->assertEquals('array', $result);
    }

    public function test_converts_mixed_type(): void
    {
        $code = '<?php function test(mixed $param): void {}';
        $parser = (new ParserFactory)->createForNewestSupportedVersion();
        $ast = $parser->parse($code);
        $functionNode = $ast[0];
        $paramType = $functionNode->params[0]->type;

        $result = $this->converter->convert($paramType);
        $this->assertEquals('mixed', $result);
    }

    public function test_node_to_string_handles_string_scalar(): void
    {
        $code = "<?php \$var = 'test';";
        $parser = (new ParserFactory)->createForNewestSupportedVersion();
        $ast = $parser->parse($code);
        $exprNode = $ast[0];
        $stringNode = $exprNode->expr->expr;

        $result = $this->converter->nodeToString($stringNode);
        $this->assertEquals("'test'", $result);
    }

    public function test_node_to_string_handles_int_scalar(): void
    {
        $code = '<?php $var = 42;';
        $parser = (new ParserFactory)->createForNewestSupportedVersion();
        $ast = $parser->parse($code);
        $exprNode = $ast[0];
        $intNode = $exprNode->expr->expr;

        $result = $this->converter->nodeToString($intNode);
        $this->assertEquals('42', $result);
    }

    public function test_node_to_string_handles_float_scalar(): void
    {
        $code = '<?php $var = 3.14;';
        $parser = (new ParserFactory)->createForNewestSupportedVersion();
        $ast = $parser->parse($code);
        $exprNode = $ast[0];
        $floatNode = $exprNode->expr->expr;

        $result = $this->converter->nodeToString($floatNode);
        $this->assertEquals('3.14', $result);
    }

    public function test_node_to_string_handles_const_fetch(): void
    {
        $code = '<?php $var = true;';
        $parser = (new ParserFactory)->createForNewestSupportedVersion();
        $ast = $parser->parse($code);
        $exprNode = $ast[0];
        $constNode = $exprNode->expr->expr;

        $result = $this->converter->nodeToString($constNode);
        $this->assertEquals('true', $result);
    }

    public function test_node_to_string_handles_array(): void
    {
        $code = '<?php $var = [];';
        $parser = (new ParserFactory)->createForNewestSupportedVersion();
        $ast = $parser->parse($code);
        $exprNode = $ast[0];
        $arrayNode = $exprNode->expr->expr;

        $result = $this->converter->nodeToString($arrayNode);
        $this->assertEquals('[]', $result);
    }

    public function test_node_to_string_handles_unknown_node(): void
    {
        $code = '<?php $var = new stdClass();';
        $parser = (new ParserFactory)->createForNewestSupportedVersion();
        $ast = $parser->parse($code);
        $exprNode = $ast[0];
        $newNode = $exprNode->expr->expr;

        $result = $this->converter->nodeToString($newNode);
        $this->assertEquals('unknown', $result);
    }
}
