<?php

/*
|--------------------------------------------------------------------------
| Configuration File Override
|--------------------------------------------------------------------------
|
| You can place an autodoc.json file in your project root to override
| these default settings. The JSON file should contain any of these keys:
| - output_path (string)
| - source_directories (array)
| - excluded_directories (array)
| - file_extensions (array)
| - include_protected_methods (boolean)
| - include_private_methods (boolean)
| - title (string)
|
*/

// Determine the correct project root for finding autodoc.json
// When installed as a dependency, find the consumer project by locating vendor directory
$configDir = __DIR__;  // This is vendor/ajility/laravel-autodoc/config when installed as dependency
$isPackageContext = false;

// Check if we're installed as a dependency (in vendor folder)
if (preg_match('#^(.+)[/\\\\]vendor[/\\\\]ajility[/\\\\]laravel-autodoc[/\\\\]#', $configDir, $matches)) {
    // We're a dependency - use the consumer project root
    $projectRoot = $matches[1];
    $isPackageContext = true;
} elseif (function_exists('base_path')) {
    // Regular Laravel app context
    $projectRoot = base_path();
} else {
    // Standalone development - use current directory
    $projectRoot = getcwd();
    $isPackageContext = true;
}

$configFile = $projectRoot.'/autodoc.json';
$jsonConfig = [];

if (file_exists($configFile)) {
    $jsonContent = file_get_contents($configFile);
    if ($jsonContent === false) {
        throw new \RuntimeException("Failed to read configuration file: {$configFile}");
    }

    $jsonConfig = json_decode($jsonContent, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new \RuntimeException(
            "Invalid JSON in {$configFile}: ".json_last_error_msg()
        );
    }

    // Validate the JSON configuration
    $validator = new \Ajility\LaravelAutodoc\Utils\ConfigurationValidator;
    $errors = $validator->validate($jsonConfig);
    if (! empty($errors)) {
        throw new \InvalidArgumentException(
            "Invalid configuration in {$configFile}:\n".implode("\n", $errors)
        );
    }
}

// When used as a dependency, require autodoc.json for source_directories
if ($isPackageContext && empty($jsonConfig['source_directories'])) {
    throw new \RuntimeException(
        "When using laravel-autodoc as a dependency, you must create an autodoc.json file\n".
        "in your project root ({$projectRoot}) with at least 'source_directories' configured.\n\n".
        "Example autodoc.json:\n".
        "{\n".
        '  "source_directories": ["src"]'."\n".
        '}'
    );
}

return [
    /*
    |--------------------------------------------------------------------------
    | Documentation Output Path
    |--------------------------------------------------------------------------
    |
    | The directory where the generated documentation will be saved.
    | Can be overridden with --path option when running the command.
    |
    */
    'output_path' => $jsonConfig['output_path'] ?? ($projectRoot.'/storage/app/docs'),

    /*
    |--------------------------------------------------------------------------
    | Source Directories
    |--------------------------------------------------------------------------
    |
    | The directories to scan for PHP files. By default, it scans the 'app'
    | directory for Laravel apps. When using this package as a dependency,
    | you must configure source_directories in your autodoc.json file.
    |
    */
    'source_directories' => $jsonConfig['source_directories'] ?? [
        function_exists('app_path') ? app_path() : $projectRoot.'/app',
    ],

    /*
    |--------------------------------------------------------------------------
    | Excluded Directories
    |--------------------------------------------------------------------------
    |
    | Directories to exclude from documentation generation.
    |
    */
    'excluded_directories' => $jsonConfig['excluded_directories'] ?? [
        'vendor',
        'node_modules',
        'storage',
        'bootstrap/cache',
        'public',
    ],

    /*
    |--------------------------------------------------------------------------
    | File Extensions
    |--------------------------------------------------------------------------
    |
    | The file extensions to include when scanning for classes.
    |
    */
    'file_extensions' => $jsonConfig['file_extensions'] ?? ['php'],

    /*
    |--------------------------------------------------------------------------
    | Include Protected Methods
    |--------------------------------------------------------------------------
    |
    | Whether to include protected methods in the documentation.
    | Protected methods are often part of the intended API for subclasses.
    |
    */
    'include_protected_methods' => $jsonConfig['include_protected_methods'] ?? false,

    /*
    |--------------------------------------------------------------------------
    | Include Private Methods
    |--------------------------------------------------------------------------
    |
    | Whether to include private methods in the documentation.
    | Private methods are internal implementation details.
    |
    */
    'include_private_methods' => $jsonConfig['include_private_methods'] ?? false,

    /*
    |--------------------------------------------------------------------------
    | Documentation Title
    |--------------------------------------------------------------------------
    |
    | The title to use for the generated documentation.
    |
    */
    'title' => $jsonConfig['title'] ?? 'Laravel Documentation',
];
