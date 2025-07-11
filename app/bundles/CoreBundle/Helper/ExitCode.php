<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Helper;

use Symfony\Component\Console\Command\Command;

/**
 * Holds exit code constants for commands.
 */
final class ExitCode
{
    /**
     * The command completed successfully.
     */
    public const SUCCESS = Command::SUCCESS;

    /**
     * The command exited with some kind of error.
     */
    public const FAILURE = Command::FAILURE;

    /**
     * Indicating something that is not really an error. This means that a mailer
     * (e.g.) could not create a connection, and the request should be reattempted later.
     */
    public const TEMPORARY_FAILURE = 75;

    /**
     * The command was terminated with the SIGTERM signal.
     */
    public const TERMINATED = 143;
}
