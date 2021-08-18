<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Helper\Tree;

use RecursiveIteratorIterator;

class JsPlumbFormatter implements NodeFormatterInterface
{
    public function format(NodeInterface $parentNode): array
    {
        $iterator = new RecursiveIteratorIterator($parentNode, RecursiveIteratorIterator::SELF_FIRST);
        $data     = $this->addNodeAndEdges($parentNode, ['nodes' => [], 'edges' => []]);

        foreach ($iterator as $childNode) {
            $data = $this->addNodeAndEdges($childNode, $data);
        }

        return $data;
    }

    private function addNodeAndEdges(NodeInterface $parentNode, array $data): array
    {
        $data['nodes'][] = ['id' => $parentNode->getValue(), 'name' => $parentNode->getValue()];

        foreach ($parentNode->getChildrenArray() as $childNode) {
            $data['edges'][] = ['source' => $parentNode->getValue(), 'target' => $childNode->getValue()];
        }

        return $data;
    }
}
