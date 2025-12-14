<?php

namespace Ajility\LaravelAutodoc\Services\AST;

class NamespaceResolver
{
    private ?string $currentNamespace = null;

    private array $useStatements = [];

    /**
     * Set the current namespace.
     */
    public function setNamespace(?string $namespace): void
    {
        $this->currentNamespace = $namespace;
    }

    /**
     * Add a use statement.
     *
     * @param  string  $fqn  Fully qualified name
     */
    public function addUseStatement(string $alias, string $fqn): void
    {
        $this->useStatements[$alias] = $fqn;
    }

    /**
     * Resolve a class name to its fully qualified name.
     */
    public function resolve(string $className): string
    {
        // If it's already fully qualified (starts with backslash), return without the leading backslash
        if (str_starts_with($className, '\\')) {
            return ltrim($className, '\\');
        }

        // Check if this class is in our use statements
        if (isset($this->useStatements[$className])) {
            return $this->useStatements[$className];
        }

        // If it contains a backslash, it's already fully qualified
        if (str_contains($className, '\\')) {
            return $className;
        }

        // If we have a current namespace and it's a simple name, assume it's in the same namespace
        if ($this->currentNamespace) {
            return $this->currentNamespace.'\\'.$className;
        }

        // Return as-is
        return $className;
    }

    /**
     * Get the current namespace.
     */
    public function getCurrentNamespace(): ?string
    {
        return $this->currentNamespace;
    }

    /**
     * Reset the namespace and use statements.
     */
    public function reset(): void
    {
        $this->currentNamespace = null;
        $this->useStatements = [];
    }
}
