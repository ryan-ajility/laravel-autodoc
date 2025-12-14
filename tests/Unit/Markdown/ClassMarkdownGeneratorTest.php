<?php

namespace Ajility\LaravelAutodoc\Tests\Unit\Markdown;

use Ajility\LaravelAutodoc\Services\ClassLinkBuilder;
use Ajility\LaravelAutodoc\Services\DocblockProcessor;
use Ajility\LaravelAutodoc\Services\Markdown\ClassMarkdownGenerator;
use Ajility\LaravelAutodoc\Services\PathResolver;
use PHPUnit\Framework\TestCase;

class ClassMarkdownGeneratorTest extends TestCase
{
    private ClassMarkdownGenerator $generator;

    protected function setUp(): void
    {
        parent::setUp();
        $pathResolver = new PathResolver;
        $docblockProcessor = new DocblockProcessor;
        $linkBuilder = new ClassLinkBuilder($pathResolver);

        $this->generator = new ClassMarkdownGenerator(
            $docblockProcessor,
            $linkBuilder,
            $pathResolver
        );
    }

    public function test_it_generates_basic_class_markdown(): void
    {
        $classInfo = [
            'name' => 'PaymentService',
            'namespace' => 'App\\Services',
            'type' => 'class',
            'docblock' => '/** * Handles payment processing */',
            'extends' => null,
            'implements' => [],
            'traits' => [],
            'methods' => [],
            'properties' => [],
        ];

        $result = $this->generator->generate(
            $classInfo,
            'Services/PaymentService.md',
            '/Users/test/project/src/Services/PaymentService.php',
            '/Users/test/project/docs',
            []
        );

        $this->assertStringContainsString('# PaymentService', $result);
        $this->assertStringContainsString('## Description', $result);
        $this->assertStringContainsString('Handles payment processing', $result);
    }

    public function test_it_includes_source_file_link(): void
    {
        $classInfo = [
            'name' => 'PaymentService',
            'namespace' => 'App\\Services',
            'type' => 'class',
            'docblock' => null,
            'extends' => null,
            'implements' => [],
            'traits' => [],
            'methods' => [],
            'properties' => [],
        ];

        $result = $this->generator->generate(
            $classInfo,
            'Services/PaymentService.md',
            '/Users/test/project/src/Services/PaymentService.php',
            '/Users/test/project/docs',
            []
        );

        $this->assertStringContainsString('**Source:**', $result);
        $this->assertStringContainsString('[Services/PaymentService.md]', $result);
    }

    public function test_it_includes_extends_information(): void
    {
        $classInfo = [
            'name' => 'StripeService',
            'namespace' => 'App\\Services',
            'type' => 'class',
            'docblock' => null,
            'extends' => 'App\\Services\\BasePaymentService',
            'implements' => [],
            'traits' => [],
            'methods' => [],
            'properties' => [],
        ];

        $classMap = [
            'App\\Services\\BasePaymentService' => 'Services/BasePaymentService.md',
        ];

        $result = $this->generator->generate(
            $classInfo,
            'Services/StripeService.md',
            '/Users/test/project/src/Services/StripeService.php',
            '/Users/test/project/docs',
            $classMap
        );

        $this->assertStringContainsString('**Extends:**', $result);
        $this->assertStringContainsString('[`BasePaymentService`]', $result);
    }

    public function test_it_includes_implements_information(): void
    {
        $classInfo = [
            'name' => 'PaymentService',
            'namespace' => 'App\\Services',
            'type' => 'class',
            'docblock' => null,
            'extends' => null,
            'implements' => [
                'App\\Contracts\\PaymentProcessorInterface',
                'App\\Contracts\\RefundableInterface',
            ],
            'traits' => [],
            'methods' => [],
            'properties' => [],
        ];

        $classMap = [
            'App\\Contracts\\PaymentProcessorInterface' => 'Contracts/PaymentProcessorInterface.md',
            'App\\Contracts\\RefundableInterface' => 'Contracts/RefundableInterface.md',
        ];

        $result = $this->generator->generate(
            $classInfo,
            'Services/PaymentService.md',
            '/Users/test/project/src/Services/PaymentService.php',
            '/Users/test/project/docs',
            $classMap
        );

        $this->assertStringContainsString('**Implements:**', $result);
        $this->assertStringContainsString('[`PaymentProcessorInterface`]', $result);
        $this->assertStringContainsString('[`RefundableInterface`]', $result);
    }

    public function test_it_includes_trait_information(): void
    {
        $classInfo = [
            'name' => 'PaymentService',
            'namespace' => 'App\\Services',
            'type' => 'class',
            'docblock' => null,
            'extends' => null,
            'implements' => [],
            'traits' => ['App\\Traits\\Loggable', 'App\\Traits\\Cacheable'],
            'methods' => [],
            'properties' => [],
        ];

        $classMap = [
            'App\\Traits\\Loggable' => 'Traits/Loggable.md',
            'App\\Traits\\Cacheable' => 'Traits/Cacheable.md',
        ];

        $result = $this->generator->generate(
            $classInfo,
            'Services/PaymentService.md',
            '/Users/test/project/src/Services/PaymentService.php',
            '/Users/test/project/docs',
            $classMap
        );

        $this->assertStringContainsString('**Uses Traits:**', $result);
        $this->assertStringContainsString('[`Loggable`]', $result);
        $this->assertStringContainsString('[`Cacheable`]', $result);
    }

