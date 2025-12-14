<?php

namespace Ajility\LaravelAutodoc\Utils;

class ConfigurationValidator
{
    private const DEFAULT_MAX_FILES = 10000;

    private const DEFAULT_MAX_FILE_SIZE = 1048576; // 1MB in bytes

    private const DEFAULT_MAX_EXECUTION_TIME = 300; // 5 minutes

    /**
     * Validate the configuration array.
     *
     * @param  array  $config  The configuration to validate
     * @return array An array of error messages (empty if valid)
     */
    public function validate(array $config): array
    {
        $errors = [];

        // Validate required fields
        $errors = array_merge($errors, $this->validateOutputPath($config));
        $errors = array_merge($errors, $this->validateSourceDirectories($config));

        // Validate optional fields if present
        if (isset($config['excluded_directories'])) {
            $errors = array_merge($errors, $this->validateExcludedDirectories($config['excluded_directories']));
        }

        if (isset($config['file_extensions'])) {
            $errors = array_merge($errors, $this->validateFileExtensions($config['file_extensions']));
        }

        if (isset($config['include_protected_methods'])) {
            $errors = array_merge($errors, $this->validateBooleanField('include_protected_methods', $config['include_protected_methods']));
        }

        if (isset($config['include_private_methods'])) {
            $errors = array_merge($errors, $this->validateBooleanField('include_private_methods', $config['include_private_methods']));
        }

        if (isset($config['title'])) {
            $errors = array_merge($errors, $this->validateTitle($config['title']));
        }

        return $errors;
    }

    /**
     * Validate the output_path field.
     */
    private function validateOutputPath(array $config): array
    {
        $errors = [];

        if (! isset($config['output_path'])) {
            $errors[] = 'output_path is required';

            return $errors;
        }

        if (! is_string($config['output_path'])) {
            $errors[] = 'output_path must be a string';

            return $errors;
        }

        if (trim($config['output_path']) === '') {
            $errors[] = 'output_path must be a non-empty string';
        }

        return $errors;
    }

    /**
     * Validate the source_directories field.
     */
    private function validateSourceDirectories(array $config): array
    {
        $errors = [];

        if (! isset($config['source_directories'])) {
            $errors[] = 'source_directories is required';

            return $errors;
        }

        if (! is_array($config['source_directories'])) {
            $errors[] = 'source_directories must be an array';

            return $errors;
        }

        if (empty($config['source_directories'])) {
            $errors[] = 'source_directories must contain at least one directory';

            return $errors;
        }

        foreach ($config['source_directories'] as $index => $directory) {
            if (! is_string($directory)) {
                $errors[] = "source_directories element at index {$index} must be a string";
            } elseif (trim($directory) === '') {
                $errors[] = "source_directories element at index {$index} must be a non-empty string";
            }
        }

        return $errors;
    }

    /**
     * Validate the excluded_directories field.
     *
     * @param  mixed  $excludedDirectories
     */
    private function validateExcludedDirectories($excludedDirectories): array
    {
        $errors = [];

        if (! is_array($excludedDirectories)) {
            $errors[] = 'excluded_directories must be an array';

            return $errors;
        }

        foreach ($excludedDirectories as $index => $directory) {
            if (! is_string($directory)) {
                $errors[] = "excluded_directories element at index {$index} must be a string";
            }
        }

        return $errors;
    }

    /**
     * Validate the file_extensions field.
     *
     * @param  mixed  $fileExtensions
     */
    private function validateFileExtensions($fileExtensions): array
    {
        $errors = [];

        if (! is_array($fileExtensions)) {
            $errors[] = 'file_extensions must be an array';

            return $errors;
        }

        foreach ($fileExtensions as $index => $extension) {
            if (! is_string($extension)) {
                $errors[] = "file_extensions element at index {$index} must be a string";
            }
        }

        return $errors;
    }

    /**
     * Validate a boolean field.
     *
     * @param  mixed  $value
     */
    private function validateBooleanField(string $fieldName, $value): array
    {
        $errors = [];

        if (! is_bool($value)) {
            $errors[] = "{$fieldName} must be a boolean";
        }

        return $errors;
    }

    /**
     * Validate the title field.
     *
     * @param  mixed  $title
     */
    private function validateTitle($title): array
    {
        $errors = [];

        if (! is_string($title)) {
            $errors[] = 'title must be a string';
        }

        return $errors;
    }
}
