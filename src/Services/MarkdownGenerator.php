<?php

namespace Ajility\LaravelAutodoc\Services;

class MarkdownGenerator
{
    /**
     * Maximum number of directory levels to traverse upward when calculating relative paths.
     * Prevents generating excessively long paths like ../../../../../../../Users/...
     */
    private const MAX_UPWARD_PATH_LEVELS = 5;

    /**
     * Generate markdown documentation for a class.
     *
     * @param  array  $classInfo  The parsed class information
     * @param  string  $filePath  The original file path (relative to source directory)
     * @param  array  $classMap  Map of fully qualified class names to their markdown file paths
     * @param  string|null  $absoluteSourcePath  The absolute path to the source file
     * @param  string|null  $outputPath  The absolute path to the documentation output directory
     * @return string The generated markdown
     */
    public function generateClassMarkdown(array $classInfo, string $filePath, array $classMap = [], ?string $absoluteSourcePath = null, ?string $outputPath = null): string
    {
        $markdown = '';

        // Add title (just the class name, no "class:" prefix)
        $markdown .= "# {$classInfo['name']}\n\n";

        // Add source file path if available
        if ($absoluteSourcePath && $outputPath) {
            $relativeSourcePath = $this->calculateRelativePathToSource($filePath, $absoluteSourcePath, $outputPath);
            $markdown .= "**Source:** [{$filePath}]({$relativeSourcePath})\n\n";
        }

        // Add inheritance and trait information
        if ($classInfo['extends'] || ! empty($classInfo['implements']) || ! empty($classInfo['traits'])) {
            $inheritanceInfo = [];

            if ($classInfo['extends']) {
                $extendsLink = $this->createClassLink($classInfo['extends'], $classMap, $filePath);
                $inheritanceInfo[] = "**Extends:** {$extendsLink}";
            }

            if (! empty($classInfo['implements'])) {
                $interfaces = array_map(
                    fn ($interface) => $this->createClassLink($interface, $classMap, $filePath),
                    $classInfo['implements']
                );
                $inheritanceInfo[] = '**Implements:** '.implode(', ', $interfaces);
            }

            if (! empty($classInfo['traits'])) {
                $traits = array_map(
                    fn ($trait) => $this->createClassLink($trait, $classMap, $filePath),
                    $classInfo['traits']
                );
                $inheritanceInfo[] = '**Uses Traits:** '.implode(', ', $traits);
            }

            $markdown .= implode("  \n", $inheritanceInfo)."\n\n";
        }

        // Add class docblock
        if ($classInfo['docblock']) {
            $markdown .= "## Description\n\n";
            $markdown .= $this->formatDocblock($classInfo['docblock'])."\n\n";
        }

        // Add properties
        if (! empty($classInfo['properties'])) {
            $markdown .= "## Properties\n\n";
            $markdown .= $this->generatePropertiesTable($classInfo['properties']);
            $markdown .= "\n";
        }

        // Add methods
        if (! empty($classInfo['methods'])) {
            $markdown .= "## Methods\n\n";

            foreach ($classInfo['methods'] as $method) {
                $markdown .= $this->generateMethodMarkdown($method);
            }
        }

        return $markdown;
    }

    /**
     * Generate properties table.
     */
    private function generatePropertiesTable(array $properties): string
    {
        $markdown = "| Name | Visibility | Type | Default | Description |\n";
        $markdown .= "| --- | --- | --- | --- | --- |\n";

        foreach ($properties as $propertyGroup) {
            foreach ($propertyGroup as $property) {
                $name = '`$'.$property['name'].'`';
                $visibility = '`'.$property['visibility'].'`';
                $type = '`'.($property['type'] ?? 'mixed').'`';
                $default = $property['default'] !== null ? '`'.$property['default'].'`' : '`-`';
                $description = $property['docblock'] ? $this->formatDocblock($property['docblock']) : '';

                $markdown .= "| {$name} | {$visibility} | {$type} | {$default} | {$description} |\n";
            }
        }

        return $markdown;
    }

