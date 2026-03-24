<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Command;

use Mautic\CoreBundle\Command\ModeratedCommand;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\PathsHelper;
use Mautic\LeadBundle\Entity\CompanySegment;
use Mautic\LeadBundle\Model\CompanySegmentModel;
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
class UpdateCompanySegmentsCommand extends ModeratedCommand
{
    public function __construct(
        private CompanySegmentModel $companySegmentModel,
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
                InputOption::VALUE_OPTIONAL,
                'Set batch size of companies to process per round. Defaults to 300.',
                300
            )
            ->addOption(
                'max-companies',
                'm',
                InputOption::VALUE_OPTIONAL,
                'Set max number of companies to process per company segment for this script execution. Defaults to all.',
                null
            )
            ->addOption(
                'segment-id',
                'i',
                InputOption::VALUE_OPTIONAL,
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
                InputOption::VALUE_IS_ARRAY | InputOption::VALUE_OPTIONAL,
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
        $excludeSegments       = $input->getOption('exclude');

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

            $this->rebuildSegment($segment, $batch, $max, $output, $enableTimeMeasurement);
        } else {
            $filter = [
                'iterable_mode' => true,
            ];

            if (is_array($excludeSegments) && count($excludeSegments) > 0) {
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

            foreach ($companySegments as $companySegment) {
                assert($companySegment instanceof CompanySegment);
                $this->rebuildSegment($companySegment, $batch, $max, $output, $enableTimeMeasurement);
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

    private function rebuildSegment(CompanySegment $companySegment, int $batch, ?int $max, OutputInterface $output, bool $enableTimeMeasurement = false): void
    {
        if (!$companySegment->isPublished()) {
            return;
        }

        $output->writeln('<info>'.$this->translator->trans('mautic.company_segments.rebuild.rebuilding', ['%id%' => $companySegment->getId()]).'</info>');
        $startTime   = microtime(true);
        $processed   = $this->companySegmentModel->rebuildCompanySegment($companySegment, $batch, $max, $output);
        $rebuildTime = round(microtime(true) - $startTime, 2);
        if (null === $max) {
            // Only full segment rebuilds count
            $companySegment->setLastBuiltDateToCurrentDatetime();
            $companySegment->setLastBuiltTime($rebuildTime);
            $this->companySegmentModel->saveEntity($companySegment);
        }

        $this->companySegmentModel->getRepository()->detachEntity($companySegment);

        $output->writeln(
            '<comment>'.$this->translator->trans('mautic.company_segments.rebuild.companies_affected', ['%companies%' => $processed]).'</comment>'
        );

        if ($enableTimeMeasurement) {
            $output->writeln('<fg=cyan>'.$this->translator->trans(
                'mautic.company_segments.rebuild.segment.time',
                ['%time%' => $rebuildTime]
            ).'</>'."\n");
        }
    }
}
