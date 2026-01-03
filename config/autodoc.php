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
// When running via testbench, base_path() points to testbench's Laravel stub
// In that case, use getcwd() which points to the actual consumer project
$basePath = base_path();
if (str_contains($basePath, 'testbench-core/laravel')) {
    $projectRoot = getcwd();
} else {
    $projectRoot = $basePath;
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
    | directory for Laravel apps, or 'src' for packages running via testbench.
    |
    */
    'source_directories' => $jsonConfig['source_directories'] ?? [
        str_contains($basePath, 'testbench-core/laravel') ? $projectRoot.'/src' : app_path(),
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
