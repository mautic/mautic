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
 *      { "id":"1", "name":"foo" },
 *      { "id":"2", "name":"bar" }
 *    ],
 *  ],
 *  "edges":[
 *    { "source":"1", "target":"2" }
 *  ]
 *];
 */
class JsPlumbFormatter implements NodeFormatterInterface
{
    public function format(NodeInterface $node): array
    {
        $iterator = new RecursiveIteratorIterator($node, RecursiveIteratorIterator::SELF_FIRST);
        $data     = $this->addNodeAndEdges($node, ['levels' => [], 'edges' => []], 0, -1);

        foreach ($iterator as $childNode) {
            $data = $this->addNodeAndEdges($childNode, $data, $iterator->getDepth() + 1, $iterator->key());
        }

        return $data;
    }

    private function addNodeAndEdges(NodeInterface $parentNode, array $data, int $depth, $key): array
    {
        $parentParentId = $parentNode->getParent() ? $parentNode->getParent()->getValue() : 0;
        $id             = "{$parentParentId}-{$parentNode->getValue()}";
        $node           = [
            'id'   => $id,
            'name' => $parentNode->getParam('name'),
            'link' => $parentNode->getParam('link'),
        ];

        if ($message = $parentNode->getParam('message')) {
            $node['message'] = $message;
        }

        $data['levels'][$depth]['nodes'][] = $node;

        foreach ($parentNode->getChildrenArray() as $childNode) {
            $data['edges'][] = ['source' => $id, 'target' => "{$parentNode->getValue()}-{$childNode->getValue()}"];
        }

        return $data;
    }
}
