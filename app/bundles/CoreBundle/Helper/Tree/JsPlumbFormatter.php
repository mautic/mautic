<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Helper\Tree;

use RecursiveIteratorIterator;

/**
 * Will generate this structure:.
 *
 * [
 *  "levels":[
 *    "nodes":[
 *      { "id":"foo", "name":"foo" },
 *      { "id":"bar", "name":"bar" }
 *    ],
 *  ],
 *  "edges":[
 *    { "source":"foo", "target":"bar" }
 *  ]
 *];
 */
class JsPlumbFormatter implements NodeFormatterInterface
{
    public function format(NodeInterface $parentNode): array
    {
        $iterator = new RecursiveIteratorIterator($parentNode, RecursiveIteratorIterator::SELF_FIRST);
        $data     = $this->addNodeAndEdges($parentNode, ['levels' => [], 'edges' => []], 0);

        foreach ($iterator as $childNode) {
            $data = $this->addNodeAndEdges($childNode, $data, $iterator->getDepth() + 1);
        }

        return $data;
    }

    private function addNodeAndEdges(NodeInterface $parentNode, array $data, int $depth): array
    {
        $data['levels'][$depth]['nodes'][] = ['id' => $parentNode->getValue(), 'name' => $parentNode->getValue()];

        foreach ($parentNode->getChildrenArray() as $childNode) {
            $data['edges'][] = ['source' => $parentNode->getValue(), 'target' => $childNode->getValue()];
        }

        return $data;
    }
}
