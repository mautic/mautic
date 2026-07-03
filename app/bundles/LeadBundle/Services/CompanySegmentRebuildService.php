<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Services;

use Mautic\LeadBundle\Entity\CompanySegment;
use Mautic\LeadBundle\Model\CompanySegmentModel;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Service to handle company segment dependency resolution and rebuilding.
 */
final class CompanySegmentRebuildService
{
    public function __construct(
        private CompanySegmentModel $companySegmentModel,
        private TranslatorInterface $translator,
    ) {
    }

    /**
     * Rebuild a single company segment.
     */
    public function rebuildSegment(
        CompanySegment $companySegment,
        int $batch,
        ?int $max,
        OutputInterface $output,
        bool $enableTimeMeasurement = false,
    ): int {
        if (!$companySegment->isPublished()) {
            return 0;
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

        return $processed;
    }

    /**
     * Rebuild all dependent segments recursively before rebuilding the main segment.
     *
     * @param array<int>        $rebuiltSegments List of segment IDs that have already been rebuilt
     * @param array<int>        $dependencyChain Chain of segment IDs to detect circular dependencies
     * @param array<int|string> $excludeSegments List of segment IDs to exclude from rebuilding
     *
     * @param-out array<int> $rebuiltSegments Updated list of segment IDs that have been rebuilt
     */
    public function rebuildDependentSegments(
        CompanySegment $companySegment,
        array &$rebuiltSegments,
        int $batch,
        ?int $max,
        OutputInterface $output,
        bool $enableTimeMeasurement,
        array $dependencyChain = [],
        array $excludeSegments = [],
    ): void {
        $currentId         = $companySegment->getId();
        $dependencyChain[] = $currentId;

        foreach ($companySegment->getFilters() as $filter) {
            if (CompanySegmentModel::PROPERTIES_FIELD === $filter['type']) {
                foreach ($filter['filter'] ?? [] as $dependentSegmentId) {
                    $dependentSegmentId = (int) $dependentSegmentId;

                    // Skip if already rebuilt or in exclude list
                    if (in_array($dependentSegmentId, $rebuiltSegments) || in_array($dependentSegmentId, $excludeSegments)) {
                        continue;
                    }

                    // Check for circular dependency
                    if (in_array($dependentSegmentId, $dependencyChain)) {
                        $output->writeln(
                            '<error>'.$this->translator->trans(
                                'Circular dependency detected in company segment chain: %chain%',
                                ['%chain%' => implode(' → ', array_merge($dependencyChain, [$dependentSegmentId]))]
                            ).'</error>'
                        );
                        continue; // Skip this dependency to prevent infinite recursion
                    }

                    $dependentCompanySegment = $this->companySegmentModel->getEntity($dependentSegmentId);
                    if (!$dependentCompanySegment instanceof CompanySegment) {
                        continue;
                    }

                    // Check if this dependent segment has its own dependencies
                    if ($dependentCompanySegment->hasFilterTypeOf(CompanySegmentModel::PROPERTIES_FIELD)) {
                        $this->rebuildDependentSegments(
                            $dependentCompanySegment,
                            $rebuiltSegments,
                            $batch,
                            $max,
                            $output,
                            $enableTimeMeasurement,
                            $dependencyChain,
                            $excludeSegments
                        );
                    }

                    $this->rebuildSegment($dependentCompanySegment, $batch, $max, $output, $enableTimeMeasurement);
                    $rebuiltSegments[] = $dependentSegmentId;
                }
            }
        }
    }
}
