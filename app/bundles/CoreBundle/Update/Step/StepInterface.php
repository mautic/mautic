<?php

/*
 * @copyright   2020 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        https://www.mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Update\Step;

use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

interface StepInterface
{
    public function getOrder(): int;

    public function shouldExecuteInFinalStage(): bool;

    public function execute(ProgressBar $progressBar, InputInterface $input, OutputInterface $output): void;
}
