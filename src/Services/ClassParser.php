<?php

namespace Ajility\LaravelAutodoc\Services;

use Ajility\LaravelAutodoc\Services\AST\ClassInfoVisitor;
use Ajility\LaravelAutodoc\Services\AST\MethodInfoExtractor;
use Ajility\LaravelAutodoc\Services\AST\NamespaceResolver;
use Ajility\LaravelAutodoc\Services\AST\PropertyInfoExtractor;
use PhpParser\Error;
use PhpParser\NodeTraverser;
use PhpParser\ParserFactory;
use SplFileInfo;

class ClassParser
{
    private array $parsedClasses = [];

    public function __construct(
        private MethodInfoExtractor $methodExtractor,
        private PropertyInfoExtractor $propertyExtractor,
        private NamespaceResolver $namespaceResolver
    ) {}

    /**
     * Parse a PHP file and extract class information.
     */
    public function parseFile(SplFileInfo $file, bool $includePrivateMethods = false): array
    {
        $this->parsedClasses = [];

        $code = file_get_contents($file->getPathname());

        $parser = (new ParserFactory)->createForNewestSupportedVersion();

        try {
            $ast = $parser->parse($code);

            // Reset the namespace resolver for each file
            $this->namespaceResolver->reset();

            // Create visitor with injected dependencies
            $visitor = new ClassInfoVisitor(
                $this->methodExtractor,
                $this->propertyExtractor,
                $this->namespaceResolver,
                $includePrivateMethods
            );

            $traverser = new NodeTraverser;
            $traverser->addVisitor($visitor);
            $traverser->traverse($ast);

            $this->parsedClasses = $visitor->getClasses();

        } catch (Error $e) {
            // If parsing fails, return empty array
            return [];
        }

        return $this->parsedClasses;
    }
}
