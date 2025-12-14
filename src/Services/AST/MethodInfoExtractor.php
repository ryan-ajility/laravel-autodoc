<?php

namespace Ajility\LaravelAutodoc\Services\AST;

use PhpParser\Node\Stmt\ClassMethod;

class MethodInfoExtractor
{
    public function __construct(
        private TypeStringConverter $typeConverter,
        private DocblockExtractor $docblockExtractor
    ) {}

    /**
     * Extract method information from a method node.
     */
    public function extract(ClassMethod $method): array
    {
        return [
            'name' => $method->name->toString(),
            'docblock' => $this->docblockExtractor->extract($method),
            'visibility' => $this->getVisibility($method),
            'isStatic' => $method->isStatic(),
            'isAbstract' => $method->isAbstract(),
            'isFinal' => $method->isFinal(),
            'parameters' => $this->extractParameters($method),
            'returnType' => $this->typeConverter->convert($method->getReturnType()),
        ];
    }

    /**
     * Extract parameters from a method.
     */
    public function extractParameters(ClassMethod $method): array
    {
        $params = [];

        foreach ($method->params as $param) {
            $params[] = [
                'name' => $param->var->name,
                'type' => $this->typeConverter->convert($param->type),
                'default' => $param->default ? $this->typeConverter->nodeToString($param->default) : null,
                'byRef' => $param->byRef,
                'variadic' => $param->variadic,
            ];
        }

        return $params;
    }

    /**
     * Get the visibility of a method.
     */
    private function getVisibility(ClassMethod $method): string
    {
        if ($method->isPrivate()) {
            return 'private';
        }

        if ($method->isProtected()) {
            return 'protected';
        }

        return 'public';
    }
}
