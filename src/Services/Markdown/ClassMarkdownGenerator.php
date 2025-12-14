<?php

namespace Ajility\LaravelAutodoc\Services\Markdown;

use Ajility\LaravelAutodoc\Services\ClassLinkBuilder;
use Ajility\LaravelAutodoc\Services\DocblockProcessor;
use Ajility\LaravelAutodoc\Services\PathResolver;

class ClassMarkdownGenerator
{
    public function __construct(
        private DocblockProcessor $docblockProcessor,
        private ClassLinkBuilder $linkBuilder,
        private PathResolver $pathResolver
    ) {}

    /**
     * Generate markdown documentation for a class.
     *
     * @param  array  $class  The parsed class information
     * @param  string  $relativeFilePath  The relative path of the documentation file
     * @param  string  $absoluteSourcePath  The absolute path to the source file
     * @param  string  $outputPath  The absolute path to the documentation output directory
     * @param  array  $classMap  Map of fully qualified class names to their markdown file paths
     * @return string The generated markdown
     */
    public function generate(
        array $class,
        string $relativeFilePath,
        string $absoluteSourcePath,
        string $outputPath,
        array $classMap
    ): string {
        $markdown = '';

        // Add title (just the class name, no "class:" prefix)
        $markdown .= "# {$class['name']}\n\n";

        // Add source file path if available
        if ($absoluteSourcePath && $outputPath) {
            $relativeSourcePath = $this->pathResolver->calculateRelativePathToSource(
                $relativeFilePath,
                $absoluteSourcePath,
                $outputPath
            );
            $markdown .= "**Source:** [{$relativeFilePath}]({$relativeSourcePath})\n\n";
        }

        // Add inheritance and trait information
        if ($class['extends'] || ! empty($class['implements']) || ! empty($class['traits'])) {
            $inheritanceInfo = [];

            if ($class['extends']) {
                $extendsLink = $this->linkBuilder->createLink($class['extends'], $classMap, $relativeFilePath);
                $inheritanceInfo[] = "**Extends:** {$extendsLink}";
            }

            if (! empty($class['implements'])) {
                $interfaces = array_map(
                    fn ($interface) => $this->linkBuilder->createLink($interface, $classMap, $relativeFilePath),
                    $class['implements']
                );
                $inheritanceInfo[] = '**Implements:** '.implode(', ', $interfaces);
            }

            if (! empty($class['traits'])) {
                $traits = array_map(
                    fn ($trait) => $this->linkBuilder->createLink($trait, $classMap, $relativeFilePath),
                    $class['traits']
                );
                $inheritanceInfo[] = '**Uses Traits:** '.implode(', ', $traits);
            }

            $markdown .= implode("  \n", $inheritanceInfo)."\n\n";
        }

        // Add class docblock
        if ($class['docblock']) {
            $markdown .= "## Description\n\n";
            $markdown .= $this->docblockProcessor->format($class['docblock'])."\n\n";
        }

        // Add properties
        if (! empty($class['properties'])) {
            $markdown .= "## Properties\n\n";
            $markdown .= $this->generatePropertiesTable($class['properties']);
            $markdown .= "\n";
        }

        // Add methods
        if (! empty($class['methods'])) {
            $markdown .= "## Methods\n\n";

            foreach ($class['methods'] as $method) {
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

        foreach ($properties as $property) {
            $name = '`$'.$property['name'].'`';
            $visibility = '`'.$property['visibility'].'`';
            $type = '`'.($property['type'] ?? 'mixed').'`';
            $default = $property['default'] !== null ? '`'.$property['default'].'`' : '`-`';
            $description = $property['docblock'] ? $this->docblockProcessor->format($property['docblock']) : '';

            $markdown .= "| {$name} | {$visibility} | {$type} | {$default} | {$description} |\n";
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
            $markdown .= $this->docblockProcessor->format($method['docblock'])."\n\n";
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

                $description = $this->docblockProcessor->extractParamDescription($method['docblock'], $param['name']);

                $markdown .= "| `{$name}` | `{$type}` | `{$default}` | {$description} |\n";
            }

            $markdown .= "\n";
        }

        // Add return type
        if ($method['returnType']) {
            $returnDescription = $this->docblockProcessor->extractReturnDescription($method['docblock']);

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
}
