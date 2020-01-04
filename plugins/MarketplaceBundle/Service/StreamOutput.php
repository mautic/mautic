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
        $message = $this->prependTimestamp($message);

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

    private function prependTimestamp(string $message): string
    {
        if (empty($message)) {
            return $message;
        }

        $dateTime = new \DateTimeImmutable();

        return "{$dateTime->format(\DateTimeInterface::ATOM)} - {$message}";
    }
}
