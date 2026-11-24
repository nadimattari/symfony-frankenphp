<?php

declare(strict_types=1);

namespace App\Service\Mail;

/** A run of this type is already queued or running. */
final class ActiveMailRunException extends \RuntimeException
{
    public function __construct(public readonly string $runType)
    {
        parent::__construct('A mail run is already in progress.');
    }
}
