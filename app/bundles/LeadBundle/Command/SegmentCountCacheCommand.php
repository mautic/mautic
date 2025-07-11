<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Command;

use Mautic\CoreBundle\Helper\ExitCode;
use Mautic\LeadBundle\Entity\LeadListRepository;
use Mautic\LeadBundle\Helper\SegmentCountCacheHelper;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SegmentCountCacheCommand extends Command
{
    public const COMMAND_NAME = 'lead:list:count-cache-update';

    public function __construct(
        private LeadListRepository $leadListRepository,
        private SegmentCountCacheHelper $segmentCountCacheHelper,
    ) {
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this->setName(self::COMMAND_NAME)
            ->setDescription('Update segment count cache for changed segments.');
    }

    /**
     * @throws InvalidArgumentException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $segmentsForRecount = $this->getAllSegmentsForRecount();
        if (count($segmentsForRecount) > 0) {
            $totalLeadCount = $this->leadListRepository->getLeadCount($segmentsForRecount);
            if (!is_array($totalLeadCount)) {
                $totalLeadCount = [$segmentsForRecount[0] => $totalLeadCount];
            }
            foreach ($totalLeadCount as $segmentId => $leadCount) {
                $this->segmentCountCacheHelper->setSegmentContactCount((int) $segmentId, (int) $leadCount);
            }
        }
        $output->writeln(sprintf('<info>%s segment\'s contact count have been updated.</info>', count($segmentsForRecount)));

        return ExitCode::SUCCESS;
    }

    /**
     * @return int[]
     */
    private function getAllSegmentsForRecount(): array
    {
        $segmentsForRecount = [];
        $segmentIds         = $this->leadListRepository->getLists();
        foreach ($segmentIds as $segment) {
            $segmentId = $segment['id'];
            if ($this->segmentCountCacheHelper->hasSegmentIdForReCount($segmentId)) {
                $segmentsForRecount[] = $segmentId;
            }
        }

        return $segmentsForRecount;
    }
}
