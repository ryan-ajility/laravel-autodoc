<?php

namespace Ajility\LaravelAutodoc\Tests\Unit;

use Ajility\LaravelAutodoc\Services\MarkdownGenerator;
use PHPUnit\Framework\TestCase;

class MarkdownGeneratorTest extends TestCase
{
    private MarkdownGenerator $generator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->generator = new MarkdownGenerator;
    }

    public function test_it_generates_basic_class_markdown(): void
    {
        $classInfo = [
            'name' => 'TestClass',
            'type' => 'class',
            'namespace' => 'App\Test',
            'docblock' => '/** * A test class */',
            'extends' => null,
            'implements' => [],
            'methods' => [],
            'properties' => [],
        ];

        $markdown = $this->generator->generateClassMarkdown($classInfo, 'src/TestClass.php');

        $this->assertStringContainsString('# TestClass', $markdown);
        $this->assertStringContainsString('## Description', $markdown);
        $this->assertStringContainsString('A test class', $markdown);
    }

    public function test_it_includes_extends_information(): void
    {
        $classInfo = [
            'name' => 'ChildClass',
            'type' => 'class',
            'namespace' => 'App\Test',
            'docblock' => null,
            'extends' => 'ParentClass',
            'implements' => [],
            'methods' => [],
            'properties' => [],
        ];

        $markdown = $this->generator->generateClassMarkdown($classInfo, 'src/ChildClass.php');

        $this->assertStringContainsString('# ChildClass', $markdown);
        $this->assertStringContainsString('**Extends:** `ParentClass`', $markdown);
    }

    public function test_it_includes_implements_information(): void
    {
        $classInfo = [
            'name' => 'TestClass',
            'type' => 'class',
            'namespace' => 'App\Test',
            'docblock' => null,
            'extends' => null,
            'implements' => ['InterfaceA', 'InterfaceB'],
            'methods' => [],
            'properties' => [],
        ];

        $markdown = $this->generator->generateClassMarkdown($classInfo, 'src/TestClass.php');

        $this->assertStringContainsString('# TestClass', $markdown);
        $this->assertStringContainsString('**Implements:** `InterfaceA`, `InterfaceB`', $markdown);
    }

    public function test_it_includes_both_extends_and_implements_information(): void
    {
        $classInfo = [
            'name' => 'ComplexClass',
            'type' => 'class',
            'namespace' => 'App\Test',
            'docblock' => null,
            'extends' => 'BaseClass',
            'implements' => ['InterfaceA', 'InterfaceB'],
            'methods' => [],
            'properties' => [],
        ];

        $markdown = $this->generator->generateClassMarkdown($classInfo, 'src/ComplexClass.php');

        $this->assertStringContainsString('# ComplexClass', $markdown);
        $this->assertStringContainsString('**Extends:** `BaseClass`', $markdown);
        $this->assertStringContainsString('**Implements:** `InterfaceA`, `InterfaceB`', $markdown);
    }

    public function test_it_generates_method_documentation(): void
    {
        $classInfo = [
            'name' => 'TestClass',
            'type' => 'class',
            'namespace' => 'App\Test',
            'docblock' => null,
            'extends' => null,
            'implements' => [],
            'methods' => [
                [
                    'name' => 'testMethod',
                    'visibility' => 'public',
                    'isStatic' => false,
                    'isAbstract' => false,
                    'isFinal' => false,
                    'docblock' => '/** * Test method */',
                    'parameters' => [],
                    'returnType' => 'void',
                ],
            ],
            'properties' => [],
        ];

        $markdown = $this->generator->generateClassMarkdown($classInfo, 'src/TestClass.php');

        $this->assertStringContainsString('## Methods', $markdown);
        $this->assertStringContainsString('### testMethod()', $markdown);
        $this->assertStringContainsString('**Returns:**', $markdown);
        $this->assertStringContainsString('`void`', $markdown);
    }

    public function test_it_generates_method_with_parameters(): void
    {
        $classInfo = [
            'name' => 'TestClass',
            'type' => 'class',
            'namespace' => 'App\Test',
            'docblock' => null,
            'extends' => null,
            'implements' => [],
            'methods' => [
                [
                    'name' => 'processData',
                    'visibility' => 'public',
                    'isStatic' => false,
                    'isAbstract' => false,
                    'isFinal' => false,
                    'docblock' => '/** * Process data * @param string $name The name * @param int $age The age */',
                    'parameters' => [
                        [
                            'name' => 'name',
                            'type' => 'string',
                            'default' => null,
                            'byRef' => false,
                            'variadic' => false,
                        ],
                        [
                            'name' => 'age',
                            'type' => 'int',
                            'default' => '18',
                            'byRef' => false,
                            'variadic' => false,
                        ],
                    ],
                    'returnType' => 'bool',
                ],
            ],
            'properties' => [],
        ];

        $markdown = $this->generator->generateClassMarkdown($classInfo, 'src/TestClass.php');

        $this->assertStringContainsString('**Parameters:**', $markdown);
        $this->assertStringContainsString('| `$name` | `string` |', $markdown);
        $this->assertStringContainsString('| `$age` | `int` | `18` |', $markdown);
    }

    public function test_it_generates_static_method_documentation(): void
    {
        $classInfo = [
            'name' => 'TestClass',
            'type' => 'class',
            'namespace' => 'App\Test',
            'docblock' => null,
            'extends' => null,
            'implements' => [],
            'methods' => [
                [
                    'name' => 'staticMethod',
                    'visibility' => 'public',
                    'isStatic' => true,
                    'isAbstract' => false,
                    'isFinal' => false,
                    'docblock' => null,
                    'parameters' => [],
                    'returnType' => 'string',
                ],
            ],
            'properties' => [],
        ];

        $markdown = $this->generator->generateClassMarkdown($classInfo, 'src/TestClass.php');

        $this->assertStringContainsString('### static staticMethod()', $markdown);
        $this->assertStringContainsString('public static', $markdown);
    }

    public function test_it_generates_property_documentation(): void
    {
        $classInfo = [
            'name' => 'TestClass',
            'type' => 'class',
            'namespace' => 'App\Test',
            'docblock' => null,
            'extends' => null,
            'implements' => [],
            'methods' => [],
            'properties' => [
                [
                    [
                        'name' => 'testProperty',
                        'visibility' => 'public',
                        'isStatic' => false,
                        'type' => 'string',
                        'default' => "'test'",
                        'docblock' => '/** * A test property */',
                    ],
                ],
            ],
        ];

        $markdown = $this->generator->generateClassMarkdown($classInfo, 'src/TestClass.php');

        $this->assertStringContainsString('## Properties', $markdown);
        $this->assertStringContainsString('| `$testProperty` |', $markdown);
        $this->assertStringContainsString('| `public` |', $markdown);
        $this->assertStringContainsString('| `string` |', $markdown);
        $this->assertStringContainsString("| `'test'` |", $markdown);
    }

    public function test_it_generates_index_markdown(): void
    {
        $structure = [
            'src/ClassA.php' => [
                [
                    'name' => 'ClassA',
                    'namespace' => 'App\Test',
                    'type' => 'class',
                ],
            ],
            'src/ClassB.php' => [
                [
                    'name' => 'ClassB',
                    'namespace' => 'App\Test',
                    'type' => 'class',
                ],
            ],
        ];

        $markdown = $this->generator->generateIndexMarkdown('Test Documentation');

        $this->assertStringContainsString('# Test Documentation', $markdown);
        $this->assertStringContainsString('## Overview', $markdown);
        $this->assertStringContainsString('Generated on:', $markdown);
    }

    public function test_it_handles_class_without_namespace(): void
    {
        $structure = [
            'src/GlobalClass.php' => [
                [
                    'name' => 'GlobalClass',
                    'namespace' => null,
                    'type' => 'class',
                ],
            ],
        ];

        $markdown = $this->generator->generateIndexMarkdown('Test Documentation');

        $this->assertStringContainsString('## Overview', $markdown);
    }

    public function test_it_generates_interface_markdown(): void
    {
        $classInfo = [
            'name' => 'TestInterface',
            'type' => 'interface',
            'namespace' => 'App\Test',
            'docblock' => '/** * Test interface */',
            'extends' => null,
            'implements' => [],
            'methods' => [],
            'properties' => [],
        ];

        $markdown = $this->generator->generateClassMarkdown($classInfo, 'src/TestInterface.php');

        $this->assertStringContainsString('# TestInterface', $markdown);
    }

    public function test_it_generates_trait_markdown(): void
    {
        $classInfo = [
            'name' => 'TestTrait',
            'type' => 'trait',
            'namespace' => 'App\Test',
            'docblock' => '/** * Test trait */',
            'extends' => null,
            'implements' => [],
            'methods' => [],
            'properties' => [],
        ];

        $markdown = $this->generator->generateClassMarkdown($classInfo, 'src/TestTrait.php');

        $this->assertStringContainsString('# TestTrait', $markdown);
    }
}
