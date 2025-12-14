<?php

namespace Ajility\LaravelAutodoc;

use Ajility\LaravelAutodoc\Commands\GenerateDocsCommand;
use Ajility\LaravelAutodoc\Utils\PathValidator;
use Illuminate\Support\ServiceProvider;

class AutodocServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/autodoc.php', 'autodoc'
        );

        // Register AST components as singletons
        $this->app->singleton(\Ajility\LaravelAutodoc\Services\AST\TypeStringConverter::class);
        $this->app->singleton(\Ajility\LaravelAutodoc\Services\AST\DocblockExtractor::class);
        $this->app->singleton(\Ajility\LaravelAutodoc\Services\AST\NamespaceResolver::class);

        $this->app->singleton(\Ajility\LaravelAutodoc\Services\AST\MethodInfoExtractor::class, function ($app) {
            return new \Ajility\LaravelAutodoc\Services\AST\MethodInfoExtractor(
                $app->make(\Ajility\LaravelAutodoc\Services\AST\TypeStringConverter::class),
                $app->make(\Ajility\LaravelAutodoc\Services\AST\DocblockExtractor::class)
            );
        });

        $this->app->singleton(\Ajility\LaravelAutodoc\Services\AST\PropertyInfoExtractor::class, function ($app) {
            return new \Ajility\LaravelAutodoc\Services\AST\PropertyInfoExtractor(
                $app->make(\Ajility\LaravelAutodoc\Services\AST\TypeStringConverter::class),
                $app->make(\Ajility\LaravelAutodoc\Services\AST\DocblockExtractor::class)
            );
        });

        $this->app->singleton(\Ajility\LaravelAutodoc\Services\ClassParser::class, function ($app) {
            return new \Ajility\LaravelAutodoc\Services\ClassParser(
                $app->make(\Ajility\LaravelAutodoc\Services\AST\MethodInfoExtractor::class),
                $app->make(\Ajility\LaravelAutodoc\Services\AST\PropertyInfoExtractor::class),
                $app->make(\Ajility\LaravelAutodoc\Services\AST\NamespaceResolver::class)
            );
        });

        // Register the PathValidator as a singleton
        $this->app->singleton(PathValidator::class, function () {
            return new PathValidator;
        });

        // Register Markdown generation components
        $this->app->singleton(\Ajility\LaravelAutodoc\Services\PathResolver::class);
        $this->app->singleton(\Ajility\LaravelAutodoc\Services\DocblockProcessor::class);

        $this->app->bind(\Ajility\LaravelAutodoc\Services\ClassLinkBuilder::class, function ($app) {
            return new \Ajility\LaravelAutodoc\Services\ClassLinkBuilder(
                $app->make(\Ajility\LaravelAutodoc\Services\PathResolver::class)
            );
        });

        $this->app->bind(\Ajility\LaravelAutodoc\Services\Markdown\ClassMarkdownGenerator::class, function ($app) {
            return new \Ajility\LaravelAutodoc\Services\Markdown\ClassMarkdownGenerator(
                $app->make(\Ajility\LaravelAutodoc\Services\DocblockProcessor::class),
                $app->make(\Ajility\LaravelAutodoc\Services\ClassLinkBuilder::class),
                $app->make(\Ajility\LaravelAutodoc\Services\PathResolver::class)
            );
        });

        $this->app->singleton(\Ajility\LaravelAutodoc\Services\Markdown\IndexMarkdownGenerator::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/autodoc.php' => config_path('autodoc.php'),
            ], 'autodoc-config');

            $this->commands([
                GenerateDocsCommand::class,
            ]);
        }
    }
}
