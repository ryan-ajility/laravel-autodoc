<?php

namespace Ajility\LaravelAutodoc\Services\AST;

use PhpParser\Node;

class TypeStringConverter
{
    /**
     * Convert a type node to its string representation.
     *
     * @param  mixed  $type
     */
    public function convert($type): ?string
    {
        if ($type === null) {
            return null;
        }

        if (method_exists($type, 'toString')) {
            return $type->toString();
        }

        // Handle complex types like nullable types
        if ($type instanceof Node\NullableType) {
            return '?'.$this->convert($type->type);
        }

        if ($type instanceof Node\UnionType) {
            $types = array_map(fn ($t) => $this->convert($t), $type->types);

            return implode('|', $types);
        }

        if ($type instanceof Node\IntersectionType) {
            $types = array_map(fn ($t) => $this->convert($t), $type->types);

            return implode('&', $types);
        }

        if ($type instanceof Node\Identifier || $type instanceof Node\Name) {
            return $type->toString();
        }

        return 'mixed';
    }

    /**
     * Convert a node to its string representation.
     */
    public function nodeToString(Node $node): string
    {
        if ($node instanceof Node\Scalar\String_) {
            return "'{$node->value}'";
        }

        if ($node instanceof Node\Scalar\Int_ || $node instanceof Node\Scalar\Float_) {
            return (string) $node->value;
        }

        if ($node instanceof Node\Expr\ConstFetch) {
            return $node->name->toString();
        }

        if ($node instanceof Node\Expr\Array_) {
            return '[]';
        }

        return 'unknown';
    }
}
