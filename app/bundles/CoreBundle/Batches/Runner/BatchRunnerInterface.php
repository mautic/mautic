<?php

namespace Mautic\CoreBundle\Batches\Runner;

use Mautic\CoreBundle\Batches\Action\BatchActionInterface;
use Mautic\CoreBundle\Batches\Exception\BatchActionFailException;
use Mautic\CoreBundle\Batches\Exception\BatchActionSuccessException;
use Symfony\Component\HttpFoundation\Request;

/**
 * Runner that run a batch action according to request
 *
 * @author David Vurbs <david.vurbs@mautic.com>
 */
interface BatchRunnerInterface
{
    /**
     * Set batch action
     *
     * @param BatchActionInterface  $batchAction
     * @param Request               $request
     */
    public function setBatchAction(BatchActionInterface $batchAction, Request $request);

    /**
     * Run a batch action
     *
     * @throws BatchActionSuccessException
     * @throws BatchActionFailException
     */
    public function run();
}