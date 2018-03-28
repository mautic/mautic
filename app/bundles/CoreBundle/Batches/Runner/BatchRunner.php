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
use Mautic\CoreBundle\Batches\Exception\BatchActionFailException;
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
     * @see BatchRunnerInterface::__construct()
     * {@inheritdoc}
     */
    public function __construct(BatchActionInterface $batchAction, Request $request)
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
        if ($this->batchAction->getSourceAdapter() === null) {
            throw BatchActionFailException::sourceAdapterNotSet();
        }

        if ($this->batchAction->getHandlerAdapter() === null) {
            throw BatchActionFailException::handlerAdapterNotSet();
        }

        $settings = $this->batchAction->getHandlerAdapter()->getParameters($this->request);
        $this->batchAction->getHandlerAdapter()->loadSettings($settings);
        $objects = $this->batchAction->getSourceAdapter()->loadObjectsById(
            $this->batchAction->getSourceAdapter()->getIdList($settings)
        );

        foreach ($objects as $object) {
            $this->batchAction->getHandlerAdapter()->update($object);
        }

        $this->batchAction->getHandlerAdapter()->store($objects);

        return count($objects);
    }
}