    /**
     * Generate markdown for a method.
     */
    private function generateMethodMarkdown(array $method): string
    {
        $modifiers = [];

        if ($method['isAbstract']) {
            $modifiers[] = 'abstract';
        }

        if ($method['isFinal']) {
            $modifiers[] = 'final';
        }

        if ($method['isStatic']) {
            $modifiers[] = 'static';
        }

        $modifierString = ! empty($modifiers) ? implode(' ', $modifiers).' ' : '';

        // Generate method signature
        $signature = $this->generateMethodSignature($method);

        $markdown = "### {$modifierString}{$method['name']}()\n\n";
        $markdown .= "```php\n{$method['visibility']} {$modifierString}{$signature}\n```\n\n";

        // Add docblock
        if ($method['docblock']) {
            $markdown .= $this->formatDocblock($method['docblock'])."\n\n";
        }

        // Add parameters table
        if (! empty($method['parameters'])) {
            $markdown .= "**Parameters:**\n\n";
            $markdown .= "| Name | Type | Default | Description |\n";
            $markdown .= "|------|------|---------|-------------|\n";

            foreach ($method['parameters'] as $param) {
                $name = ($param['variadic'] ? '...' : '').'$'.$param['name'];
                $type = $param['type'] ?? 'mixed';
                $default = $param['default'] ?? '-';

                $description = $this->extractParamDescription($method['docblock'], $param['name']);

                $markdown .= "| `{$name}` | `{$type}` | `{$default}` | {$description} |\n";
            }

            $markdown .= "\n";
        }

        // Add return type
        if ($method['returnType']) {
            $returnDescription = $this->extractReturnDescription($method['docblock']);

            if ($returnDescription) {
                // Inline format: **Returns:** `type`: description
                $markdown .= "**Returns:**\n`{$method['returnType']}`: {$returnDescription}\n\n";
            } else {
                // Just the type if no description
                $markdown .= "**Returns:**\n`{$method['returnType']}`\n\n";
            }
        }

        $markdown .= "---\n\n";

        return $markdown;
    }

    /**
     * Generate method signature.
     */
    private function generateMethodSignature(array $method): string
    {
        $params = [];

        foreach ($method['parameters'] as $param) {
            $paramStr = '';

            if ($param['type']) {
                $paramStr .= $param['type'].' ';
            }

            if ($param['byRef']) {
                $paramStr .= '&';
            }

            if ($param['variadic']) {
                $paramStr .= '...';
            }

            $paramStr .= '$'.$param['name'];

            if ($param['default'] !== null) {
                $paramStr .= ' = '.$param['default'];
            }

            $params[] = $paramStr;
        }

        $signature = $method['name'].'('.implode(', ', $params).')';

        if ($method['returnType']) {
            $signature .= ': '.$method['returnType'];
        }

        return $signature;
    }

    /**
     * Format docblock to markdown.
     */
    private function formatDocblock(?string $docblock): string
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
    private function extractParamDescription(?string $docblock, string $paramName): string
    {
        if (! $docblock) {
            return '';
        }

        if (preg_match('/@param\s+[\w\\\\|]+\s+\$'.$paramName.'\s+(.+)$/m', $docblock, $matches)) {
            return trim($matches[1]);
        }

        return '';
    }

    /**
     * Extract return description from docblock.
     */
    private function extractReturnDescription(?string $docblock): string
    {
        if (! $docblock) {
            return '';
        }

        if (preg_match('/@return\s+[\w\\\\|]+\s+(.+)$/m', $docblock, $matches)) {
            return trim($matches[1]);
        }

        return '';
    }

