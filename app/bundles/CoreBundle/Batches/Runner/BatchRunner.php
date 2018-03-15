<?php

namespace Mautic\CoreBundle\Batches\Runner;

use Mautic\CoreBundle\Batches\Action\BatchActionInterface;
use Mautic\CoreBundle\Batches\Exception\BatchActionSuccessException;
use Symfony\Component\HttpFoundation\Request;

/**
 * {@inheritdoc}
 *
 * @see BatchRunnerInterface
 *
 * @author David Vurbs <david.vurbs@mautic.com>
 */
class BatchRunner implements BatchRunnerInterface
{
    /**
     * @var BatchActionInterface
     */
    private $batchAction;

    /**
     * @var Request
     */
    private $request;

    /**
     * @see BatchRunnerInterface::setBatchAction()
     * {@inheritdoc}
     */
    public function setBatchAction(BatchActionInterface $batchAction, Request $request)
    {
        $this->batchAction =    $batchAction;
        $this->request =        $request;
    }

    /**
     * @see BatchRunnerInterface::run()
     * {@inheritdoc}
     */
    public function run()
    {
        $this->batchAction->getHandlerAdapter()->loadSettings($this->request);
        $objects = $this->batchAction->getSourceAdapter()->loadObjectsById(
            $this->batchAction->getSourceAdapter()->getIdList($this->request)
        );

        foreach ($objects as $object) {
            $this->batchAction->getHandlerAdapter()->update($object);
        }

        $this->batchAction->getHandlerAdapter()->store($objects);

        $successException = new BatchActionSuccessException();
        $successException->setCountProcessed(count($objects));
        throw $successException;
    }
}