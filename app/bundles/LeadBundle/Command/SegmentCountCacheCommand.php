<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Command;

use Mautic\CoreBundle\Helper\ExitCode;
use Mautic\LeadBundle\Entity\LeadListRepository;
use Mautic\LeadBundle\Helper\SegmentCountCacheHelper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SegmentCountCacheCommand extends Command
{
    public const COMMAND_NAME = 'mautic:segments:update-cache';

    /**
     * @var LeadListRepository
     */
    private $leadListRepository;

    /**
     * @var SegmentCountCacheHelper
     */
    private $segmentCountCacheHelper;

    public function __construct(
        LeadListRepository $leadListRepository,
        SegmentCountCacheHelper $segmentCountCacheHelper,
    ) {
        $this->leadListRepository      = $leadListRepository;
        $this->segmentCountCacheHelper = $segmentCountCacheHelper;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setName(self::COMMAND_NAME)
            ->setDescription('Updates segment contact counts cache')
            ->addArgument(
                'segment_ids',
                InputArgument::IS_ARRAY | InputArgument::OPTIONAL,
                'Array of segment IDs to process',
                []
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $segmentIds = $input->getArgument('segment_ids');
        foreach ($segmentIds as $segmentId) {
            $totalLeadCount = $this->leadListRepository->getLeadCount((int) $segmentId);
            $this->segmentCountCacheHelper->setSegmentContactCount((int) $segmentId, (int) $totalLeadCount);
        }

        return ExitCode::SUCCESS;
    }
}
