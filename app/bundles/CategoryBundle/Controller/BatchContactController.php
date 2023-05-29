<?php

namespace Mautic\CategoryBundle\Controller;

use Doctrine\Persistence\ManagerRegistry;
use Mautic\CategoryBundle\Model\CategoryModel;
use Mautic\CategoryBundle\Model\ContactActionModel;
use Mautic\CoreBundle\Controller\AbstractFormController;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\LeadBundle\Form\Type\BatchType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class BatchContactController extends AbstractFormController
{
    /**
     * @var ContactActionModel
     */
    private $actionModel;

    /**
     * @var CategoryModel
     */
    private $categoryModel;

    public function __construct(CorePermissions $security, UserHelper $userHelper, ContactActionModel $actionModel, CategoryModel $categoryModel, ManagerRegistry $doctrine)
    {
        $this->actionModel   = $actionModel;
        $this->categoryModel = $categoryModel;

        parent::__construct($security, $userHelper, $doctrine);
    }

    /**
     * Adds or removes categories to multiple contacts defined by contact ID.
     *
     * @return JsonResponse
     */
    public function execAction(Request $request)
    {
        $params = $request->get('lead_batch');
        $ids    = empty($params['ids']) ? [] : json_decode($params['ids']);

        if ($ids && is_array($ids)) {
            $categoriesToAdd    = isset($params['add']) ? $params['add'] : [];
            $categoriesToRemove = isset($params['remove']) ? $params['remove'] : [];
            $contactIds         = json_decode($params['ids']);

            $this->actionModel->addContactsToCategories($contactIds, $categoriesToAdd);
            $this->actionModel->removeContactsFromCategories($contactIds, $categoriesToRemove);

            $this->addFlashMessage('mautic.lead.batch_leads_affected', [
                '%count%'     => count($ids),
            ]);
        } else {
            $this->addFlashMessage('mautic.core.error.ids.missing');
        }

        return new JsonResponse([
            'closeModal' => true,
            'flashes'    => $this->getFlashContent(),
        ]);
    }

    /**
     * View the modal form for adding contacts into categories in batches.
     *
     * @return JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function indexAction()
    {
        $route = $this->generateUrl('mautic_category_batch_contact_set');
        $rows  = $this->categoryModel->getLookupResults('global', '', 300);
        $items = [];

        foreach ($rows as $category) {
            $items[$category['title']] = $category['id'];
        }

        return $this->delegateView(
            [
                'viewParameters' => [
                    'form' => $this->createForm(
                        BatchType::class,
                        [],
                        [
                            'items'  => $items,
                            'action' => $route,
                        ]
                    )->createView(),
                ],
                'contentTemplate' => '@MauticLead/Batch/form.html.twig',
                'passthroughVars' => [
                    'activeLink'    => '#mautic_contact_index',
                    'mauticContent' => 'leadBatch',
                    'route'         => $route,
                ],
            ]
        );
    }
}
