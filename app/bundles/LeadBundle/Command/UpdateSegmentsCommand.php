<?php

namespace Mautic\LeadBundle\Command;

use Mautic\CoreBundle\Command\ModeratedCommand;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\PathsHelper;
use Mautic\LeadBundle\Entity\CompanySegment;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Model\CompanySegmentModel;
use Mautic\LeadBundle\Model\ListModel;
use Mautic\LeadBundle\Services\CompanySegmentRebuildService;
use Mautic\LeadBundle\Services\LeadSegmentRebuildService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsCommand(
    name: UpdateSegmentsCommand::NAME,
    description: 'Update contacts in smart segments and companies in company segments based on new data.',
    aliases: ['mautic:segments:rebuild']
)]
class UpdateSegmentsCommand extends ModeratedCommand
{
    public const NAME = 'mautic:segments:update';

    public function __construct(
        private ListModel $listModel,
        private CompanySegmentModel $companySegmentModel,
        private LeadSegmentRebuildService $leadRebuildService,
        private CompanySegmentRebuildService $companyRebuildService,
        private TranslatorInterface $translator,
        PathsHelper $pathsHelper,
        CoreParametersHelper $coreParametersHelper,
    ) {
        parent::__construct($pathsHelper, $coreParametersHelper);
    }

    protected function configure()
    {
        $this
            ->addOption(
                '--batch-limit',
                '-b',
                InputOption::VALUE_OPTIONAL,
                'Set batch size of contacts and companies to process per round. Defaults to 300.',
                300
            )
            ->addOption(
                '--max-contacts',
                '-m',
                InputOption::VALUE_OPTIONAL,
                'Set max number of contacts to process per segment for this script execution. Defaults to all.',
                false
            )
            ->addOption(
                '--max-companies',
                null,
                InputOption::VALUE_REQUIRED,
                'Set max number of companies to process per company segment for this script execution. Defaults to all.'
            )
            ->addOption(
                '--list-id',
                '-i',
                InputOption::VALUE_OPTIONAL,
                'Specific lead segment ID to rebuild. Defaults to all.',
                false
            )
            ->addOption(
                'companysegment-id',
                null,
                InputOption::VALUE_REQUIRED,
                'Specific company segment ID to rebuild. Defaults to all.'
            )
            ->addOption(
                '--timing',
                '-tm',
                InputOption::VALUE_OPTIONAL,
                'Measure timing of build with output to CLI.',
                false
            )
            ->addOption(
                'exclude',
                'd',
                InputOption::VALUE_IS_ARRAY | InputOption::VALUE_OPTIONAL,
                'Exclude specific lead segment IDs from being rebuilt. Otherwise, all lead segments will be rebuilt.',
                []
            )
            ->addOption(
                'exclude-companysegment-id',
                null,
                InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED,
                'Exclude specific company segment IDs from being rebuilt. Otherwise, all company segments will be rebuilt.',
                []
            );

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $listId                 = $input->getOption('list-id');
        $companySegmentId       = $input->getOption('companysegment-id');
        $batch                  = $input->getOption('batch-limit');
        $maxContacts            = $input->getOption('max-contacts') ? (int) $input->getOption('max-contacts') : null;
        $maxCompanies           = $input->getOption('max-companies');
        $enableTimeMeasurement  = (bool) $input->getOption('timing');
        $output                 = ($input->getOption('quiet')) ? new NullOutput() : $output;
        $excludeLeadSegments    = $input->getOption('exclude');
        $excludeCompanySegments = $this->cleanExcludeCompanySegments($input->getOption('exclude-companysegment-id'), $output);

        // Validate companysegment-id
        if (null !== $companySegmentId && (!is_numeric($companySegmentId) || ((int) $companySegmentId <= 0))) {
            $output->writeln('<error>The --companysegment-id option must be a positive number or none.</error>');

            return Command::FAILURE;
        }
        $companySegmentId = null !== $companySegmentId ? (int) $companySegmentId : null;

        // Validate batch-limit
        if (!is_numeric($batch) || (int) $batch < 1) {
            $output->writeln('<error>The --batch-limit option must be a positive number.</error>');

            return Command::FAILURE;
        }
        $batch = (int) $batch;

        // Validate max-companies
        if (null !== $maxCompanies && (!is_numeric($maxCompanies) || (int) $maxCompanies <= 0)) {
            $output->writeln('<error>The --max-companies option must be a positive number or none.</error>');

            return Command::FAILURE;
        }
        $maxCompanies = null !== $maxCompanies ? (int) $maxCompanies : null;

        if (!$this->checkRunStatus($input, $output, $listId ?: $companySegmentId)) {
            return Command::SUCCESS;
        }

        if ($enableTimeMeasurement) {
            $startTime = microtime(true);
        }

        if ($listId) {
            $result = $this->processSingleLeadSegment((int) $listId, $batch, $maxContacts, $output, $enableTimeMeasurement, $excludeLeadSegments);
            if (Command::FAILURE === $result) {
                return Command::FAILURE;
            }
        } elseif (null !== $companySegmentId) {
            $result = $this->processSingleCompanySegment($companySegmentId, $batch, $maxCompanies, $output, $enableTimeMeasurement, $excludeCompanySegments);
            if (Command::FAILURE === $result) {
                return Command::FAILURE;
            }
        } else {
            $this->processAllCompanySegments($batch, $maxCompanies, $output, $enableTimeMeasurement, $excludeCompanySegments);
            $this->processAllLeadSegments($batch, $maxContacts, $output, $enableTimeMeasurement, $excludeLeadSegments);
        }

        $this->completeRun();

        if ($enableTimeMeasurement) {
            $totalTime = round(microtime(true) - $startTime, 2);
            $output->writeln('<fg=magenta>'.$this->translator->trans('mautic.lead.list.rebuild.total.time', ['%time%' => $totalTime]).'</>'."\n");
        }

        return Command::SUCCESS;
    }

