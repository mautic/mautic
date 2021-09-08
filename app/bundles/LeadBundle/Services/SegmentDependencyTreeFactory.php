<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Services;

use Mautic\CoreBundle\Helper\Tree\IntNode;
use Mautic\CoreBundle\Helper\Tree\NodeInterface;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Model\ListModel;
use Symfony\Component\Routing\RouterInterface;

class SegmentDependencyTreeFactory
{
    /**
     * @var ListModel
     */
    private $segmentModel;

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var int[]
     */
    private $usedSegmentIds = [];

    public function __construct(ListModel $segmentModel, RouterInterface $router)
    {
        $this->segmentModel = $segmentModel;
        $this->router       = $router;
    }

    public function buildTree(LeadList $segment, NodeInterface $rootNode = null): NodeInterface
    {
        $rootNode      = $rootNode ?? new IntNode($segment->getId());
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
            function (array $filter) {
                return 'leadlist' === $filter['type'];
            }
        );

        if (!$segmentMembershipFilters) {
            return [];
        }

        $childSegmentIds = [];

        foreach ($segmentMembershipFilters as $filter) {
            foreach ($filter['properties']['filter'] as $childSegmentId) {
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
