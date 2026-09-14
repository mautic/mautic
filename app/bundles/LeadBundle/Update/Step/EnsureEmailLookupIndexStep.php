<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Update\Step;

use Mautic\CoreBundle\Update\Step\StepInterface;
use Mautic\LeadBundle\Field\EmailLookupIndex;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final readonly class EnsureEmailLookupIndexStep implements StepInterface
{
    public function __construct(
        private EmailLookupIndex $emailLookupIndex,
    ) {
    }

    public function getOrder(): int
    {
        return 55;
    }

    public function shouldExecuteInFinalStage(): bool
    {
        return true;
    }

    public function execute(ProgressBar $progressBar, InputInterface $input, OutputInterface $output): void
    {
        $this->emailLookupIndex->ensure();
        $progressBar->advance();
    }
}
