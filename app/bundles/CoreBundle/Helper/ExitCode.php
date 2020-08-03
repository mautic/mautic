<?php

declare(strict_types=1);

/*
 * @copyright   2020 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Helper;

/**
 * Holds exit code constants for commands.
 */
class ExitCode
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
