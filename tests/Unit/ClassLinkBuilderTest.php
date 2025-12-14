<?php

namespace Ajility\LaravelAutodoc\Tests\Unit;

use Ajility\LaravelAutodoc\Services\ClassLinkBuilder;
use Ajility\LaravelAutodoc\Services\PathResolver;
use PHPUnit\Framework\TestCase;

class ClassLinkBuilderTest extends TestCase
{
    private ClassLinkBuilder $builder;

    protected function setUp(): void
    {
        parent::setUp();
        $pathResolver = new PathResolver;
        $this->builder = new ClassLinkBuilder($pathResolver);
    }

    public function test_it_creates_link_for_documented_class(): void
    {
        $className = 'App\\Services\\PaymentService';
        $classMap = [
            'App\\Services\\PaymentService' => 'Services/PaymentService.md',
        ];
        $currentFilePath = 'Controllers/PaymentController.md';

        $result = $this->builder->createLink($className, $classMap, $currentFilePath);

        $this->assertStringContainsString('[`PaymentService`]', $result);
        $this->assertStringContainsString('(../Services/PaymentService.md)', $result);
    }

    public function test_it_creates_inline_code_for_undocumented_class(): void
    {
        $className = 'App\\External\\ThirdPartyService';
        $classMap = [
            'App\\Services\\PaymentService' => 'Services/PaymentService.md',
        ];
        $currentFilePath = 'Controllers/PaymentController.md';

        $result = $this->builder->createLink($className, $classMap, $currentFilePath);

        $this->assertEquals('`ThirdPartyService`', $result);
    }

    public function test_it_creates_link_with_relative_path_in_same_directory(): void
    {
        $className = 'App\\Services\\NotificationService';
        $classMap = [
            'App\\Services\\NotificationService' => 'Services/NotificationService.md',
        ];
        $currentFilePath = 'Services/PaymentService.md';

        $result = $this->builder->createLink($className, $classMap, $currentFilePath);

        $this->assertStringContainsString('[`NotificationService`]', $result);
        $this->assertStringContainsString('(NotificationService.md)', $result);
    }

    public function test_it_creates_link_from_subdirectory_to_parent(): void
    {
        $className = 'App\\Services\\BaseService';
        $classMap = [
            'App\\Services\\BaseService' => 'Services/BaseService.md',
        ];
        $currentFilePath = 'Services/Payment/StripeService.md';

        $result = $this->builder->createLink($className, $classMap, $currentFilePath);

        $this->assertStringContainsString('[`BaseService`]', $result);
        $this->assertStringContainsString('(../BaseService.md)', $result);
    }

    public function test_it_gets_short_class_name_from_namespaced_class(): void
    {
        $className = 'App\\Services\\Payment\\StripeService';

        $result = $this->builder->getShortClassName($className);

        $this->assertEquals('StripeService', $result);
    }

    public function test_it_gets_short_class_name_with_leading_backslash(): void
    {
        $className = '\\App\\Services\\PaymentService';

        $result = $this->builder->getShortClassName($className);

        $this->assertEquals('PaymentService', $result);
    }

    public function test_it_gets_short_class_name_for_global_class(): void
    {
        $className = 'DateTime';

        $result = $this->builder->getShortClassName($className);

        $this->assertEquals('DateTime', $result);
    }

    public function test_it_creates_link_for_interface(): void
    {
        $className = 'App\\Contracts\\PaymentProcessorInterface';
        $classMap = [
            'App\\Contracts\\PaymentProcessorInterface' => 'Contracts/PaymentProcessorInterface.md',
        ];
        $currentFilePath = 'Services/PaymentService.md';

        $result = $this->builder->createLink($className, $classMap, $currentFilePath);

        $this->assertStringContainsString('[`PaymentProcessorInterface`]', $result);
        $this->assertStringContainsString('(../Contracts/PaymentProcessorInterface.md)', $result);
    }

    public function test_it_creates_link_for_trait(): void
    {
        $className = 'App\\Traits\\Loggable';
        $classMap = [
            'App\\Traits\\Loggable' => 'Traits/Loggable.md',
        ];
        $currentFilePath = 'Services/PaymentService.md';

        $result = $this->builder->createLink($className, $classMap, $currentFilePath);

        $this->assertStringContainsString('[`Loggable`]', $result);
        $this->assertStringContainsString('(../Traits/Loggable.md)', $result);
    }

    public function test_it_handles_empty_class_map(): void
    {
        $className = 'App\\Services\\PaymentService';
        $classMap = [];
        $currentFilePath = 'Controllers/PaymentController.md';

        $result = $this->builder->createLink($className, $classMap, $currentFilePath);

        $this->assertEquals('`PaymentService`', $result);
    }

    public function test_it_handles_deeply_nested_paths(): void
    {
        $className = 'App\\Services\\Payment\\Processors\\Stripe\\StripeProcessor';
        $classMap = [
            'App\\Services\\Payment\\Processors\\Stripe\\StripeProcessor' => 'Services/Payment/Processors/Stripe/StripeProcessor.md',
        ];
        $currentFilePath = 'Controllers/PaymentController.md';

        $result = $this->builder->createLink($className, $classMap, $currentFilePath);

        $this->assertStringContainsString('[`StripeProcessor`]', $result);
        $this->assertStringContainsString(
            '(../Services/Payment/Processors/Stripe/StripeProcessor.md)',
            $result
        );
    }

    public function test_it_creates_multiple_links_for_different_classes(): void
    {
        $classMap = [
            'App\\Services\\PaymentService' => 'Services/PaymentService.md',
            'App\\Services\\NotificationService' => 'Services/NotificationService.md',
        ];
        $currentFilePath = 'Controllers/Controller.md';

        $result1 = $this->builder->createLink(
            'App\\Services\\PaymentService',
            $classMap,
            $currentFilePath
        );
        $result2 = $this->builder->createLink(
            'App\\Services\\NotificationService',
            $classMap,
            $currentFilePath
        );

        $this->assertStringContainsString('PaymentService', $result1);
        $this->assertStringContainsString('NotificationService', $result2);
    }

    public function test_it_handles_class_name_without_namespace(): void
    {
        $className = 'SimpleClass';
        $classMap = [
            'SimpleClass' => 'SimpleClass.md',
        ];
        $currentFilePath = 'OtherClass.md';

        $result = $this->builder->createLink($className, $classMap, $currentFilePath);

        $this->assertStringContainsString('[`SimpleClass`]', $result);
        $this->assertStringContainsString('(SimpleClass.md)', $result);
    }

    public function test_it_handles_external_framework_classes(): void
    {
        $className = 'Illuminate\\Support\\Collection';
        $classMap = [];
        $currentFilePath = 'Services/PaymentService.md';

        $result = $this->builder->createLink($className, $classMap, $currentFilePath);

        $this->assertEquals('`Collection`', $result);
    }

    public function test_it_handles_psr_interfaces(): void
    {
        $className = 'Psr\\Log\\LoggerInterface';
        $classMap = [];
        $currentFilePath = 'Services/LogService.md';

        $result = $this->builder->createLink($className, $classMap, $currentFilePath);

        $this->assertEquals('`LoggerInterface`', $result);
    }
}
