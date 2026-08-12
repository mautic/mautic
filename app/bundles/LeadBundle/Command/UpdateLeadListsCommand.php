<?php

namespace Mautic\LeadBundle\Command;

use Mautic\CoreBundle\Command\ModeratedCommand;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\PathsHelper;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Entity\LeadListRepository;
use Mautic\LeadBundle\Model\ListModel;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsCommand(
    name: UpdateLeadListsCommand::NAME,
    description: 'Update contacts in smart segments based on new contact data.',
    aliases: ['mautic:segments:rebuild']
)]
final class UpdateLeadListsCommand extends ModeratedCommand
{
    public const NAME = 'mautic:segments:update';

    public function __construct(
        private readonly ListModel $listModel,
        private readonly TranslatorInterface $translator,
        PathsHelper $pathsHelper,
        CoreParametersHelper $coreParametersHelper,
        private readonly LeadListRepository $leadListRepository,
    ) {
        parent::__construct($pathsHelper, $coreParametersHelper);
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                '--batch-limit',
                '-b',
                InputOption::VALUE_OPTIONAL,
                'Set batch size of contacts to process per round. Defaults to 300.',
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
                '--list-id',
                '-i',
                InputOption::VALUE_OPTIONAL,
                'Specific ID to rebuild. Defaults to all.',
                false
            )
            ->addOption(
                '--timing',
                '-tm',
                InputOption::VALUE_OPTIONAL,
                'Measure timing of build with output to CLI .',
                false
            )
            ->addOption(
                'exclude',
                'd',
                InputOption::VALUE_IS_ARRAY | InputOption::VALUE_OPTIONAL,
                'Exclude a specific segment from being rebuilt. Otherwise, all segments will be rebuilt.',
                []
            );

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $id                    = $input->getOption('list-id');
        $batch                 = $input->getOption('batch-limit');
        $max                   = $input->getOption('max-contacts') ? (int) $input->getOption('max-contacts') : null;
        $enableTimeMeasurement = (bool) $input->getOption('timing');
        $output                = ($input->getOption('quiet')) ? new NullOutput() : $output;
        $excludeSegments       = $input->getOption('exclude');

        if (!$this->checkRunStatus($input, $output, $id)) {
            return Command::SUCCESS;
        }

        if ($enableTimeMeasurement) {
            $startTime = microtime(true);
        }

