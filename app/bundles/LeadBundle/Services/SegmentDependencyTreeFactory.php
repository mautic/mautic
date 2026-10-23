<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Services;

use Mautic\CoreBundle\Helper\Tree\IntNode;
use Mautic\CoreBundle\Helper\Tree\NodeInterface;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Entity\LeadListRepository;
use Mautic\LeadBundle\Security\Permissions\LeadPermissions;
use Symfony\Component\Routing\RouterInterface;

final class SegmentDependencyTreeFactory
{
    /**
     * @var int[]
     */
    private array $usedSegmentIds = [];

    public function __construct(
        private readonly RouterInterface $router,
        private readonly LeadListRepository $leadListRepository,
        private readonly ?CorePermissions $corePermissions = null,
    ) {
    }

    public function buildTree(LeadList $segment, ?NodeInterface $rootNode = null): NodeInterface
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

        if ([] === $segmentMembershipFilters) {
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

        return array_values(array_filter(
            $this->leadListRepository->findBy(['id' => $childSegmentIds]),
            fn (LeadList $childSegment): bool => null === $this->corePermissions || $this->corePermissions->hasEntityAccess(
                LeadPermissions::LISTS_VIEW_OWN,
                LeadPermissions::LISTS_VIEW_OTHER,
                $childSegment->getCreatedBy()
            )
        ));
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
