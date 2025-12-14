<?php

namespace Ajility\LaravelAutodoc\Services\AST;

use PhpParser\Node\Stmt\Property;

class PropertyInfoExtractor
{
    public function __construct(
        private TypeStringConverter $typeConverter,
        private DocblockExtractor $docblockExtractor
    ) {}

    /**
     * Extract property information from a property node.
     */
    public function extract(Property $property): array
    {
        $props = [];
        foreach ($property->props as $prop) {
            $props[] = [
                'name' => $prop->name->toString(),
                'docblock' => $this->docblockExtractor->extract($property),
                'visibility' => $this->getVisibility($property),
                'isStatic' => $property->isStatic(),
                'type' => $this->typeConverter->convert($property->type),
                'default' => $prop->default ? $this->typeConverter->nodeToString($prop->default) : null,
            ];
        }

        return $props;
    }

    /**
     * Get the visibility of a property.
     */
    private function getVisibility(Property $property): string
    {
        if ($property->isPrivate()) {
            return 'private';
        }

        if ($property->isProtected()) {
            return 'protected';
        }

        return 'public';
    }
}