    public function test_it_generates_properties_table(): void
    {
        $classInfo = [
            'name' => 'PaymentService',
            'namespace' => 'App\\Services',
            'type' => 'class',
            'docblock' => null,
            'extends' => null,
            'implements' => [],
            'traits' => [],
            'methods' => [],
            'properties' => [
                [
                    'name' => 'apiKey',
                    'visibility' => 'private',
                    'type' => 'string',
                    'default' => null,
                    'docblock' => '/** * API key for payment processor */',
                ],
            ],
        ];

        $result = $this->generator->generate(
            $classInfo,
            'Services/PaymentService.md',
            '/Users/test/project/src/Services/PaymentService.php',
            '/Users/test/project/docs',
            []
        );

        $this->assertStringContainsString('## Properties', $result);
        $this->assertStringContainsString('| `$apiKey` |', $result);
        $this->assertStringContainsString('| `private` |', $result);
        $this->assertStringContainsString('| `string` |', $result);
    }

    public function test_it_generates_method_documentation(): void
    {
        $classInfo = [
            'name' => 'PaymentService',
            'namespace' => 'App\\Services',
            'type' => 'class',
            'docblock' => null,
            'extends' => null,
            'implements' => [],
            'traits' => [],
            'methods' => [
                [
                    'name' => 'processPayment',
                    'visibility' => 'public',
                    'isStatic' => false,
                    'isAbstract' => false,
                    'isFinal' => false,
                    'docblock' => '/** * Process a payment * @param float $amount The payment amount * @return bool Success status */',
                    'parameters' => [
                        [
                            'name' => 'amount',
                            'type' => 'float',
                            'default' => null,
                            'byRef' => false,
                            'variadic' => false,
                        ],
                    ],
                    'returnType' => 'bool',
                ],
            ],
            'properties' => [],
        ];

        $result = $this->generator->generate(
            $classInfo,
            'Services/PaymentService.md',
            '/Users/test/project/src/Services/PaymentService.php',
            '/Users/test/project/docs',
            []
        );

        $this->assertStringContainsString('## Methods', $result);
        $this->assertStringContainsString('### processPayment()', $result);
        $this->assertStringContainsString('**Parameters:**', $result);
        $this->assertStringContainsString('| `$amount` | `float` |', $result);
        $this->assertStringContainsString('**Returns:**', $result);
        $this->assertStringContainsString('`bool`', $result);
    }

    public function test_it_generates_static_method_documentation(): void
    {
        $classInfo = [
            'name' => 'PaymentService',
            'namespace' => 'App\\Services',
            'type' => 'class',
            'docblock' => null,
            'extends' => null,
            'implements' => [],
            'traits' => [],
            'methods' => [
                [
                    'name' => 'create',
                    'visibility' => 'public',
                    'isStatic' => true,
                    'isAbstract' => false,
                    'isFinal' => false,
                    'docblock' => '/** * Create a new instance */',
                    'parameters' => [],
                    'returnType' => 'self',
                ],
            ],
            'properties' => [],
        ];

        $result = $this->generator->generate(
            $classInfo,
            'Services/PaymentService.md',
            '/Users/test/project/src/Services/PaymentService.php',
            '/Users/test/project/docs',
            []
        );

        $this->assertStringContainsString('### static create()', $result);
        $this->assertStringContainsString('public static', $result);
    }

    public function test_it_generates_abstract_method_documentation(): void
    {
        $classInfo = [
            'name' => 'BasePaymentService',
            'namespace' => 'App\\Services',
            'type' => 'class',
            'docblock' => null,
            'extends' => null,
            'implements' => [],
            'traits' => [],
            'methods' => [
                [
                    'name' => 'process',
                    'visibility' => 'public',
                    'isStatic' => false,
                    'isAbstract' => true,
                    'isFinal' => false,
                    'docblock' => null,
                    'parameters' => [],
                    'returnType' => 'bool',
                ],
            ],
            'properties' => [],
        ];

        $result = $this->generator->generate(
            $classInfo,
            'Services/BasePaymentService.md',
            '/Users/test/project/src/Services/BasePaymentService.php',
            '/Users/test/project/docs',
            []
        );

        $this->assertStringContainsString('### abstract process()', $result);
        $this->assertStringContainsString('public abstract', $result);
    }

