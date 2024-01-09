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
    /**
     * @var int[]
     */
    private array $usedSegmentIds = [];

    public function __construct(
        private ListModel $segmentModel,
        private RouterInterface $router
    ) {
    }

    public function buildTree(LeadList $segment, NodeInterface $rootNode = null): NodeInterface
    {
        $rootNode ??= new IntNode($segment->getId());
        $childSegments = $this->findChildSegments($segment);

        $rootNode->addParam('name', $segment->getName());
        $rootNode->addParam('link', $this->generateSegmentDetailRoute($segment));

        $this->usedSegmentIds[] = $segment->getId();

        foreach ($childSegments as $childSegment) {
            $childNode = new IntNode($childSegment->getId());
            $rootNode->addChild($childNode);
            $childNode->addParam('name', $childSegment->getName());
            $childNode->addParam('link', $this->generateSegmentDetailRoute($childSegment));

            // Be aware of the loops here. We must stop building children
            // and report the problem instead if there is a loop or duplicate segments.
            if (!in_array($childSegment->getId(), $this->usedSegmentIds)) {
                $this->buildTree($childSegment, $childNode);
            } else {
                $childNode->addParam('message', 'This segment already exists in the segment dependency tree');
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
            // Old segments don't use properties array.
            $segmentIds = $filter['properties']['filter'] ?? $filter['filter'];
            foreach ($segmentIds as $childSegmentId) {
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
