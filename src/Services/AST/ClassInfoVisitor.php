<?php

namespace Ajility\LaravelAutodoc\Services\AST;

use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Interface_;
use PhpParser\Node\Stmt\Trait_;
use PhpParser\NodeVisitorAbstract;

class ClassInfoVisitor extends NodeVisitorAbstract
{
    private array $classes = [];

    public function __construct(
        private MethodInfoExtractor $methodExtractor,
        private PropertyInfoExtractor $propertyExtractor,
        private NamespaceResolver $namespaceResolver,
        private bool $includePrivateMethods = false
    ) {}

    public function enterNode(Node $node)
    {
        if ($node instanceof Node\Stmt\Namespace_) {
            $this->namespaceResolver->setNamespace($node->name?->toString());
        }

        if ($node instanceof Node\Stmt\Use_) {
            foreach ($node->uses as $use) {
                $alias = $use->alias?->toString() ?? $use->name->getLast();
                $this->namespaceResolver->addUseStatement($alias, $use->name->toString());
            }
        }

        if ($node instanceof Class_ || $node instanceof Interface_ || $node instanceof Trait_) {
            $this->classes[] = $this->extractClassInfo($node);
        }

        return null;
    }

    public function getClasses(): array
    {
        return $this->classes;
    }

    private function extractClassInfo($node): array
    {
        $classInfo = [
            'name' => $node->name?->toString() ?? 'anonymous',
            'type' => $this->getNodeType($node),
            'docblock' => $node->getDocComment()?->getText(),
            'namespace' => $this->namespaceResolver->getCurrentNamespace(),
            'extends' => null,
            'implements' => [],
            'traits' => [],
            'methods' => [],
            'properties' => [],
        ];

        // Get extends
        if ($node instanceof Class_ && $node->extends) {
            $classInfo['extends'] = $this->namespaceResolver->resolve($node->extends->toString());
        }

        // Get implements
        if ($node instanceof Class_ && $node->implements) {
            foreach ($node->implements as $interface) {
                $classInfo['implements'][] = $this->namespaceResolver->resolve($interface->toString());
            }
        }

        // Get methods, properties, and traits
        foreach ($node->stmts as $stmt) {
            if ($stmt instanceof ClassMethod) {
                $methodInfo = $this->methodExtractor->extract($stmt);

                // Skip private/protected methods if not included
                if (! $this->includePrivateMethods &&
                    ($methodInfo['visibility'] === 'private' || $methodInfo['visibility'] === 'protected')) {
                    continue;
                }

                $classInfo['methods'][] = $methodInfo;
            }

            if ($stmt instanceof Node\Stmt\Property) {
                $propertyInfos = $this->propertyExtractor->extract($stmt);
                foreach ($propertyInfos as $propertyInfo) {
                    $classInfo['properties'][] = $propertyInfo;
                }
            }

            // Get traits
            if ($stmt instanceof Node\Stmt\TraitUse) {
                foreach ($stmt->traits as $trait) {
                    $classInfo['traits'][] = $this->namespaceResolver->resolve($trait->toString());
                }
            }
        }

        return $classInfo;
    }

    private function getNodeType($node): string
    {
        if ($node instanceof Class_) {
            return 'class';
        }

        if ($node instanceof Interface_) {
            return 'interface';
        }

        if ($node instanceof Trait_) {
            return 'trait';
        }

        return 'unknown';
    }
}
