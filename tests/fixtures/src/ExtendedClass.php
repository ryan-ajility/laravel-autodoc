<?php

namespace App\Sample;

/**
 * An extended class that implements an interface.
 */
class ExtendedClass extends SampleClass implements SampleInterface
{
    use SampleTrait;

    /**
     * Implementation of getName from interface.
     */
    public function getName(): string
    {
        return 'ExtendedClass';
    }

    /**
     * Implementation of setName from interface.
     *
     * @param  string  $name  The name to set
     */
    public function setName(string $name): void
    {
        $this->publicProperty = $name;
    }
}
