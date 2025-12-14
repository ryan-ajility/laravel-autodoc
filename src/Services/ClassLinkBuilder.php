<?php

namespace Ajility\LaravelAutodoc\Services;

class ClassLinkBuilder
{
    public function __construct(
        private PathResolver $pathResolver
    ) {}

    /**
     * Create a clickable link for a class reference.
     *
     * @param  string  $className  The class name (fully qualified from parser)
     * @param  array  $classMap  Map of fully qualified class names to their markdown file paths
     * @param  string  $currentFilePath  The current file path for relative link calculation
     * @return string The markdown link or inline code if not found
     */
    public function createLink(string $className, array $classMap, string $currentFilePath): string
    {
        // The className is already fully qualified from the parser
        // Check if we have a documentation page for this class
        if (isset($classMap[$className])) {
            $targetPath = $classMap[$className];
            $relativePath = $this->pathResolver->getRelativePath($currentFilePath, $targetPath);

            // Extract just the short class name for display
            $shortName = $this->getShortClassName($className);

            return "[`{$shortName}`]({$relativePath})";
        }

        // If not found in our class map, just return as inline code (external class)
        // Use short name for display
        $shortName = $this->getShortClassName($className);

        return "`{$shortName}`";
    }

    /**
     * Extract the short class name from a fully qualified class name.
     *
     * @param  string  $className  The fully qualified class name
     * @return string The short class name
     */
    public function getShortClassName(string $className): string
    {
        // Remove leading backslash if present
        $className = ltrim($className, '\\');

        // Get the last part after the last backslash
        if (str_contains($className, '\\')) {
            return substr($className, strrpos($className, '\\') + 1);
        }

        return $className;
    }
}
