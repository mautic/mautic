<?php

namespace Mautic\CoreBundle\Batches\Service;

use Mautic\CoreBundle\Batches\Exception\BatchActionFailException;
use Mautic\CoreBundle\Batches\Group\BatchGroupInterface;
use Mautic\CoreBundle\Batches\Runner\BatchRunnerInterface;
use Symfony\Component\HttpFoundation\Request;

interface BatchesServiceInterface
{
    /**
     * Create batch action runner from group
     *
     * @param Request               $request
     * @param BatchGroupInterface   $batchGroup
     * @param string                $actionName
     *
     * @throws BatchActionFailException
     *
     * @return BatchRunnerInterface
     */
    public function createRunnerFromGroup(Request $request, BatchGroupInterface $batchGroup, $actionName);
}