<?php

namespace App\Sample;

/**
 * A sample interface for testing.
 */
interface SampleInterface
{
    /**
     * Get the name.
     */
    public function getName(): string;

    /**
     * Set the name.
     *
     * @param  string  $name  The name to set
     */
    public function setName(string $name): void;
}
