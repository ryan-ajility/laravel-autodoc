# Laravel Autodoc

A Laravel 12 package that automatically generates markdown documentation for your Laravel projects by parsing PHP docblocks and class structures.

## Features

- **Automatic Documentation Generation**: Scans your Laravel project and generates markdown files for each class
- **Directory Structure Preservation**: Mirrors your project's directory structure in the documentation
- **Docblock Parsing**: Extracts and formats PHPDoc comments into readable markdown
- **Class Information**: Documents classes, interfaces, and traits with their properties and methods
- **Method Details**: Includes parameters, return types, visibility, and more
- **Configurable**: Customize output paths, source directories, and what to include
- **Laravel 12 Compatible**: Built specifically for Laravel 12 with PHP 8.2+

## Requirements

- PHP 8.2 or higher
- Laravel 12.0 or higher

## Installation

Install the package via Composer:

```bash
composer require ajility/laravel-autodoc
```

The service provider will be automatically registered via Laravel's package discovery.

### Publish Configuration

Optionally, publish the configuration file:

```bash
php artisan vendor:publish --tag=autodoc-config
```

This will create a `config/autodoc.php` file where you can customize the package settings.

## Usage

### Basic Usage

Generate documentation for your entire project:

```bash
php artisan autodoc:generate
```

This will scan your `app` directory and generate markdown documentation in `storage/app/docs`.

### Command Options

The command supports several options for customization:

```bash
# Specify a custom output path
php artisan autodoc:generate --path=/path/to/docs

# Add additional source directories to scan
php artisan autodoc:generate --source=app/Custom --source=packages/MyPackage

# Exclude specific directories
php artisan autodoc:generate --exclude=app/Legacy --exclude=app/Deprecated

# Include private and protected methods
php artisan autodoc:generate --private
```

### Configuration

You can configure the package in two ways (in order of precedence):

#### 1. JSON Configuration File (Recommended)

Create an `autodoc.json` file in your project root:

```json
{
  "output_path": "storage/app/documentation",
  "source_directories": [
    "app",
    "packages/custom-package/src"
  ],
  "excluded_directories": [
    "vendor",
    "node_modules",
    "storage",
    "bootstrap/cache",
    "public",
    "tests"
  ],
  "file_extensions": ["php"],
  "include_protected_methods": false,
  "include_private_methods": false,
  "title": "My Application API Documentation"
}
```

This is ideal for per-project customization and can be committed to version control.

#### 2. Published Configuration File

Publish and edit the config file:

```bash
php artisan vendor:publish --tag=autodoc-config
```

This creates `config/autodoc.php` with all available options:

```php
return [
    // Output directory for generated documentation
    'output_path' => storage_path('app/docs'),

    // Directories to scan for PHP files
    'source_directories' => [
        app_path(),
    ],

    // Directories to exclude from scanning
    'excluded_directories' => [
        'vendor',
        'node_modules',
        'storage',
        'bootstrap/cache',
        'public',
    ],

    // File extensions to include
    'file_extensions' => ['php'],

    // Include protected methods in documentation
    'include_protected_methods' => false,

    // Include private methods in documentation
    'include_private_methods' => false,

    // Documentation title
    'title' => 'Laravel Documentation',
];
```

**Configuration Priority**: JSON file → Published config → Package defaults

## Documentation Output

The package generates:

1. **README.md**: Main documentation overview and guide
2. **Individual Class Files**: One markdown file per class, mirroring your directory structure

### Example Output Structure

```
storage/app/docs/
├── README.md
├── Http/
│   ├── Controllers/
│   │   ├── UserController.md
│   │   └── PostController.md
│   └── Middleware/
│       └── Authenticate.md
├── Models/
│   ├── User.md
│   └── Post.md
└── Services/
    └── PaymentService.md
```

### Example Class Documentation

Each class documentation file includes:

- Class/Interface/Trait name and type
- File path
- Namespace
- Extends and implements information
- Class description from docblock
- Properties with types, visibility, and descriptions
- Methods with:
  - Signature
  - Visibility and modifiers (static, abstract, final)
  - Parameters table with types and descriptions
  - Return type and description
  - Full docblock content

## Example

Given a class like this:

```php
<?php

namespace App\Services;

/**
 * Handles payment processing for the application.
 */
class PaymentService
{
    /**
     * Process a payment transaction.
     *
     * @param float $amount The amount to charge
     * @param string $currency The currency code (e.g., USD)
     * @param array $metadata Additional metadata for the transaction
     * @return array The transaction result
     */
    public function processPayment(float $amount, string $currency = 'USD', array $metadata = []): array
    {
        // Implementation
    }
}
```

The generated markdown will include all this information in a well-formatted document.

## Development

### Testing

The package uses Orchestra Testbench for testing:

```bash
composer test
```

### Package Structure

```
src/
├── Commands/
│   └── GenerateDocsCommand.php      # Artisan command
├── Services/
│   ├── DirectoryScanner.php         # Scans project directories
│   ├── ClassParser.php              # Parses PHP files and extracts class info
│   ├── MarkdownGenerator.php        # Generates markdown from parsed data
│   └── DocumentationGenerator.php   # Main orchestrator
└── AutodocServiceProvider.php       # Laravel service provider
```

## License

This package is open-sourced software licensed under the [MIT license](LICENSE).

## Credits

- Built with [nikic/php-parser](https://github.com/nikic/PHP-Parser) for robust PHP parsing
- Uses [Orchestra Testbench](https://github.com/orchestral/testbench) for package development

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## Support

If you discover any issues, please open an issue on the GitHub repository.
