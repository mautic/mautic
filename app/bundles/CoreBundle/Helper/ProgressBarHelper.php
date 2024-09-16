<?php

namespace Mautic\CoreBundle\Helper;

use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\StreamOutput;

class ProgressBarHelper
{
    /**
     * Avoid printing progress bar to stderr
     * https://github.com/symfony/symfony/issues/18744.
     *
     * @param int $maxCount
     *
     * @return ProgressBar
     */
    public static function init(OutputInterface $output, $maxCount = 0)
    {
        $output = $output instanceof StreamOutput ? new StreamOutput($output->getStream()) : $output;

        return new ProgressBar($output, $maxCount);
    }
}
