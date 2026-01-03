<?php

namespace Ajility\LaravelAutodoc\Services;

use Ajility\LaravelAutodoc\Services\Markdown\ClassMarkdownGenerator;
use Ajility\LaravelAutodoc\Services\Markdown\IndexMarkdownGenerator;
use Ajility\LaravelAutodoc\Utils\PathValidator;

class DocumentationGenerator
{
    private const DEFAULT_DIRECTORY_PERMISSIONS = 0755;

    public function __construct(
        private DirectoryScanner $scanner,
        private ClassParser $parser,
        private ClassMarkdownGenerator $classMarkdownGenerator,
        private IndexMarkdownGenerator $indexMarkdownGenerator,
        private PathResolver $pathResolver,
        private PathValidator $pathValidator
    ) {}

    /**
     * Generate documentation for the project.
     *
     * @param  array  $config  Configuration array
     * @return array Statistics about generated documentation
     */
    public function generate(array $config): array
    {
        // Validate configuration structure
        $requiredKeys = ['output_path', 'source_directories'];
        foreach ($requiredKeys as $key) {
            if (! isset($config[$key])) {
                throw new \InvalidArgumentException("Missing required configuration: {$key}");
            }
        }

        if (! is_string($config['output_path']) || trim($config['output_path']) === '') {
            throw new \InvalidArgumentException('output_path must be a non-empty string');
        }

        if (! is_array($config['source_directories']) || empty($config['source_directories'])) {
            throw new \InvalidArgumentException('source_directories must be a non-empty array');
        }

        // Trim the output path and convert to absolute path for consistent path calculations
        $outputPath = trim($config['output_path']);
        if (! $this->isAbsolutePath($outputPath)) {
            $projectRoot = $this->getProjectRoot();
            $outputPath = $projectRoot.DIRECTORY_SEPARATOR.$outputPath;
        }
        $sourceDirectories = $config['source_directories'];
        $excludedDirectories = $config['excluded_directories'];
        $fileExtensions = $config['file_extensions'];
        $includePrivateMethods = $config['include_private_methods'];
        $title = $config['title'];

        // Validate paths to prevent directory traversal attacks (unless explicitly disabled for testing)
        if (! isset($config['_skip_path_validation']) || $config['_skip_path_validation'] !== true) {
            // Use the actual package root (not testbench's base_path in testing)
            $projectRoot = $this->getProjectRoot();

            // Validate output path
            $this->pathValidator->validateOutputPath($outputPath, $projectRoot);

            // Validate all source directories
            foreach ($sourceDirectories as $sourceDir) {
                $this->pathValidator->validateSourceDirectory($sourceDir, $projectRoot);
            }
        }

        // Clean and create output directory
        if (file_exists($outputPath)) {
            $this->deleteDirectory($outputPath);
        }

        if (! @mkdir($outputPath, self::DEFAULT_DIRECTORY_PERMISSIONS, true) && ! is_dir($outputPath)) {
            throw new \RuntimeException("Failed to create directory: {$outputPath}");
        }

        // Scan for PHP files
        $files = $this->scanner->scan($sourceDirectories, $excludedDirectories, $fileExtensions);

        $stats = [
            'files_scanned' => count($files),
            'classes_documented' => 0,
            'methods_documented' => 0,
            'properties_documented' => 0,
        ];

        $classMap = []; // Map of fully qualified class names to their markdown file paths

        // First pass: build the class map
        foreach ($files as $file) {
            $classes = $this->parser->parseFile($file, $includePrivateMethods);

            if (empty($classes)) {
                continue;
            }

            foreach ($classes as $class) {
                $relativePath = $this->findRelativePath($file->getPathname(), $sourceDirectories);
                $mdPath = str_replace('.php', '.md', $relativePath);

                // Build fully qualified class name
                $namespace = $class['namespace'] ? $class['namespace'].'\\' : '';
                $fullClassName = $namespace.$class['name'];

                // Store in class map
                $classMap[$fullClassName] = $mdPath;
            }
        }

        // Second pass: generate documentation with class map
        foreach ($files as $file) {
            $classes = $this->parser->parseFile($file, $includePrivateMethods);

            if (empty($classes)) {
                continue;
            }

            foreach ($classes as $class) {
                // Get relative path for the file by finding which source directory it belongs to
                $relativePath = $this->findRelativePath($file->getPathname(), $sourceDirectories);

                // Generate markdown with class map for clickable links
                // Pass the absolute source path and output path for source file linking
                $markdown = $this->classMarkdownGenerator->generate(
                    $class,
                    $relativePath,
                    $file->getPathname(),
                    $outputPath,
                    $classMap
                );

                // Create directory structure in output
                $outputFilePath = $outputPath.DIRECTORY_SEPARATOR.str_replace('.php', '.md', $relativePath);
                $outputDir = dirname($outputFilePath);

                if (! is_dir($outputDir)) {
                    if (! @mkdir($outputDir, self::DEFAULT_DIRECTORY_PERMISSIONS, true) && ! is_dir($outputDir)) {
                        throw new \RuntimeException("Failed to create directory: {$outputDir}");
                    }
                }

                // Write markdown file
                $result = @file_put_contents($outputFilePath, $markdown);
                if ($result === false) {
                    throw new \RuntimeException("Failed to write file: {$outputFilePath}. Check permissions and disk space.");
                }

                // Update stats
                $stats['classes_documented']++;
                $stats['methods_documented'] += count($class['methods']);
                $stats['properties_documented'] += count($class['properties']);
            }
        }

        // Generate index file
        $indexMarkdown = $this->indexMarkdownGenerator->generate($title);
        $indexFilePath = $outputPath.DIRECTORY_SEPARATOR.'README.md';
        $result = @file_put_contents($indexFilePath, $indexMarkdown);
        if ($result === false) {
            throw new \RuntimeException("Failed to write file: {$indexFilePath}. Check permissions and disk space.");
        }

        return $stats;
    }

