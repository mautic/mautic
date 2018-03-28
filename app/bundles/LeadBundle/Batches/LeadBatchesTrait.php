<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Batches;

use Mautic\CoreBundle\Batches\Action\BatchActionInterface;
use Mautic\CoreBundle\Batches\Exception\BatchActionFailException;
use Mautic\CoreBundle\Batches\Runner\BatchRunner;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

trait LeadBatchesTrait
{
    /**
     * Help handler for batch requests in lead.
     *
     * @param BatchActionInterface $batchAction
     * @param \Closure             $viewDelegateCallback
     *
     * @return mixed|JsonResponse
     */
    public function handleBatchRequest(BatchActionInterface $batchAction, \Closure $viewDelegateCallback)
    {
        try {
            $runner = new BatchRunner(
                $batchAction,
                $this->request
            );

            if ($this->request->isMethod(Request::METHOD_POST)) {
                $processedObjectsCount = $runner->run();

                $this->addFlash('mautic.lead.batch_leads_affected', [
                    'pluralCount' => $processedObjectsCount,
                    '%count%'     => $processedObjectsCount,
                ]);

                return new JsonResponse([
                    'closeModal' => true,
                    'flashes'    => $this->getFlashContent(),
                ]);
            }
        } catch (BatchActionFailException $e) {
            $this->addFlash($e->getMessage(), [], 'error', false);

            return new JsonResponse([
                'closeModal' => true,
                'flashes'    => $this->getFlashContent(),
            ]);
        }

        return call_user_func($viewDelegateCallback);
    }
}
