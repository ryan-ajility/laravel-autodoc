<?php

namespace App\Sample;

/**
 * A sample class for testing documentation generation.
 *
 * This class demonstrates various PHP features that should be documented.
 */
class SampleClass
{
    /**
     * A public property with a default value.
     */
    public string $publicProperty = 'default';

    /**
     * A protected property.
     */
    protected int $protectedProperty = 42;

    /**
     * A private property.
     */
    private array $privateProperty = [];

    /**
     * A public method that does something.
     *
     * @param  string  $name  The name parameter
     * @param  int  $age  The age parameter
     * @return string The formatted result
     */
    public function publicMethod(string $name, int $age = 18): string
    {
        return "{$name} is {$age} years old";
    }

    /**
     * A protected method.
     *
     * @param  array  $data  The data to process
     * @return bool Success status
     */
    protected function protectedMethod(array $data): bool
    {
        return ! empty($data);
    }

    /**
     * A private method.
     */
    private function privateMethod(): void
    {
        // Do nothing
    }

    /**
     * A static method.
     *
     * @param  string  $value  The value to process
     * @return string The processed value
     */
    public static function staticMethod(string $value): string
    {
        return strtoupper($value);
    }
}