    /**
     * Find the relative path from the source directory that contains the file.
     *
     * @param  string  $filePath  The full file path
     * @param  array  $sourceDirectories  Array of source directories
     * @return string The relative path from the matching source directory
     */
    private function findRelativePath(string $filePath, array $sourceDirectories): string
    {
        // Convert to absolute path for comparison
        $absoluteFilePath = realpath($filePath);

        foreach ($sourceDirectories as $sourceDir) {
            $absoluteSourceDir = realpath($sourceDir);

            if ($absoluteSourceDir === false) {
                continue;
            }

            // Check if file is under this source directory
            $relativePath = $this->scanner->getRelativePath($absoluteFilePath, $absoluteSourceDir);

            // If we got a relative path (not the same as input), this is the correct source dir
            if ($relativePath !== $absoluteFilePath) {
                return $relativePath;
            }
        }

        // Fallback to using the first source directory
        return $this->scanner->getRelativePath($filePath, $sourceDirectories[0]);
    }

    /**
     * Get the project root directory.
     *
     * When installed as a dependency, finds the consumer project by
     * locating the vendor directory in the path.
     * In a regular Laravel app, this uses base_path().
     * For package development with testbench, uses the package root directory.
     *
     * @return string The project root directory
     */
    private function getProjectRoot(): string
    {
        $currentDir = __DIR__;  // This is vendor/ajility/laravel-autodoc/src/Services when installed as dependency

        // Check if we're installed as a dependency (works on both Unix and Windows)
        if (preg_match('#^(.+)[/\\\\]vendor[/\\\\]ajility[/\\\\]laravel-autodoc[/\\\\]#', $currentDir, $matches)) {
            return $matches[1];
        }

        // Regular Laravel context
        if (function_exists('base_path')) {
            try {
                $basePath = base_path();

                // If base_path points to testbench, we're in package development mode
                // Use the package root (go up from src/Services)
                if (str_contains($basePath, 'testbench-core/laravel')) {
                    return dirname(__DIR__, 2);
                }

                return $basePath;
            } catch (\Throwable $e) {
                // Fall through
            }
        }

        // Standalone development - go up from src/Services to package root
        return dirname(__DIR__, 2);
    }

    /**
     * Check if a path is absolute.
     *
     * @param  string  $path  The path to check
     * @return bool True if the path is absolute
     */
    private function isAbsolutePath(string $path): bool
    {
        // Unix absolute paths start with /
        if (str_starts_with($path, '/')) {
            return true;
        }

        // Windows absolute paths start with drive letter (e.g., C:\)
        if (preg_match('/^[a-zA-Z]:[\\\\\/]/', $path)) {
            return true;
        }

        return false;
    }

    /**
     * Recursively delete a directory.
     *
     * @param  string  $dir  The directory to delete
     */
    private function deleteDirectory(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        $items = @scandir($dir);
        if ($items === false) {
            throw new \RuntimeException("Failed to scan directory: {$dir}");
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $dir.DIRECTORY_SEPARATOR.$item;
            if (is_dir($path)) {
                $this->deleteDirectory($path);
            } else {
                unlink($path);
            }
        }

        rmdir($dir);
    }
}
