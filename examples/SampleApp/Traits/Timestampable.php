<?php

namespace App\Traits;

/**
 * Timestampable trait.
 *
 * Provides automatic timestamp management for models and entities.
 * Automatically tracks creation and modification times.
 */
trait Timestampable
{
    /**
     * The created at timestamp.
     */
    protected ?string $createdAt = null;

    /**
     * The updated at timestamp.
     */
    protected ?string $updatedAt = null;

    /**
     * Touch the model's timestamps.
     *
     * Updates the updated_at timestamp to the current time.
     */
    public function touch(): void
    {
        $this->updatedAt = date('Y-m-d H:i:s');
    }

    /**
     * Get the created at timestamp.
     *
     * @return string|null The creation timestamp
     */
    public function getCreatedAt(): ?string
    {
        return $this->createdAt;
    }

    /**
     * Get the updated at timestamp.
     *
     * @return string|null The last update timestamp
     */
    public function getUpdatedAt(): ?string
    {
        return $this->updatedAt;
    }
}
