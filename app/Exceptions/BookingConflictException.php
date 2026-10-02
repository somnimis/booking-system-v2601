<?php

namespace App\Exceptions;

use Exception;

/**
 * Thrown when a requisition submission hits a scheduling conflict.
 *
 * Carries the conflict items so the controller can return them in the
 * response payload and the frontend can render the conflict modal
 * (same shape as /requisition/check-availability returns).
 */
class BookingConflictException extends Exception
{
    protected array $conflictItems;

    public function __construct(array $conflictItems, string $message = 'Time slot conflicts with existing booking(s).')
    {
        parent::__construct($message);
        $this->conflictItems = $conflictItems;
    }

    public function getConflictItems(): array
    {
        return $this->conflictItems;
    }
}