    /**
     * Generate an index markdown file.
     *
     * @param  string  $title  The documentation title
     */
    public function generateIndexMarkdown(string $title): string
    {
        $markdown = "# {$title}\n";
        $markdown .= 'Generated on: '.date('Y-m-d H:i:s')."\n\n";

        $markdown .= "The documentation in this directory is entirely dependent on the contents of its corresponding codebase. Edits will be removed upon regeneration.\n\n";

        $markdown .= "## Overview\n\n";
        $markdown .= "This documentation was automatically generated using [Laravel Autodoc](https://github.com/ryan-ajility/laravel-autodoc), a package that extracts documentation directly from PHP source code. All information is derived from:\n\n";
        $markdown .= "- PHP docblocks\n";
        $markdown .= "- Class structures and relationships\n";
        $markdown .= "- Method signatures and parameters\n";
        $markdown .= "- Type declarations and return types\n\n";

        $markdown .= "## What's Included\n\n";
        $markdown .= "This documentation includes:\n\n";
        $markdown .= "- **All Classes, Interfaces, and Traits**: Complete documentation for every class in the configured source directories\n";
        $markdown .= "- **Method Signatures**: Full method documentation with parameters, return types, and descriptions\n";
        $markdown .= "- **Property Information**: Class properties with their types, visibility, and descriptions\n";
        $markdown .= "- **Namespace Organization**: Documentation is organized following your project's namespace structure\n";
        $markdown .= "- **Cross-References**: Related classes and inheritance hierarchies\n\n";

        $markdown .= "## Navigation\n\n";
        $markdown .= "- Documentation structure mirrors your source code directory structure\n";
        $markdown .= "- Use your IDE or markdown viewer's search functionality to find specific classes or methods\n";
        $markdown .= "- Each class documentation includes a clickable link to its source file\n";
        $markdown .= "- Click the **Source:** link at the top of each class page to navigate directly to the source code\n\n";

        $markdown .= "## How to Regenerate\n\n";
        $markdown .= "To regenerate this documentation after code changes:\n\n";
        $markdown .= "```bash\n";
        $markdown .= "php artisan autodoc:generate\n";
        $markdown .= "```\n\n";

        $markdown .= "### Regeneration Options\n\n";
        $markdown .= "```bash\n";
        $markdown .= "# Generate with all available methods\n";
        $markdown .= "php artisan autodoc:generate --path=custom/path --private --protected\n\n";
        $markdown .= "# Specify custom source directories\n";
        $markdown .= "php artisan autodoc:generate --source=app/Custom --source=packages/MyPackage\n\n";
        $markdown .= "# Exclude specific directories\n";
        $markdown .= "php artisan autodoc:generate --exclude=app/Deprecated\n";
        $markdown .= "```\n\n";

        $markdown .= "### When to Regenerate\n\n";
        $markdown .= "Regenerate the documentation when:\n\n";
        $markdown .= "- You add new classes, methods, or properties\n";
        $markdown .= "- You update docblock comments\n";
        $markdown .= "- You change method signatures or return types\n";
        $markdown .= "- You modify class relationships (extends, implements)\n";
        $markdown .= "- You want to reflect the current state of your codebase\n\n";

        $markdown .= "## Configuration\n\n";
        $markdown .= "Documentation generation is configured via `autodoc.json` in the project root or `config/autodoc.php`. See the package README for configuration options.\n\n";

        $markdown .= "## Understanding the Documentation Format\n\n";
        $markdown .= "Each class documentation file contains:\n\n";
        $markdown .= "### Header Section\n";
        $markdown .= "- **Class Name**: The name of the class, interface, or trait\n";
        $markdown .= "- **Source Link**: Clickable link to the source file (relative path from documentation)\n";
        $markdown .= "- **Extends/Implements**: Parent classes and interfaces with links to their documentation\n";
        $markdown .= "- **Traits**: Used traits with links to their documentation\n\n";

        $markdown .= "### Description\n";
        $markdown .= "The description section contains the class-level docblock comments explaining the purpose and usage of the class.\n\n";

        $markdown .= "### Properties\n";
        $markdown .= "Table showing all class properties with:\n";
        $markdown .= "- Name and type\n";
        $markdown .= "- Visibility (public, protected, private)\n";
        $markdown .= "- Description from docblocks\n\n";

        $markdown .= "### Methods\n";
        $markdown .= "Detailed documentation for each method including:\n";
        $markdown .= "- **Signature**: Complete method signature with modifiers\n";
        $markdown .= "- **Parameters Table**: Name, type, and description for each parameter\n";
        $markdown .= "- **Return Type**: What the method returns\n";
        $markdown .= "- **Description**: Detailed explanation from docblocks\n\n";

        $markdown .= "## Important Notes\n\n";
        $markdown .= "⚠️ **Do Not Edit Directly**: All files in this directory are auto-generated. Any manual edits will be lost when documentation is regenerated.\n\n";
        $markdown .= "⚠️ **Source of Truth**: The source code itself is the source of truth. This documentation reflects the state of the codebase at the time of generation.\n\n";
        $markdown .= "⚠️ **Keep Updated**: For accurate documentation, regenerate regularly as part of your development workflow (e.g., before commits, in CI/CD pipelines, before releases).\n\n";

        $markdown .= "## Integration with Development Workflow\n\n";
        $markdown .= "### In CI/CD\n";
        $markdown .= "Add documentation generation to your pipeline:\n\n";
        $markdown .= "```yaml\n";
        $markdown .= "# Example GitHub Actions\n";
        $markdown .= "- name: Generate Documentation\n";
        $markdown .= "  run: php artisan autodoc:generate\n";
        $markdown .= "```\n\n";

        $markdown .= "### Git Hooks\n";
        $markdown .= "Consider adding a pre-commit hook to regenerate docs:\n\n";
        $markdown .= "```bash\n";
        $markdown .= "#!/bin/sh\n";
        $markdown .= "php artisan autodoc:generate\n";
        $markdown .= "git add storage/app/docs/\n";
        $markdown .= "```\n\n";

        $markdown .= "### Documentation Reviews\n";
        $markdown .= "Use the generated documentation during:\n";
        $markdown .= "- Code reviews to verify docblock quality\n";
        $markdown .= "- Onboarding new team members\n";
        $markdown .= "- API documentation for internal services\n";
        $markdown .= "- Planning refactoring efforts\n\n";

        $markdown .= "## Troubleshooting\n\n";
        $markdown .= "**Documentation is missing classes**: Check that your source directories are configured correctly in `autodoc.json` or `config/autodoc.php`.\n\n";
        $markdown .= "**Private methods not showing**: By default, only public methods are documented. Use `--private` flag or set `include_private_methods: true` in configuration.\n\n";
        $markdown .= "**Outdated information**: Run `php artisan autodoc:generate` to regenerate with current code.\n\n";
        $markdown .= "**File not found errors**: Ensure all configured source directories exist and are readable.\n\n";

        $markdown .= "## Package Information\n\n";
        $markdown .= "- **Package**: ajility/laravel-autodoc\n";
        $markdown .= "- **Requirements**: PHP 8.2+, Laravel 12+\n";
        $markdown .= "- **Parser**: Uses [nikic/php-parser](https://github.com/nikic/PHP-Parser) for accurate PHP code analysis\n";
        $markdown .= "- **License**: MIT\n\n";
        $markdown .= "For more information about the package itself, see the [main package README](https://github.com/ryan-ajility/laravel-autodoc).\n";

        return $markdown;
    }

