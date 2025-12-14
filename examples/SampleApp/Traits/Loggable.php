<?php

namespace App\Traits;

/**
 * Loggable trait.
 *
 * Provides logging functionality for classes that need to track
 * operations and events.
 */
trait Loggable
{
    /**
     * Log messages.
     */
    protected array $logs = [];

    /**
     * Add a log entry.
     *
     * Records a log message with a timestamp and optional level.
     *
     * @param  string  $message  The log message
     * @param  string  $level  The log level (info, warning, error, etc.)
     */
    public function log(string $message, string $level = 'info'): void
    {
        $this->logs[] = [
            'message' => $message,
            'level' => $level,
            'timestamp' => date('Y-m-d H:i:s'),
        ];
    }

    /**
     * Get all log entries.
     *
     * @return array All logged messages
     */
    public function getLogs(): array
    {
        return $this->logs;
    }

    /**
     * Clear all log entries.
     */
    public function clearLogs(): void
    {
        $this->logs = [];
    }
}
