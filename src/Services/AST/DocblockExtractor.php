<?php

namespace Ajility\LaravelAutodoc\Services\AST;

use PhpParser\Node;

class DocblockExtractor
{
    /**
     * Extract docblock comment from a node.
     */
    public function extract(Node $node): ?string
    {
        $docComment = $node->getDocComment();

        return $docComment ? $docComment->getText() : null;
    }
}
