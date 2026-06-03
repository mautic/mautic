<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Command;

use Mautic\CoreBundle\Command\ModeratedCommand;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\PathsHelper;
use Mautic\LeadBundle\Entity\CompanySegment;
use Mautic\LeadBundle\Model\CompanySegmentModel;
use Mautic\LeadBundle\Services\CompanySegmentRebuildService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsCommand(
    name: 'mautic:company-segments:update',
    description: 'Update companies in filter-based Company Segments based on new company data.',
)]
final class UpdateCompanySegmentsCommand extends ModeratedCommand
{
    public function __construct(
        private CompanySegmentModel $companySegmentModel,
        private CompanySegmentRebuildService $rebuildService,
        private TranslatorInterface $translator,
        PathsHelper $pathsHelper,
        CoreParametersHelper $coreParametersHelper,
    ) {
        parent::__construct($pathsHelper, $coreParametersHelper);
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'batch-limit',
                'b',
                InputOption::VALUE_REQUIRED,
                'Set batch size of companies to process per round. Defaults to 300.',
                300
            )
            ->addOption(
                'max-companies',
                'm',
                InputOption::VALUE_REQUIRED,
                'Set max number of companies to process per company segment for this script execution. Defaults to all.',
                null
            )
            ->addOption(
                'segment-id',
                'i',
                InputOption::VALUE_REQUIRED,
                'Specific ID to rebuild. Defaults to all.',
                null
            )
            ->addOption(
                'timing',
                'tm',
                InputOption::VALUE_NONE,
                'Measure timing of build with output to CLI.'
            )
            ->addOption(
                'exclude',
                'd',
                InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED,
                'Exclude a specific company segment from being rebuilt. Otherwise, all company segments will be rebuilt.',
                []
            );

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $id                    = $input->getOption('segment-id');
        $batch                 = $input->getOption('batch-limit');
        $max                   = $input->getOption('max-companies');
        $enableTimeMeasurement = (bool) $input->getOption('timing');
        $output                = true === $input->getOption('quiet') ? new NullOutput() : $output;
        $excludeSegments       = $this->cleanExcludeSegments($input->getOption('exclude'), $output);

        // Validate segment-id
        if (null !== $id && (!is_numeric($id) || ((int) $id <= 0))) {
            $output->writeln('<error>The --segment-id option must be a positive number or none.</error>');

            return Command::FAILURE;
        }
        $id = null !== $id ? (int) $id : null;

        if (!is_numeric($batch) || (int) $batch < 1) {
            $output->writeln('<error>The --batch-limit option must be a positive number.</error>');

            return Command::FAILURE;
        }
        $batch = (int) $batch;

        if (null !== $max && (!is_numeric($max) || (int) $max <= 0)) {
            $output->writeln('<error>The --max-companies option must be a positive number or none.</error>');

            return Command::FAILURE;
        }
        $max = null !== $max ? (int) $max : null;

        if (!$this->checkRunStatus($input, $output, $id)) {
            return Command::SUCCESS;
        }

        if ($enableTimeMeasurement) {
            $startTime = microtime(true);
        }

        if (null !== $id) {
            $segment = $this->companySegmentModel->getEntity($id);
            if (!$segment instanceof CompanySegment) {
                $output->writeln('<error>'.$this->translator->trans('mautic.company_segments.rebuild.not_found', ['%id%' => $id]).'</error>');

                return Command::FAILURE;
            }

            if (null === $segment->getId()) {
                $output->writeln('<error>'.$this->translator->trans('mautic.company_segments.rebuild.not_found', ['%id%' => $id]).'</error>');

                return Command::FAILURE;
            }

            // Track already rebuilt segments to avoid rebuilding them multiple times
            $rebuiltSegments = [];

            // First check if this segment has dependencies and rebuild them
            if ($segment->hasFilterTypeOf(CompanySegmentModel::PROPERTIES_FIELD)) {
                $this->rebuildService->rebuildDependentSegments($segment, $rebuiltSegments, $batch, $max, $output, $enableTimeMeasurement, [], $excludeSegments);
            }

            // Add the current segment ID to the rebuilt segments to avoid rebuilding it again
            $rebuiltSegments[] = (int) $segment->getId();

            $this->rebuildService->rebuildSegment($segment, $batch, $max, $output, $enableTimeMeasurement);
        } else {
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

                // Skip if already rebuilt
                if (in_array($segmentId, $rebuiltSegments)) {
                    continue;
                }

                // Process any dependent segments first (segments that are used as filters in this segment)
                if ($companySegment->hasFilterTypeOf(CompanySegmentModel::PROPERTIES_FIELD)) {
                    $this->rebuildService->rebuildDependentSegments($companySegment, $rebuiltSegments, $batch, $max, $output, $enableTimeMeasurement, [], $excludeSegments);
                }

                // Add the current segment ID to the rebuilt segments to avoid rebuilding it again
                $rebuiltSegments[] = $segmentId;

                // Rebuild the current segment
                $this->rebuildService->rebuildSegment($companySegment, $batch, $max, $output, $enableTimeMeasurement);
                unset($companySegment);
            }
            unset($companySegments);
        }

        $this->completeRun();

        if ($enableTimeMeasurement) {
            $totalTime = round(microtime(true) - $startTime, 2);
            $output->writeln('<fg=magenta>'.$this->translator->trans('mautic.company_segments.rebuild.total.time', ['%time%' => $totalTime]).'</>'."\n");
        }

        return Command::SUCCESS;
    }

    /**
     * @param array<mixed> $excludeSegments
     *
     * @return array<int>
     */
    private function cleanExcludeSegments(array $excludeSegments, OutputInterface $output): array
    {
        if (0 === count($excludeSegments)) {
            return [];
        }

        $existingExcludeSegments = [];

        foreach ($excludeSegments as $id) {
            if (null !== $id && (!is_numeric($id) || ((int) $id <= 0))) {
                $output->writeln("<error>Skipped --exclude id {$id}: The segment id {$id} specified in the --exclude options is not a positive number.</error>");
                continue;
            }
            if (!$this->companySegmentModel->getEntity((int) $id) instanceof CompanySegment) {
                $output->writeln("<error>Skipped --exclude id {$id}: There is no segment with the id {$id} specified in the --exclude options.</error>");
                continue;
            }
            $existingExcludeSegments[] = (int) $id;
        }

        return $existingExcludeSegments;
    }
}
