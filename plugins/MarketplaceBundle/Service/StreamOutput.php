<?php

/*
 * @copyright   2019 Mautic. All rights reserved
 * @author      Mautic.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MarketplaceBundle\Service;

use RuntimeException;
use Symfony\Component\Console\Output\StreamOutput as SymfonyStreamOutput;

class StreamOutput extends SymfonyStreamOutput
{
    protected function doWrite($message, $newline)
    {
        if (
            false === @fwrite($this->getStream(), $message) ||
            (
                $newline &&
                (false === @fwrite($this->getStream(), PHP_EOL))
            )
        ) {
            throw new RuntimeException('Unable to write output.');
        }

        echo $message;

        ob_flush();
        flush();
    }
}
