<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

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
     * @param OutputInterface $output
     * @param int             $maxCount
     *
     * @return ProgressBar
     */
    public static function init(OutputInterface $output, $maxCount = 0)
    {
        $output = $output instanceof StreamOutput ? new StreamOutput($output->getStream()) : $output;

        return new ProgressBar($output, $maxCount);
    }
}
