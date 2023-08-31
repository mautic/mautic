<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Services;

use Mautic\CoreBundle\Helper\Tree\IntNode;
use Mautic\CoreBundle\Helper\Tree\NodeInterface;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Model\ListModel;
use Symfony\Component\Routing\RouterInterface;

class SegmentDependencyTreeFactory
{
    public function __construct(
        private ListModel $segmentModel,
        private RouterInterface $router,
    ) {
    }

    /**
     * @param int[] $usedSegmentIds
     */
    public function buildTree(LeadList $segment, ?NodeInterface $rootNode = null, array $usedSegmentIds = []): NodeInterface
    {
        $rootNode      = $rootNode ?? new IntNode($segment->getId() ?? 0);
        $childSegments = $this->findChildSegments($segment);

        $rootNode->addParam('name', $segment->getName());
        $rootNode->addParam('link', $this->generateSegmentDetailRoute($segment));

        $usedSegmentIds[] = $segment->getId() ?? 0;

        foreach ($childSegments as $childSegment) {
            $childNode = new IntNode($childSegment->getId() ?? 0);
            $rootNode->addChild($childNode);
            $childNode->addParam('name', $childSegment->getName());
            $childNode->addParam('link', $this->generateSegmentDetailRoute($childSegment));

            // Be aware of the loops here. We must stop building children
            // and report the problem instead if there is a loop or duplicate segments.
            if (!in_array($childSegment->getId(), $usedSegmentIds)) {
                $this->buildTree($childSegment, $childNode, $usedSegmentIds);
            } else {
                $childNode->addParam('circular', true);
                $childNode->addParam('message', 'This segment creates a loop in the dependency tree.');
            }
        }

        return $rootNode;
    }

    /**
     * @return LeadList[]
     */
    private function findChildSegments(LeadList $segment): array
    {
        $segmentMembershipFilters = array_filter(
            $segment->getFilters(),
            fn (array $filter): bool => 'leadlist' === $filter['type']
        );

        if (!$segmentMembershipFilters) {
            return [];
        }

        $childSegmentIds = [];

        foreach ($segmentMembershipFilters as $filter) {
            $bcFilter              = $filter['filter'] ?? [];
            $childSegmentIdsFilter = $filter['properties']['filter'] ?? $bcFilter;
            foreach ($childSegmentIdsFilter as $childSegmentId) {
                $childSegmentIds[] = (int) $childSegmentId;
            }
        }

        return $this->segmentModel->getRepository()->findBy(['id' => $childSegmentIds]);
    }

    private function generateSegmentDetailRoute(LeadList $segment): string
    {
        return $this->router->generate(
            'mautic_segment_action',
            [
                'objectAction' => 'view',
                'objectId'     => $segment->getId(),
            ]
        );
    }
}
