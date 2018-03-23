<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Batches\Runner;

use Mautic\CoreBundle\Batches\Action\BatchActionInterface;
use Mautic\CoreBundle\Batches\Exception\BatchActionSuccessException;
use Symfony\Component\HttpFoundation\Request;

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