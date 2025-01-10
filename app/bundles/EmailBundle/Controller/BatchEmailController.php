<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Controller;

use Mautic\CategoryBundle\Model\CategoryModel;
use Mautic\CoreBundle\Controller\AbstractFormController;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Form\Type\BatchCategoryType;
use Mautic\EmailBundle\Model\EmailActionModel;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class BatchEmailController extends AbstractFormController
{
    /**
     * Adds or removes categories to multiple emails defined by email ID.
     */
    public function execAction(Request $request, EmailActionModel $actionModel, CategoryModel $categoryModel): JsonResponse
    {
        $params = $request->get('email_batch');
        $ids    = empty($params['ids']) ? [] : json_decode($params['ids']);

        if ($ids && is_array($ids)) {
            $newCategoryId = $params['newCategory'];

            $newCategory = $categoryModel->getEntity($newCategoryId);
            $affected    = $actionModel->setCategory($ids, $newCategory);

            $this->addFlashMessage('mautic.email.batch_emails_affected', [
                '%count%' => count($affected),
            ]);
        } else {
            $this->addFlashMessage('mautic.core.error.ids.missing');
        }

        return new JsonResponse([
            'closeModal'  => true,
            'flashes'     => $this->getFlashContent(),
            'affected'    => !empty($affected) ? array_map(fn (Email $affected) => $affected->getId(), $affected) : [],
            'newCategory' => [
                'name'  => !empty($newCategory) ? $newCategory->getTitle() : null,
                'color' => !empty($newCategory) ? $newCategory->getColor() : null,
            ],
            'callback' => 'emailBatchSubmitCallback',
        ]);
    }

    /**
     * View the modal form for adding contacts into categories in batches.
     */
    public function indexAction(): Response
    {
        $route = $this->generateUrl('mautic_email_batch_categories_set');

        return $this->delegateView(
            [
                'viewParameters' => [
                    'form' => $this->createForm(
                        BatchCategoryType::class,
                        [],
                        [
                            'action' => $route,
                            'attr'   => [
                                'data-submit-callback' => 'emailBatchSubmit',
                            ],
                        ]
                    )->createView(),
                ],
                'contentTemplate' => '@MauticEmail/Batch/form.html.twig',
                'passthroughVars' => [
                    'activeLink'    => '#mautic_email_index',
                    'mauticContent' => 'emailBatch',
                    'route'         => $route,
                ],
            ]
        );
    }
}
