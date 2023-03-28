<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Helper;

/**
 * Holds exit code constants for commands.
 */
final class ExitCode
{
    /**
     * The command completed successfully.
     */
    public const SUCCESS = 0;

    /**
     * The command exited with some kind of error.
     */
    public const FAILURE = 1;

    /**
     * Indicating something that is not really an error. This means that a mailer
     * (e.g.) could not create a connection, and the request should be reattempted later.
     */
    public const TEMPORARY_FAILURE = 75;
}
