<?php

namespace Ajility\LaravelAutodoc\Services;

class DocblockProcessor
{
    /**
     * Format docblock to markdown.
     */
    public function format(?string $docblock): string
    {
        if (! $docblock) {
            return '';
        }

        // Remove /** and */
        $docblock = preg_replace('/^\/\*\*|\*\/$/', '', $docblock);

        // Remove leading asterisks and spaces
        $lines = explode("\n", $docblock);
        $cleaned = [];

        foreach ($lines as $line) {
            $line = preg_replace('/^\s*\*\s?/', '', $line);
            $cleaned[] = $line;
        }

        $text = implode("\n", $cleaned);

        // Remove @param, @return, @throws, etc. from the description
        $text = preg_replace('/@\w+.*$/m', '', $text);

        return trim($text);
    }

    /**
     * Extract parameter description from docblock.
     */
    public function extractParamDescription(?string $docblock, string $paramName): string
    {
        if (! $docblock) {
            return '';
        }

        if (preg_match('/@param\s+[\w\\\\|?<>,\[\]]+\s+\$'.$paramName.'\s+(.+)$/m', $docblock, $matches)) {
            return trim($matches[1]);
        }

        return '';
    }

    /**
     * Extract return description from docblock.
     */
    public function extractReturnDescription(?string $docblock): string
    {
        if (! $docblock) {
            return '';
        }

        // Match @return followed by type, then description
        // Type can include generics like array<string, mixed>, unions like string|int, nullable like ?string
        // Match pattern: @return <type with possible <...>> <description>
        if (preg_match('/@return\s+[^\s<]+(?:<[^>]+>)?(?:\|[^\s<]+(?:<[^>]+>)?)*\s+([^\n*]+)/m', $docblock, $matches)) {
            return trim($matches[1]);
        }

        return '';
    }
}
