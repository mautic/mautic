<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Model\AbTest;

use Mautic\CoreBundle\Entity\VariantEntityInterface;
use Mautic\CoreBundle\Event\DetermineWinnerEvent;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class AbTestResultService
{
    public function __construct(
        private EventDispatcherInterface $dispatcher,
    ) {
    }

    /**
     * @param mixed[] $criteria
     *
     * @return array{winners?: array<int, int|string>, support?: mixed, basedOn?: string, supportTemplate?: string}
     */
    public function getAbTestResult(VariantEntityInterface $parentVariant, array $criteria = []): ?array
    {
        // get A/B test information
        [$parent, $children] = $parentVariant->getVariants();

        $abTestResults = [];
        if ($criteria) {
            $testSettings = $criteria;
            $args         = [
                'email'    => $parentVariant,
                'parent'   => $parent,
                'children' => $children,
            ];

            if (isset($testSettings['event'])) {
                $determineWinnerEvent = new DetermineWinnerEvent($args);
                $this->dispatcher->dispatch($determineWinnerEvent, $testSettings['event']);
                $abTestResults = $determineWinnerEvent->getAbTestResults();
            }
        }

        return $abTestResults;
    }
}
