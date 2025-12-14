<?php

namespace Ajility\LaravelAutodoc\Services\Markdown;

class IndexMarkdownGenerator
{
    /**
     * Generate an index markdown file.
     *
     * @param  string  $title  The documentation title
     */
    public function generate(string $title): string
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
}
