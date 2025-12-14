<?php

namespace Ajility\LaravelAutodoc\Tests\Unit;

use Ajility\LaravelAutodoc\Services\ClassParser;
use Ajility\LaravelAutodoc\Tests\TestCase;
use SplFileInfo;

class ClassParserTest extends TestCase
{
    private ClassParser $parser;

    private string $fixturesDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = $this->createClassParser();
        $this->fixturesDir = __DIR__.'/../fixtures/src';
    }

    public function test_it_parses_basic_class_information(): void
    {
        $file = new SplFileInfo($this->fixturesDir.'/SampleClass.php');
        $classes = $this->parser->parseFile($file);

        $this->assertCount(1, $classes);

        $class = $classes[0];
        $this->assertEquals('SampleClass', $class['name']);
        $this->assertEquals('class', $class['type']);
        $this->assertEquals('App\Sample', $class['namespace']);
        $this->assertNull($class['extends']);
        $this->assertEmpty($class['implements']);
    }

    public function test_it_parses_class_docblock(): void
    {
        $file = new SplFileInfo($this->fixturesDir.'/SampleClass.php');
        $classes = $this->parser->parseFile($file);

        $class = $classes[0];
        $this->assertNotNull($class['docblock']);
        $this->assertStringContainsString('A sample class for testing documentation generation', $class['docblock']);
    }

    public function test_it_parses_public_methods_by_default(): void
    {
        $file = new SplFileInfo($this->fixturesDir.'/SampleClass.php');
        $classes = $this->parser->parseFile($file, false);

        $class = $classes[0];
        $methodNames = array_map(fn ($m) => $m['name'], $class['methods']);

        $this->assertContains('publicMethod', $methodNames);
        $this->assertContains('staticMethod', $methodNames);
        $this->assertNotContains('protectedMethod', $methodNames);
        $this->assertNotContains('privateMethod', $methodNames);
    }

    public function test_it_includes_private_methods_when_enabled(): void
    {
        $file = new SplFileInfo($this->fixturesDir.'/SampleClass.php');
        $classes = $this->parser->parseFile($file, true);

        $class = $classes[0];
        $methodNames = array_map(fn ($m) => $m['name'], $class['methods']);

        $this->assertContains('publicMethod', $methodNames);
        $this->assertContains('protectedMethod', $methodNames);
        $this->assertContains('privateMethod', $methodNames);
        $this->assertContains('staticMethod', $methodNames);
    }

    public function test_it_parses_method_details(): void
    {
        $file = new SplFileInfo($this->fixturesDir.'/SampleClass.php');
        $classes = $this->parser->parseFile($file);

        $class = $classes[0];
        $publicMethod = array_values(array_filter($class['methods'], fn ($m) => $m['name'] === 'publicMethod'))[0];

        $this->assertEquals('public', $publicMethod['visibility']);
        $this->assertFalse($publicMethod['isStatic']);
        $this->assertFalse($publicMethod['isAbstract']);
        $this->assertFalse($publicMethod['isFinal']);
        $this->assertEquals('string', $publicMethod['returnType']);
        $this->assertNotNull($publicMethod['docblock']);
    }

    public function test_it_parses_method_parameters(): void
    {
        $file = new SplFileInfo($this->fixturesDir.'/SampleClass.php');
        $classes = $this->parser->parseFile($file);

        $class = $classes[0];
        $publicMethod = array_values(array_filter($class['methods'], fn ($m) => $m['name'] === 'publicMethod'))[0];

        $this->assertCount(2, $publicMethod['parameters']);

        $nameParam = $publicMethod['parameters'][0];
        $this->assertEquals('name', $nameParam['name']);
        $this->assertEquals('string', $nameParam['type']);
        $this->assertNull($nameParam['default']);
        $this->assertFalse($nameParam['byRef']);
        $this->assertFalse($nameParam['variadic']);

        $ageParam = $publicMethod['parameters'][1];
        $this->assertEquals('age', $ageParam['name']);
        $this->assertEquals('int', $ageParam['type']);
        $this->assertEquals('18', $ageParam['default']);
    }

    public function test_it_parses_static_methods(): void
    {
        $file = new SplFileInfo($this->fixturesDir.'/SampleClass.php');
        $classes = $this->parser->parseFile($file);

        $class = $classes[0];
        $staticMethod = array_values(array_filter($class['methods'], fn ($m) => $m['name'] === 'staticMethod'))[0];

        $this->assertTrue($staticMethod['isStatic']);
        $this->assertEquals('public', $staticMethod['visibility']);
    }

    public function test_it_parses_properties(): void
    {
        $file = new SplFileInfo($this->fixturesDir.'/SampleClass.php');
        $classes = $this->parser->parseFile($file);

        $class = $classes[0];
        $this->assertNotEmpty($class['properties']);

        $publicProp = array_values(array_filter($class['properties'], fn ($p) => $p['name'] === 'publicProperty'))[0];
        $this->assertEquals('public', $publicProp['visibility']);
        $this->assertEquals('string', $publicProp['type']);
        $this->assertEquals("'default'", $publicProp['default']);
    }

    public function test_it_parses_interfaces(): void
    {
        $file = new SplFileInfo($this->fixturesDir.'/SampleInterface.php');
        $classes = $this->parser->parseFile($file);

        $this->assertCount(1, $classes);

        $interface = $classes[0];
        $this->assertEquals('SampleInterface', $interface['name']);
        $this->assertEquals('interface', $interface['type']);
        $this->assertEquals('App\Sample', $interface['namespace']);
    }

    public function test_it_parses_traits(): void
    {
        $file = new SplFileInfo($this->fixturesDir.'/SampleTrait.php');
        $classes = $this->parser->parseFile($file);

        $this->assertCount(1, $classes);

        $trait = $classes[0];
        $this->assertEquals('SampleTrait', $trait['name']);
        $this->assertEquals('trait', $trait['type']);
        $this->assertEquals('App\Sample', $trait['namespace']);
    }

    public function test_it_parses_class_inheritance(): void
    {
        $file = new SplFileInfo($this->fixturesDir.'/ExtendedClass.php');
        $classes = $this->parser->parseFile($file);

        $class = $classes[0];
        $this->assertEquals('ExtendedClass', $class['name']);
        $this->assertEquals('App\Sample\SampleClass', $class['extends']);
        $this->assertContains('App\Sample\SampleInterface', $class['implements']);
    }

    public function test_it_handles_invalid_php_file(): void
    {
        $testFile = sys_get_temp_dir().'/invalid_'.uniqid().'.php';
        file_put_contents($testFile, '<?php this is not valid php');

        $file = new SplFileInfo($testFile);
        $classes = $this->parser->parseFile($file);

        $this->assertEmpty($classes);

        unlink($testFile);
    }

    public function test_it_handles_empty_file(): void
    {
        $testFile = sys_get_temp_dir().'/empty_'.uniqid().'.php';
        file_put_contents($testFile, '<?php');

        $file = new SplFileInfo($testFile);
        $classes = $this->parser->parseFile($file);

        $this->assertEmpty($classes);

        unlink($testFile);
    }
}