    /**
     * @param array<int|string> $excludeSegments
     */
    private function processSingleLeadSegment(int $id, int $batch, ?int $max, OutputInterface $output, bool $enableTimeMeasurement, array $excludeSegments): int
    {
        $list = $this->listModel->getEntity($id);

        if (!$list) {
            $output->writeln('<error>'.$this->translator->trans('mautic.lead.list.rebuild.not_found', ['%id%' => $id]).'</error>');

            return Command::FAILURE;
        }

        $rebuiltLists = [];

        if ($list->hasFilterTypeOf('leadlist')) {
            $this->leadRebuildService->rebuildDependentSegments($list, $rebuiltLists, $batch, $max, $output, $enableTimeMeasurement, [], $excludeSegments);
        }

        $rebuiltLists[] = (int) $list->getId();
        $this->leadRebuildService->rebuildSegment($list, $batch, $max, $output, $enableTimeMeasurement);

        return Command::SUCCESS;
    }

    /**
     * @param array<int> $excludeSegments
     */
    private function processSingleCompanySegment(int $id, int $batch, ?int $max, OutputInterface $output, bool $enableTimeMeasurement, array $excludeSegments): int
    {
        $segment = $this->companySegmentModel->getEntity($id);

        if (!$segment instanceof CompanySegment) {
            $output->writeln('<error>'.$this->translator->trans('mautic.company_segments.rebuild.not_found', ['%id%' => $id]).'</error>');

            return Command::FAILURE;
        }

        if (null === $segment->getId()) {
            $output->writeln('<error>'.$this->translator->trans('mautic.company_segments.rebuild.not_found', ['%id%' => $id]).'</error>');

            return Command::FAILURE;
        }

        $rebuiltSegments = [];

        if ($segment->hasFilterTypeOf(CompanySegmentModel::PROPERTIES_FIELD)) {
            $this->companyRebuildService->rebuildDependentSegments($segment, $rebuiltSegments, $batch, $max, $output, $enableTimeMeasurement, [], $excludeSegments);
        }

        $rebuiltSegments[] = (int) $segment->getId();
        $this->companyRebuildService->rebuildSegment($segment, $batch, $max, $output, $enableTimeMeasurement);

        return Command::SUCCESS;
    }

