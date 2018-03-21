<?php

namespace Mautic\LeadBundle\Batches;

use Mautic\CoreBundle\Batches\Exception\BatchActionFailException;
use Mautic\CoreBundle\Batches\Exception\BatchActionSuccessException;
use Mautic\CoreBundle\Batches\Group\BatchGroupInterface;
use Mautic\CoreBundle\Batches\Service\BatchesServiceInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Trait LeadBatchesTrait
 *
 * @package Mautic\CoreBundle\Batches\Helpers
 */
trait LeadBatchesTrait
{
    public function handleBatchRequest(BatchGroupInterface $batchGroup, $actionName, \Closure $viewDelegateCallback)
    {
        /** @var BatchesServiceInterface $batchesService */
        $batchesService = $this->get('mautic.batches');

        try {
            $runner = $batchesService->createRunnerFromGroup(
                $this->request,
                $batchGroup,
                $actionName
            );

            if ($this->request->isMethod(Request::METHOD_POST)) {
                $runner->run();
            }

        } catch (BatchActionSuccessException $successException) {
            $this->addFlash('mautic.lead.batch_leads_affected', [
                'pluralCount' => $successException->getCountProcessed(),
                '%count%'     => $successException->getCountProcessed(),
            ]);

            return new JsonResponse([
                'closeModal' => true,
                'flashes'    => $this->getFlashContent(),
            ]);

        } catch (BatchActionFailException $e) {
            if ($this->container->getParameter('kernel.environment') === 'dev') {
                $this->addFlash($e->getMessage(), [], 'error', false);
            }

            return new JsonResponse([
                'closeModal' => true,
                'flashes'    => $this->getFlashContent(),
            ]);
        }

        return call_user_func_array($viewDelegateCallback, [$actionName]);
    }
}