        if ($id) {
            $list = $this->listModel->getEntity($id);

            if (!$list) {
                $output->writeln('<error>'.$this->translator->trans('mautic.lead.list.rebuild.not_found', ['%id%' => $id]).'</error>');

                return Command::FAILURE;
            }

            // Track already rebuilt lists to avoid rebuilding them multiple times
            $rebuiltLists = [];

            // First check if this segment has dependencies and rebuild them
            if ($list->hasFilterTypeOf('leadlist')) {
                $this->rebuildDependentSegments($list, $rebuiltLists, $batch, $max, $output, $enableTimeMeasurement, [], $excludeSegments);
            }

            // Add the current list ID to the rebuilt lists to avoid rebuilding it again
            $rebuiltLists[] = (int) $list->getId();

            $this->rebuildSegment($list, $batch, $max, $output, $enableTimeMeasurement);
        } else {
            $filter = [
                'iterable_mode' => true,
            ];

            if (is_array($excludeSegments) && count($excludeSegments) > 0) {
                $filter['filter'] = [
                    'force' => [
                        [
                            'expr'   => 'notIn',
                            'column' => $this->leadListRepository->getTableAlias().'.id',
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

                // Skip if already rebuilt
                if (in_array($listId, $rebuiltLists)) {
                    continue;
                }

                // Process any dependent segments first (segments that are used as filters in this segment)
                if ($leadList->hasFilterTypeOf('leadlist')) {
                    $this->rebuildDependentSegments($leadList, $rebuiltLists, $batch, $max, $output, $enableTimeMeasurement, [], $excludeSegments);
                }

                // Add the current list ID to the rebuilt lists to avoid rebuilding it again
                $rebuiltLists[] = $listId;

                // Rebuild the current segment
                $this->rebuildSegment($leadList, $batch, $max, $output, $enableTimeMeasurement);
            }
        }

        $this->completeRun();

        if ($enableTimeMeasurement) {
            $totalTime = round(microtime(true) - $startTime, 2);
            $output->writeln('<fg=magenta>'.$this->translator->trans('mautic.lead.list.rebuild.total.time', ['%time%' => $totalTime]).'</>'."\n");
        }

        return Command::SUCCESS;
    }

    /**
     * @param array<int>        $rebuiltLists    List of segment IDs that have already been rebuilt
     * @param array<int>        $dependencyChain Chain of segment IDs to detect circular dependencies
     * @param array<int|string> $excludeSegments List of segment IDs to exclude from rebuilding
     *
     * @param-out array<int> $rebuiltLists Updated list of segment IDs that have been rebuilt
     */
    private function rebuildDependentSegments(
        LeadList $leadList,
        array &$rebuiltLists,
        int $batch,
        ?int $max,
        OutputInterface $output,
        bool $enableTimeMeasurement,
        array $dependencyChain = [],
        array $excludeSegments = [],
    ): void {
        // Track the current segment in our dependency chain
        $currentId         = $leadList->getId();
        $dependencyChain[] = $currentId;

        foreach ($leadList->getFilters() as $filter) {
            if ('leadlist' === $filter['type']) {
                foreach ($filter['filter'] ?? [] as $dependentListId) {
                    $dependentListId = (int) $dependentListId;

                    // Skip if already rebuilt or in exclude list
                    if (in_array($dependentListId, $rebuiltLists) || in_array($dependentListId, $excludeSegments)) {
                        continue;
                    }

                    // Check for circular dependency
                    if (in_array($dependentListId, $dependencyChain)) {
                        $output->writeln(
                            '<error>'.$this->translator->trans(
                                'Circular dependency detected in segment chain: %chain%',
                                ['%chain%' => implode(' → ', array_merge($dependencyChain, [$dependentListId]))]
                            ).'</error>'
                        );
                        continue; // Skip this dependency to prevent infinite recursion
                    }

                    $dependentLeadList = $this->listModel->getEntity($dependentListId);
                    if (!$dependentLeadList) {
                        continue; // Skip if the dependent segment doesn't exist - it may have been deleted
                    }

                    // Check if this dependent segment has its own dependencies
                    if ($dependentLeadList->hasFilterTypeOf('leadlist')) {
                        // Recursively process this segment's dependencies first, passing the current chain
                        $this->rebuildDependentSegments(
                            $dependentLeadList,
                            $rebuiltLists,
                            $batch,
                            $max,
                            $output,
                            $enableTimeMeasurement,
                            $dependencyChain,
                            $excludeSegments
                        );
                    }

                    // Now rebuild this dependent segment
                    $this->rebuildSegment($dependentLeadList, $batch, $max, $output, $enableTimeMeasurement);
                    $rebuiltLists[] = $dependentListId;
                }
            }
        }
    }

    private function rebuildSegment(LeadList $segment, int $batch, ?int $max, OutputInterface $output, bool $enableTimeMeasurement = false): void
    {
        if (!$segment->isPublished()) {
            return;
        }

        $output->writeln('<info>'.$this->translator->trans('mautic.lead.list.rebuild.rebuilding', ['%id%' => $segment->getId()]).'</info>');
        $lastBuiltDateBefore = $segment->getLastBuiltDate();
        $startTime           = microtime(true);
        $processed           = $this->listModel->rebuildListLeads($segment, $batch, $max, $output);
        $rebuildTime         = round(microtime(true) - $startTime, 2);

        // ListModel sets lastBuiltDate only when membership work is fully finished
        // (including runs capped by --max-contacts that still drain the queue).
        if ($segment->getLastBuiltDate() !== $lastBuiltDateBefore) {
            if (!$this->areSegmentFilterDependenciesComplete($segment)) {
                $segment->setLastBuiltDate(
                    null === $lastBuiltDateBefore ? null : \DateTime::createFromInterface($lastBuiltDateBefore)
                );
            } else {
                $segment->setLastBuiltTime($rebuildTime);
            }

            $this->listModel->saveEntity($segment);
        }

        $output->writeln(
            '<comment>'.$this->translator->trans('mautic.lead.list.rebuild.leads_affected', ['%leads%' => $processed]).'</comment>'
        );

        if ($enableTimeMeasurement) {
            $output->writeln('<fg=cyan>'.$this->translator->trans(
                'mautic.lead.list.rebuild.contacts.time',
                ['%time%' => $rebuildTime]
            ).'</>'."\n");
        }
    }

    /**
     * A segment that filters on other segments must not be marked complete while
     * any of those dependencies still need rebuilding (e.g. after a capped run).
     */
    private function areSegmentFilterDependenciesComplete(LeadList $segment): bool
    {
        foreach ($segment->getFilters() as $filter) {
            if ('leadlist' !== $filter['type']) {
                continue;
            }

            foreach ($filter['filter'] ?? [] as $dependentListId) {
                $dependentListId = (int) $dependentListId;
                $dependentList   = $this->listModel->getEntity($dependentListId);

                if (!$dependentList instanceof LeadList) {
                    continue;
                }

                if ($dependentList->needsRebuild()) {
                    return false;
                }
            }
        }

        return true;
    }
}
