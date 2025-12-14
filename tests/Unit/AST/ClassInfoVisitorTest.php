<?php

namespace Ajility\LaravelAutodoc\Tests\Unit\AST;

use Ajility\LaravelAutodoc\Services\AST\ClassInfoVisitor;
use Ajility\LaravelAutodoc\Services\AST\DocblockExtractor;
use Ajility\LaravelAutodoc\Services\AST\MethodInfoExtractor;
use Ajility\LaravelAutodoc\Services\AST\NamespaceResolver;
use Ajility\LaravelAutodoc\Services\AST\PropertyInfoExtractor;
use Ajility\LaravelAutodoc\Services\AST\TypeStringConverter;
use PhpParser\NodeTraverser;
use PhpParser\ParserFactory;
use PHPUnit\Framework\TestCase;

class ClassInfoVisitorTest extends TestCase
{
    private ClassInfoVisitor $visitor;

    protected function setUp(): void
    {
        parent::setUp();

        $typeConverter = new TypeStringConverter;
        $docblockExtractor = new DocblockExtractor;
        $methodExtractor = new MethodInfoExtractor($typeConverter, $docblockExtractor);
        $propertyExtractor = new PropertyInfoExtractor($typeConverter, $docblockExtractor);
        $namespaceResolver = new NamespaceResolver;

        $this->visitor = new ClassInfoVisitor(
            $methodExtractor,
            $propertyExtractor,
            $namespaceResolver,
            false
        );
    }

    public function test_extracts_basic_class_info(): void
    {
        $code = <<<'PHP'
<?php
namespace App\Models;

class User
{
}
PHP;

        $parser = (new ParserFactory)->createForNewestSupportedVersion();
        $ast = $parser->parse($code);

        $traverser = new NodeTraverser;
        $traverser->addVisitor($this->visitor);
        $traverser->traverse($ast);

        $classes = $this->visitor->getClasses();

        $this->assertCount(1, $classes);
        $this->assertEquals('User', $classes[0]['name']);
        $this->assertEquals('class', $classes[0]['type']);
        $this->assertEquals('App\\Models', $classes[0]['namespace']);
    }

    public function test_extracts_interface(): void
    {
        $code = <<<'PHP'
<?php
namespace App\Contracts;

interface UserInterface
{
}
PHP;

        $parser = (new ParserFactory)->createForNewestSupportedVersion();
        $ast = $parser->parse($code);

        $traverser = new NodeTraverser;
        $traverser->addVisitor($this->visitor);
        $traverser->traverse($ast);

        $classes = $this->visitor->getClasses();

        $this->assertCount(1, $classes);
        $this->assertEquals('UserInterface', $classes[0]['name']);
        $this->assertEquals('interface', $classes[0]['type']);
    }

    public function test_extracts_trait(): void
    {
        $code = <<<'PHP'
<?php
namespace App\Traits;

trait Loggable
{
}
PHP;

        $parser = (new ParserFactory)->createForNewestSupportedVersion();
        $ast = $parser->parse($code);

        $traverser = new NodeTraverser;
        $traverser->addVisitor($this->visitor);
        $traverser->traverse($ast);

        $classes = $this->visitor->getClasses();

        $this->assertCount(1, $classes);
        $this->assertEquals('Loggable', $classes[0]['name']);
        $this->assertEquals('trait', $classes[0]['type']);
    }

    public function test_extracts_class_with_docblock(): void
    {
        $code = <<<'PHP'
<?php
/**
 * User model class.
 */
class User
{
}
PHP;

        $parser = (new ParserFactory)->createForNewestSupportedVersion();
        $ast = $parser->parse($code);

        $traverser = new NodeTraverser;
        $traverser->addVisitor($this->visitor);
        $traverser->traverse($ast);

        $classes = $this->visitor->getClasses();

        $this->assertNotNull($classes[0]['docblock']);
        $this->assertStringContainsString('User model class.', $classes[0]['docblock']);
    }

    public function test_extracts_class_with_extends(): void
    {
        $code = <<<'PHP'
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
}
PHP;

        $parser = (new ParserFactory)->createForNewestSupportedVersion();
        $ast = $parser->parse($code);

        $traverser = new NodeTraverser;
        $traverser->addVisitor($this->visitor);
        $traverser->traverse($ast);

        $classes = $this->visitor->getClasses();

