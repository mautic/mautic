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

class SegmentDependencyTreeFactory
{
    /**
     * @var ListModel
     */
    private $segmentModel;

    public function __construct(ListModel $segmentModel)
    {
        $this->segmentModel = $segmentModel;
    }

    public function buildTree(LeadList $segment, NodeInterface $rootNode = null): NodeInterface
    {
        $rootNode      = $rootNode ?? new IntNode($segment->getId());
        $childSegments = $this->findChildSegments($segment);

        foreach ($childSegments as $childSegment) {
            $rootNode->addChild(new IntNode($childSegment->getId()));
            $this->buildTree($childSegment, $rootNode);
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
}
