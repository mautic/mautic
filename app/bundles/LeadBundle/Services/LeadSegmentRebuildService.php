<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Services;

use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Model\ListModel;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class LeadSegmentRebuildService
{
    public function __construct(
        private ListModel $listModel,
        private TranslatorInterface $translator,
    ) {
    }

    public function rebuildSegment(
        LeadList $segment,
        int $batch,
        ?int $max,
        OutputInterface $output,
        bool $enableTimeMeasurement = false,
    ): int {
        if (!$segment->isPublished()) {
            return 0;
        }

        $output->writeln('<info>'.$this->translator->trans('mautic.lead.list.rebuild.rebuilding', ['%id%' => $segment->getId()]).'</info>');
        $startTime   = microtime(true);
        $processed   = $this->listModel->rebuildListLeads($segment, $batch, $max, $output);
        $rebuildTime = round(microtime(true) - $startTime, 2);

        if (null === $max) {
            // Only full segment rebuilds count
            $segment->setLastBuiltDateToCurrentDatetime();
            $segment->setLastBuiltTime($rebuildTime);
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

        return $processed;
    }

    /**
     * @param array<int>        $rebuiltLists    List of segment IDs that have already been rebuilt
     * @param array<int>        $dependencyChain Chain of segment IDs to detect circular dependencies
     * @param array<int|string> $excludeSegments List of segment IDs to exclude from rebuilding
     *
     * @param-out array<int> $rebuiltLists Updated list of segment IDs that have been rebuilt
     */
    public function rebuildDependentSegments(
        LeadList $leadList,
        array &$rebuiltLists,
        int $batch,
        ?int $max,
        OutputInterface $output,
        bool $enableTimeMeasurement,
        array $dependencyChain = [],
        array $excludeSegments = [],
    ): void {
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
                        continue;
                    }

                    $dependentLeadList = $this->listModel->getEntity($dependentListId);
                    if (!$dependentLeadList) {
                        continue;
                    }

                    if ($dependentLeadList->hasFilterTypeOf('leadlist')) {
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

                    $this->rebuildSegment($dependentLeadList, $batch, $max, $output, $enableTimeMeasurement);
                    $rebuiltLists[] = $dependentListId;
                }
            }
        }
    }
}
