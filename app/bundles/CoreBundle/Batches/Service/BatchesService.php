<?php

namespace Mautic\CoreBundle\Batches\Service;

use Mautic\CoreBundle\Batches\Action\BatchActionInterface;
use Mautic\CoreBundle\Batches\Exception\BatchActionFailException;
use Mautic\CoreBundle\Batches\Group\BatchGroupInterface;
use Mautic\CoreBundle\Batches\Runner\BatchRunner;
use Mautic\CoreBundle\Batches\Runner\BatchRunnerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * {@inheritdoc}
 *
 * @see BatchesServiceInterface
 *
 * @author David Vurbs <david.vurbs@mautic.com>
 */
class BatchesService implements BatchesServiceInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * BatchesService constructor.
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @see BatchesServiceInterface::createRunnerFromGroup()
     * {@inheritdoc}
     */
    public function createRunnerFromGroup(Request $request, BatchGroupInterface $batchGroup, $actionName)
    {
        $actions = $batchGroup->registerActions();

        if (!array_key_exists($actionName, $actions)) {
            throw BatchActionFailException::unknownActionTypeInGroup($actionName, $batchGroup);
        }

        return $this->createRunner($request, $actions[$actionName]);
    }

    /**
     * Create runner of single action
     *
     * @param Request               $request
     * @param BatchActionInterface  $batchAction
     *
     * @throws BatchActionFailException
     *
     * @return BatchRunnerInterface
     */
    private function createRunner(Request $request, BatchActionInterface $batchAction)
    {
        if ($batchAction->getSourceAdapter() === null) {
            throw BatchActionFailException::sourceAdapterNotSet();
        }

        if ($batchAction->getHandlerAdapter() === null) {
            throw BatchActionFailException::handlerAdapterNotSet();
        }

        $batchAction->getSourceAdapter()->startup($this->container);
        $batchAction->getHandlerAdapter()->startup($this->container);

        $runner = new BatchRunner();
        $runner->setBatchAction($batchAction, $request);
        return $runner;
    }
}