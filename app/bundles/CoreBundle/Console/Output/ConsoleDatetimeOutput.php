<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Console\Output;

use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\ConsoleOutputInterface;

/**
 * Custom console output to prefix all messages with the current datetime
 * Heavily inspired by https://github.com/8p/AssistBundle.
 *
 * Class ConsoleDatetimeOutput
 */
class ConsoleDatetimeOutput extends ConsoleOutput implements ConsoleOutputInterface
{
    /**
     * Prefix message with current datetime.
     *
     * @param string $message
     * @param bool   $newline
     */
    protected function doWrite($message, $newline)
    {
        $date = (new \DateTime())->format('Y-m-d H:i:s');

        if (!empty($message)) {
            $message = sprintf('[%s] %s', $date, $message);
        }

        parent::doWrite($message, $newline);
    }
}