    /**
     * Create a clickable link for a class reference.
     *
     * @param  string  $className  The class name (fully qualified from parser)
     * @param  array  $classMap  Map of fully qualified class names to their markdown file paths
     * @param  string  $currentFilePath  The current file path for relative link calculation
     * @return string The markdown link or inline code if not found
     */
    private function createClassLink(string $className, array $classMap, string $currentFilePath): string
    {
        // The className is already fully qualified from the parser
        // Check if we have a documentation page for this class
        if (isset($classMap[$className])) {
            $targetPath = $classMap[$className];
            $relativePath = $this->getRelativePath($currentFilePath, $targetPath);

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
    private function getShortClassName(string $className): string
    {
        // Remove leading backslash if present
        $className = ltrim($className, '\\');

        // Get the last part after the last backslash
        if (str_contains($className, '\\')) {
            return substr($className, strrpos($className, '\\') + 1);
        }

        return $className;
    }

    /**
     * Calculate relative path from current file to target file.
     *
     * @param  string  $from  The current file path (e.g., "Services/PaymentService.md")
     * @param  string  $to  The target file path (e.g., "Contracts/PaymentProcessorInterface.md")
     * @return string The relative path
     */
    private function getRelativePath(string $from, string $to): string
    {
        // Convert paths to arrays
        $from = str_replace('.md', '', $from);
        $to = str_replace('.md', '', $to);

        $fromParts = explode('/', dirname($from));
        $toParts = explode('/', dirname($to));

        // Remove common path prefix
        while (count($fromParts) > 0 && count($toParts) > 0 && $fromParts[0] === $toParts[0]) {
            array_shift($fromParts);
            array_shift($toParts);
        }

        // Build relative path
        $relativePath = '';

        // Add ../ for each remaining directory in from path
        if (count($fromParts) > 0 && $fromParts[0] !== '.') {
            $relativePath = str_repeat('../', count($fromParts));
        }

        // Add the remaining to path
        if (count($toParts) > 0 && $toParts[0] !== '.') {
            $relativePath .= implode('/', $toParts).'/';
        }

        // Add the target filename
        $relativePath .= basename($to).'.md';

        return $relativePath;
    }

    /**
     * Calculate the relative path from the documentation file to the source file.
     *
     * @param  string  $relativeDocPath  The relative path of the documentation file (e.g., "Services/PaymentService.md")
     * @param  string  $absoluteSourcePath  The absolute path to the source file
     * @param  string  $outputPath  The absolute path to the documentation output directory
     * @return string The relative path from the documentation file to the source file
     */
    private function calculateRelativePathToSource(string $relativeDocPath, string $absoluteSourcePath, string $outputPath): string
    {
        // Get the absolute path of the documentation file
        $docFilePath = $outputPath.DIRECTORY_SEPARATOR.$relativeDocPath;
        $docDir = dirname($docFilePath);

        // Normalize paths by converting to forward slashes and using realpath
        $docDir = $this->normalizePath($docDir);
        $absoluteSourcePath = $this->normalizePath($absoluteSourcePath);

        // Convert both paths to arrays using forward slashes
        $docParts = explode('/', $docDir);
        $sourceParts = explode('/', $absoluteSourcePath);

        // Find common prefix
        $commonPrefixLength = 0;
        $minLength = min(count($docParts), count($sourceParts));

        for ($i = 0; $i < $minLength; $i++) {
            if ($docParts[$i] === $sourceParts[$i]) {
                $commonPrefixLength++;
            } else {
                break;
            }
        }

        // Remove common prefix
        $docPartsRelative = array_slice($docParts, $commonPrefixLength);
        $sourcePartsRelative = array_slice($sourceParts, $commonPrefixLength);

        // Calculate how many "../" we would need
        $upwardLevels = count($docPartsRelative);

        // If there's no common ancestor, or if the relative path would be excessively long
        // (more than 5 levels up), fall back to using the source-relative path as a reference.
        // This prevents generating paths like ../../../../../../../Users/...
        // Reasonable relative paths should not need to go up more than a few levels.
        if ($commonPrefixLength === 0 || $upwardLevels > self::MAX_UPWARD_PATH_LEVELS) {
            // Use the relativeDocPath as a template since it mirrors the source structure
            // Just convert .md to .php
            return str_replace('.md', '.php', $relativeDocPath);
        }

        // Build relative path
        $relativePath = '';

        // Add ../ for each remaining directory level in doc path
        if ($upwardLevels > 0) {
            $relativePath = str_repeat('../', $upwardLevels);
        }

        // Add the source path
        $relativePath .= implode('/', $sourcePartsRelative);

        return $relativePath;
    }

    /**
     * Normalize a file path by resolving it to absolute and converting to forward slashes.
     *
     * @param  string  $path  The path to normalize
     * @return string The normalized path
     */
    private function normalizePath(string $path): string
    {
        // Try realpath first, but if it fails (e.g., path doesn't exist yet), normalize manually
        $normalized = realpath($path);

        if ($normalized === false) {
            // Path doesn't exist, manually normalize
            $normalized = $path;

            // Convert backslashes to forward slashes
            $normalized = str_replace('\\', '/', $normalized);

            // Remove redundant slashes
            $normalized = preg_replace('#/+#', '/', $normalized);

            // Resolve . and .. segments
            $parts = explode('/', $normalized);
            $resolved = [];

            foreach ($parts as $part) {
                if ($part === '.' || $part === '') {
                    // Skip current directory references and empty parts (except for leading slash)
                    if ($part === '' && count($resolved) === 0) {
                        // Keep the leading empty string for absolute paths
                        $resolved[] = $part;
                    }

                    continue;
                } elseif ($part === '..') {
                    // Go up one directory
                    if (count($resolved) > 1 || (count($resolved) === 1 && $resolved[0] !== '')) {
                        array_pop($resolved);
                    }
                } else {
                    $resolved[] = $part;
                }
            }

            $normalized = implode('/', $resolved);

            // On macOS, /var is a symlink to /private/var
            // Normalize /var to /private/var for consistent path comparison
            if (str_starts_with($normalized, '/var/')) {
                $normalized = '/private'.$normalized;
            }
        } else {
            // Convert to forward slashes for consistency
            $normalized = str_replace('\\', '/', $normalized);
        }

        return $normalized;
    }
}