    public function test_it_generates_final_method_documentation(): void
    {
        $classInfo = [
            'name' => 'PaymentService',
            'namespace' => 'App\\Services',
            'type' => 'class',
            'docblock' => null,
            'extends' => null,
            'implements' => [],
            'traits' => [],
            'methods' => [
                [
                    'name' => 'validatePayment',
                    'visibility' => 'protected',
                    'isStatic' => false,
                    'isAbstract' => false,
                    'isFinal' => true,
                    'docblock' => null,
                    'parameters' => [],
                    'returnType' => 'bool',
                ],
            ],
            'properties' => [],
        ];

        $result = $this->generator->generate(
            $classInfo,
            'Services/PaymentService.md',
            '/Users/test/project/src/Services/PaymentService.php',
            '/Users/test/project/docs',
            []
        );

        $this->assertStringContainsString('### final validatePayment()', $result);
        $this->assertStringContainsString('protected final', $result);
    }

    public function test_it_handles_class_without_docblock(): void
    {
        $classInfo = [
            'name' => 'SimpleClass',
            'namespace' => 'App',
            'type' => 'class',
            'docblock' => null,
            'extends' => null,
            'implements' => [],
            'traits' => [],
            'methods' => [],
            'properties' => [],
        ];

        $result = $this->generator->generate(
            $classInfo,
            'SimpleClass.md',
            '/Users/test/project/src/SimpleClass.php',
            '/Users/test/project/docs',
            []
        );

        $this->assertStringContainsString('# SimpleClass', $result);
        $this->assertStringNotContainsString('## Description', $result);
    }

    public function test_it_handles_class_without_methods(): void
    {
        $classInfo = [
            'name' => 'DataObject',
            'namespace' => 'App',
            'type' => 'class',
            'docblock' => null,
            'extends' => null,
            'implements' => [],
            'traits' => [],
            'methods' => [],
            'properties' => [],
        ];

        $result = $this->generator->generate(
            $classInfo,
            'DataObject.md',
            '/Users/test/project/src/DataObject.php',
            '/Users/test/project/docs',
            []
        );

        $this->assertStringContainsString('# DataObject', $result);
        $this->assertStringNotContainsString('## Methods', $result);
    }

    public function test_it_handles_class_without_properties(): void
    {
        $classInfo = [
            'name' => 'Helper',
            'namespace' => 'App',
            'type' => 'class',
            'docblock' => null,
            'extends' => null,
            'implements' => [],
            'traits' => [],
            'methods' => [
                [
                    'name' => 'help',
                    'visibility' => 'public',
                    'isStatic' => true,
                    'isAbstract' => false,
                    'isFinal' => false,
                    'docblock' => null,
                    'parameters' => [],
                    'returnType' => 'void',
                ],
            ],
            'properties' => [],
        ];

        $result = $this->generator->generate(
            $classInfo,
            'Helper.md',
            '/Users/test/project/src/Helper.php',
            '/Users/test/project/docs',
            []
        );

        $this->assertStringContainsString('# Helper', $result);
        $this->assertStringContainsString('## Methods', $result);
        $this->assertStringNotContainsString('## Properties', $result);
    }

    public function test_it_generates_interface_documentation(): void
    {
        $classInfo = [
            'name' => 'PaymentProcessorInterface',
            'namespace' => 'App\\Contracts',
            'type' => 'interface',
            'docblock' => '/** * Payment processor contract */',
            'extends' => null,
            'implements' => [],
            'traits' => [],
            'methods' => [
                [
                    'name' => 'process',
                    'visibility' => 'public',
                    'isStatic' => false,
                    'isAbstract' => false,
                    'isFinal' => false,
                    'docblock' => null,
                    'parameters' => [],
                    'returnType' => 'bool',
                ],
            ],
            'properties' => [],
        ];

        $result = $this->generator->generate(
            $classInfo,
            'Contracts/PaymentProcessorInterface.md',
            '/Users/test/project/src/Contracts/PaymentProcessorInterface.php',
            '/Users/test/project/docs',
            []
        );

        $this->assertStringContainsString('# PaymentProcessorInterface', $result);
        $this->assertStringContainsString('Payment processor contract', $result);
    }

    public function test_it_generates_trait_documentation(): void
    {
        $classInfo = [
            'name' => 'Loggable',
            'namespace' => 'App\\Traits',
            'type' => 'trait',
            'docblock' => '/** * Adds logging capabilities */',
            'extends' => null,
            'implements' => [],
            'traits' => [],
            'methods' => [
                [
                    'name' => 'log',
                    'visibility' => 'protected',
                    'isStatic' => false,
                    'isAbstract' => false,
                    'isFinal' => false,
                    'docblock' => null,
                    'parameters' => [
                        [
                            'name' => 'message',
                            'type' => 'string',
                            'default' => null,
                            'byRef' => false,
                            'variadic' => false,
                        ],
                    ],
                    'returnType' => 'void',
                ],
            ],
            'properties' => [],
        ];

        $result = $this->generator->generate(
            $classInfo,
            'Traits/Loggable.md',
            '/Users/test/project/src/Traits/Loggable.php',
            '/Users/test/project/docs',
            []
        );

        $this->assertStringContainsString('# Loggable', $result);
        $this->assertStringContainsString('Adds logging capabilities', $result);
    }
}
