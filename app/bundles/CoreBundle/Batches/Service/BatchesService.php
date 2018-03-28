<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Batches\Service;

use Mautic\CoreBundle\Batches\Action\BatchActionInterface;
use Mautic\CoreBundle\Batches\Exception\BatchActionFailException;
use Mautic\CoreBundle\Batches\Runner\BatchRunner;
use Symfony\Component\HttpFoundation\Request;

class BatchesService implements BatchesServiceInterface
{
    /**
     * @see BatchesServiceInterface::createRunner()
     * {@inheritdoc}
     */
    public function createRunner(Request $request, BatchActionInterface $batchAction)
    {
        if ($batchAction->getSourceAdapter() === null) {
            throw BatchActionFailException::sourceAdapterNotSet();
        }

        if ($batchAction->getHandlerAdapter() === null) {
            throw BatchActionFailException::handlerAdapterNotSet();
        }

        $runner = new BatchRunner();
        $runner->setBatchAction($batchAction, $request);
        return $runner;
    }
}