    /**
     * @param array<int|string> $excludeSegments
     */
    private function processAllLeadSegments(int $batch, ?int $max, OutputInterface $output, bool $enableTimeMeasurement, array $excludeSegments): void
    {
        $filter = [
            'iterable_mode' => true,
        ];

        if (count($excludeSegments) > 0) {
            $filter['filter'] = [
                'force' => [
                    [
                        'expr'   => 'notIn',
                        'column' => $this->listModel->getRepository()->getTableAlias().'.id',
                        'value'  => $excludeSegments,
                    ],
                ],
            ];
        }

        $rebuiltLists = [];
        $leadLists    = $this->listModel->getEntities($filter);

        /** @var LeadList $leadList */
        foreach ($leadLists as $leadList) {
            $listId = $leadList->getId();

            if (in_array($listId, $rebuiltLists)) {
                continue;
            }

            if ($leadList->hasFilterTypeOf('leadlist')) {
                $this->leadRebuildService->rebuildDependentSegments($leadList, $rebuiltLists, $batch, $max, $output, $enableTimeMeasurement, [], $excludeSegments);
            }

            $rebuiltLists[] = $listId;
            $this->leadRebuildService->rebuildSegment($leadList, $batch, $max, $output, $enableTimeMeasurement);
        }
    }

    /**
     * @param array<int> $excludeSegments
     */
    private function processAllCompanySegments(int $batch, ?int $max, OutputInterface $output, bool $enableTimeMeasurement, array $excludeSegments): void
    {
        $filter = [
            'iterable_mode' => true,
        ];

        if ([] !== $excludeSegments) {
            $filter['filter'] = [
                'force' => [
                    [
                        'expr'   => 'notIn',
                        'column' => $this->companySegmentModel->getRepository()->getTableAlias().'.id',
                        'value'  => $excludeSegments,
                    ],
                ],
            ];
        }

        $companySegments = $this->companySegmentModel->getEntities($filter);
        $rebuiltSegments = [];

        foreach ($companySegments as $companySegment) {
            assert($companySegment instanceof CompanySegment);
            $segmentId = $companySegment->getId();

            if (in_array($segmentId, $rebuiltSegments)) {
                continue;
            }

            if ($companySegment->hasFilterTypeOf(CompanySegmentModel::PROPERTIES_FIELD)) {
                $this->companyRebuildService->rebuildDependentSegments($companySegment, $rebuiltSegments, $batch, $max, $output, $enableTimeMeasurement, [], $excludeSegments);
            }

            $rebuiltSegments[] = $segmentId;
            $this->companyRebuildService->rebuildSegment($companySegment, $batch, $max, $output, $enableTimeMeasurement);
            unset($companySegment);
        }
        unset($companySegments);
    }

    /**
     * @param array<mixed> $excludeSegments
     *
     * @return array<int>
     */
    private function cleanExcludeCompanySegments(array $excludeSegments, OutputInterface $output): array
    {
        if (0 === count($excludeSegments)) {
            return [];
        }

        $existingExcludeSegments = [];

        foreach ($excludeSegments as $id) {
            if (null !== $id && (!is_numeric($id) || ((int) $id <= 0))) {
                $output->writeln("<error>Skipped --exclude-companysegment-id id {$id}: The segment id {$id} is not a positive number.</error>");
                continue;
            }
            if (!$this->companySegmentModel->getEntity((int) $id) instanceof CompanySegment) {
                $output->writeln("<error>Skipped --exclude-companysegment-id id {$id}: There is no company segment with the id {$id}.</error>");
                continue;
            }
            $existingExcludeSegments[] = (int) $id;
        }

        return $existingExcludeSegments;
    }
}
