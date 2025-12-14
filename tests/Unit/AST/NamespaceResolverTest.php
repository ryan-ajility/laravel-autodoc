<?php

namespace Ajility\LaravelAutodoc\Tests\Unit\AST;

use Ajility\LaravelAutodoc\Services\AST\NamespaceResolver;
use PHPUnit\Framework\TestCase;

class NamespaceResolverTest extends TestCase
{
    private NamespaceResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resolver = new NamespaceResolver;
    }

    public function test_resolves_fully_qualified_class_name(): void
    {
        $result = $this->resolver->resolve('\\App\\Models\\User');
        $this->assertEquals('App\\Models\\User', $result);
    }

    public function test_resolves_class_name_with_backslash(): void
    {
        $result = $this->resolver->resolve('App\\Models\\User');
        $this->assertEquals('App\\Models\\User', $result);
    }

    public function test_resolves_simple_class_name_with_namespace(): void
    {
        $this->resolver->setNamespace('App\\Services');
        $result = $this->resolver->resolve('UserService');
        $this->assertEquals('App\\Services\\UserService', $result);
    }

    public function test_resolves_simple_class_name_without_namespace(): void
    {
        $result = $this->resolver->resolve('User');
        $this->assertEquals('User', $result);
    }

    public function test_resolves_class_name_from_use_statement(): void
    {
        $this->resolver->addUseStatement('User', 'App\\Models\\User');
        $result = $this->resolver->resolve('User');
        $this->assertEquals('App\\Models\\User', $result);
    }

    public function test_resolves_aliased_use_statement(): void
    {
        $this->resolver->addUseStatement('UserModel', 'App\\Models\\User');
        $result = $this->resolver->resolve('UserModel');
        $this->assertEquals('App\\Models\\User', $result);
    }

    public function test_use_statement_takes_precedence_over_namespace(): void
    {
        $this->resolver->setNamespace('App\\Services');
        $this->resolver->addUseStatement('User', 'App\\Models\\User');
        $result = $this->resolver->resolve('User');
        $this->assertEquals('App\\Models\\User', $result);
    }

    public function test_resolves_multiple_use_statements(): void
    {
        $this->resolver->addUseStatement('User', 'App\\Models\\User');
        $this->resolver->addUseStatement('Post', 'App\\Models\\Post');

        $userResult = $this->resolver->resolve('User');
        $postResult = $this->resolver->resolve('Post');

        $this->assertEquals('App\\Models\\User', $userResult);
        $this->assertEquals('App\\Models\\Post', $postResult);
    }

    public function test_set_namespace_updates_current_namespace(): void
    {
        $this->resolver->setNamespace('App\\Controllers');
        $result = $this->resolver->resolve('HomeController');
        $this->assertEquals('App\\Controllers\\HomeController', $result);

        $this->resolver->setNamespace('App\\Services');
        $result = $this->resolver->resolve('UserService');
        $this->assertEquals('App\\Services\\UserService', $result);
    }

    public function test_set_namespace_to_null_resets_namespace(): void
    {
        $this->resolver->setNamespace('App\\Services');
        $this->resolver->setNamespace(null);
        $result = $this->resolver->resolve('User');
        $this->assertEquals('User', $result);
    }

    public function test_add_use_statement_accumulates_statements(): void
    {
        $this->resolver->addUseStatement('User', 'App\\Models\\User');
        $this->resolver->addUseStatement('Post', 'App\\Models\\Post');
        $this->resolver->addUseStatement('Comment', 'App\\Models\\Comment');

        $this->assertEquals('App\\Models\\User', $this->resolver->resolve('User'));
        $this->assertEquals('App\\Models\\Post', $this->resolver->resolve('Post'));
        $this->assertEquals('App\\Models\\Comment', $this->resolver->resolve('Comment'));
    }

    public function test_reset_clears_use_statements_and_namespace(): void
    {
        $this->resolver->setNamespace('App\\Services');
        $this->resolver->addUseStatement('User', 'App\\Models\\User');

        $this->resolver->reset();

        // After reset, should not resolve from use statement or namespace
        $result = $this->resolver->resolve('User');
        $this->assertEquals('User', $result);
    }
}
