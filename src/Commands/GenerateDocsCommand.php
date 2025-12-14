<?php

namespace Ajility\LaravelAutodoc\Commands;

use Ajility\LaravelAutodoc\Services\ClassLinkBuilder;
use Ajility\LaravelAutodoc\Services\ClassParser;
use Ajility\LaravelAutodoc\Services\DirectoryScanner;
use Ajility\LaravelAutodoc\Services\DocblockProcessor;
use Ajility\LaravelAutodoc\Services\DocumentationGenerator;
use Ajility\LaravelAutodoc\Services\Markdown\ClassMarkdownGenerator;
use Ajility\LaravelAutodoc\Services\Markdown\IndexMarkdownGenerator;
use Ajility\LaravelAutodoc\Services\PathResolver;
use Ajility\LaravelAutodoc\Utils\ConfigurationValidator;
use Illuminate\Console\Command;

class GenerateDocsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'autodoc:generate
                            {--path= : The output path for documentation}
                            {--source=* : Additional source directories to scan}
                            {--exclude=* : Additional directories to exclude}
                            {--protected : Include protected methods}
                            {--private : Include private methods}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate markdown documentation for your Laravel project';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting documentation generation...');

        try {
            // Get configuration
            $config = $this->getConfiguration();
        } catch (\InvalidArgumentException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        // Display configuration
        $this->displayConfiguration($config);

        // Create generator using dependency injection
        $scanner = new DirectoryScanner;
        $parser = app(ClassParser::class);
        $pathResolver = new PathResolver;
        $docblockProcessor = new DocblockProcessor;
        $linkBuilder = new ClassLinkBuilder($pathResolver);
        $classMarkdownGenerator = new ClassMarkdownGenerator($docblockProcessor, $linkBuilder, $pathResolver);
        $indexMarkdownGenerator = new IndexMarkdownGenerator;
        $pathValidator = new \Ajility\LaravelAutodoc\Utils\PathValidator;
        $generator = new DocumentationGenerator(
            $scanner,
            $parser,
            $classMarkdownGenerator,
            $indexMarkdownGenerator,
            $pathResolver,
            $pathValidator
        );

        // Generate documentation
        $this->info('Scanning files...');

        $stats = $generator->generate($config);

        // Display results
        $this->newLine();
        $this->info('Documentation generation completed!');
        $this->newLine();

        $this->table(
            ['Metric', 'Count'],
            [
                ['Files scanned', $stats['files_scanned']],
                ['Classes documented', $stats['classes_documented']],
                ['Methods documented', $stats['methods_documented']],
                ['Properties documented', $stats['properties_documented']],
            ]
        );

        $this->newLine();
        $this->info("Documentation saved to: {$config['output_path']}");
        $this->info("Index file: {$config['output_path']}/README.md");

        return self::SUCCESS;
    }

    /**
     * Get the configuration for documentation generation.
     */
    private function getConfiguration(): array
    {
        $config = config('autodoc');

        // Validate and sanitize command options
        $path = $this->option('path');
        if ($path !== null) {
            if (! is_string($path) || trim($path) === '') {
                throw new \InvalidArgumentException('--path must be a non-empty string');
            }
            $config['output_path'] = trim($path);
        }

        if ($source = $this->option('source')) {
            if (! is_array($source)) {
                throw new \InvalidArgumentException('--source must be an array');
            }
            $sources = array_map('trim', $source);
            $sources = array_filter($sources, fn ($s) => $s !== '');
            if (empty($sources)) {
                throw new \InvalidArgumentException('--source must contain at least one directory');
            }
            $config['source_directories'] = array_merge(
                $config['source_directories'],
                $sources
            );
        }

        if ($exclude = $this->option('exclude')) {
            if (! is_array($exclude)) {
                throw new \InvalidArgumentException('--exclude must be an array');
            }
            $excludes = array_map('trim', $exclude);
            $excludes = array_filter($excludes, fn ($e) => $e !== '');
            $config['excluded_directories'] = array_merge(
                $config['excluded_directories'],
                $excludes
            );
        }

        if ($this->option('protected')) {
            $config['include_protected_methods'] = true;
        }

        if ($this->option('private')) {
            $config['include_private_methods'] = true;
        }

        // Validate the final merged configuration
        $validator = new ConfigurationValidator;
        $errors = $validator->validate($config);
        if (! empty($errors)) {
            throw new \InvalidArgumentException(
                "Invalid configuration:\n".implode("\n", $errors)
            );
        }

        return $config;
    }

    /**
     * Display the current configuration.
     */
    private function displayConfiguration(array $config): void
    {
        $this->newLine();
        $this->line('Configuration:');
        $this->line('  Output Path: '.$config['output_path']);
        $this->line('  Source Directories: '.implode(', ', $config['source_directories']));
        $this->line('  Include Protected Methods: '.($config['include_protected_methods'] ? 'Yes' : 'No'));
        $this->line('  Include Private Methods: '.($config['include_private_methods'] ? 'Yes' : 'No'));
        $this->newLine();
    }
}
