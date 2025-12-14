<?php

namespace Ajility\LaravelAutodoc\Tests\Helpers;

use Ajility\LaravelAutodoc\Services\ClassParser;
use Ajility\LaravelAutodoc\Services\DirectoryScanner;
use Ajility\LaravelAutodoc\Services\DocumentationGenerator;
use Ajility\LaravelAutodoc\Services\Markdown\ClassMarkdownGenerator;
use Ajility\LaravelAutodoc\Services\Markdown\IndexMarkdownGenerator;
use Ajility\LaravelAutodoc\Services\PathResolver;
use Ajility\LaravelAutodoc\Utils\PathValidator;

trait CreatesDocumentationGenerator
{
    protected function createDocumentationGenerator(): DocumentationGenerator
    {
        $scanner = new DirectoryScanner;
        $parser = app(ClassParser::class);
        $pathResolver = app(PathResolver::class);
        $classMarkdownGenerator = app(ClassMarkdownGenerator::class);
        $indexMarkdownGenerator = app(IndexMarkdownGenerator::class);
        $pathValidator = new PathValidator;

        return new DocumentationGenerator(
            $scanner,
            $parser,
            $classMarkdownGenerator,
            $indexMarkdownGenerator,
            $pathResolver,
            $pathValidator
        );
    }
}