        $this->assertEquals('Illuminate\\Database\\Eloquent\\Model', $classes[0]['extends']);
    }

    public function test_extracts_class_with_implements(): void
    {
        $code = <<<'PHP'
<?php
namespace App\Models;

use App\Contracts\Auditable;
use App\Contracts\Notifiable;

class User implements Auditable, Notifiable
{
}
PHP;

        $parser = (new ParserFactory)->createForNewestSupportedVersion();
        $ast = $parser->parse($code);

        $traverser = new NodeTraverser;
        $traverser->addVisitor($this->visitor);
        $traverser->traverse($ast);

        $classes = $this->visitor->getClasses();

        $this->assertCount(2, $classes[0]['implements']);
        $this->assertContains('App\\Contracts\\Auditable', $classes[0]['implements']);
        $this->assertContains('App\\Contracts\\Notifiable', $classes[0]['implements']);
    }

    public function test_extracts_class_with_traits(): void
    {
        $code = <<<'PHP'
<?php
namespace App\Models;

use App\Traits\Loggable;
use App\Traits\Timestampable;

class User
{
    use Loggable, Timestampable;
}
PHP;

        $parser = (new ParserFactory)->createForNewestSupportedVersion();
        $ast = $parser->parse($code);

        $traverser = new NodeTraverser;
        $traverser->addVisitor($this->visitor);
        $traverser->traverse($ast);

        $classes = $this->visitor->getClasses();

        $this->assertCount(2, $classes[0]['traits']);
        $this->assertContains('App\\Traits\\Loggable', $classes[0]['traits']);
        $this->assertContains('App\\Traits\\Timestampable', $classes[0]['traits']);
    }

    public function test_extracts_class_with_methods(): void
    {
        $code = <<<'PHP'
<?php
class User
{
    public function getName(): string
    {
        return 'test';
    }

    public function setName(string $name): void
    {
    }
}
PHP;

        $parser = (new ParserFactory)->createForNewestSupportedVersion();
        $ast = $parser->parse($code);

        $traverser = new NodeTraverser;
        $traverser->addVisitor($this->visitor);
        $traverser->traverse($ast);

        $classes = $this->visitor->getClasses();

        $this->assertCount(2, $classes[0]['methods']);
        $this->assertEquals('getName', $classes[0]['methods'][0]['name']);
        $this->assertEquals('setName', $classes[0]['methods'][1]['name']);
    }

    public function test_extracts_class_with_properties(): void
    {
        $code = <<<'PHP'
<?php
class User
{
    public string $name;
    private int $age;
}
PHP;

        $parser = (new ParserFactory)->createForNewestSupportedVersion();
        $ast = $parser->parse($code);

        $traverser = new NodeTraverser;
        $traverser->addVisitor($this->visitor);
        $traverser->traverse($ast);

        $classes = $this->visitor->getClasses();

        $this->assertCount(2, $classes[0]['properties']);
        $this->assertEquals('name', $classes[0]['properties'][0]['name']);
        $this->assertEquals('age', $classes[0]['properties'][1]['name']);
    }

    public function test_excludes_private_methods_by_default(): void
    {
        $code = <<<'PHP'
<?php
class User
{
    public function publicMethod(): void {}
    private function privateMethod(): void {}
}
PHP;

        $parser = (new ParserFactory)->createForNewestSupportedVersion();
        $ast = $parser->parse($code);

        $traverser = new NodeTraverser;
        $traverser->addVisitor($this->visitor);
        $traverser->traverse($ast);

        $classes = $this->visitor->getClasses();

        $this->assertCount(1, $classes[0]['methods']);
        $this->assertEquals('publicMethod', $classes[0]['methods'][0]['name']);
    }

    public function test_includes_private_methods_when_enabled(): void
    {
        $typeConverter = new TypeStringConverter;
        $docblockExtractor = new DocblockExtractor;
        $methodExtractor = new MethodInfoExtractor($typeConverter, $docblockExtractor);
        $propertyExtractor = new PropertyInfoExtractor($typeConverter, $docblockExtractor);
        $namespaceResolver = new NamespaceResolver;

        $visitor = new ClassInfoVisitor(
            $methodExtractor,
            $propertyExtractor,
            $namespaceResolver,
            true // include private methods
        );

        $code = <<<'PHP'
<?php
class User
{
    public function publicMethod(): void {}
    private function privateMethod(): void {}
}
PHP;

        $parser = (new ParserFactory)->createForNewestSupportedVersion();
        $ast = $parser->parse($code);

        $traverser = new NodeTraverser;
        $traverser->addVisitor($visitor);
        $traverser->traverse($ast);

        $classes = $visitor->getClasses();

        $this->assertCount(2, $classes[0]['methods']);
    }

    public function test_handles_multiple_classes(): void
    {
        $code = <<<'PHP'
<?php
class User {}
class Post {}
PHP;

        $parser = (new ParserFactory)->createForNewestSupportedVersion();
        $ast = $parser->parse($code);

        $traverser = new NodeTraverser;
        $traverser->addVisitor($this->visitor);
        $traverser->traverse($ast);

        $classes = $this->visitor->getClasses();

        $this->assertCount(2, $classes);
        $this->assertEquals('User', $classes[0]['name']);
        $this->assertEquals('Post', $classes[1]['name']);
    }
}
