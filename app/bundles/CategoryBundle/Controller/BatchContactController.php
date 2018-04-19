<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CategoryBundle\Controller;

use Mautic\CategoryBundle\Entity\Category;
use Mautic\CoreBundle\Controller\AbstractFormController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;

class BatchContactController extends AbstractFormController
{
    public function initialize(FilterControllerEvent $event)
    {
        $this->actionModel   = $this->container->get('mautic.category.model.contact.action');
        $this->categoryModel = $this->container->get('mautic.category.model.category');
    }

    /**
     * Adds or removes categories to multiple contacts defined by contact ID.
     *
     * @return JsonResponse
     */
    public function execAction()
    {
        $params = $this->request->get('lead_batch');

        if (isset($params['ids'])) {
            $categoriesToAdd    = isset($params['add']) ? $params['add'] : [];
            $categoriesToRemove = isset($params['remove']) ? $params['remove'] : [];
            $contactIds         = json_decode($params['ids']);

            $this->actionModel->addContactsToCategories($contactIds, $categoriesToAdd);
            $this->actionModel->removeContactsFromCategories($contactIds, $categoriesToRemove);

            $this->addFlash('mautic.lead.batch_leads_affected', [
                'pluralCount' => count($params['ids']),
                '%count%'     => count($params['ids']),
            ]);

            return new JsonResponse([
                'closeModal' => true,
                'flashes'    => $this->getFlashContent(),
            ]);
        }
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
            $items[$category['id']] = $category['title'];
        }

        return $this->delegateView(
            [
                'viewParameters' => [
                    'form' => $this->createForm(
                        'lead_batch',
                        [],
                        [
                            'items'  => $items,
                            'action' => $route,
                        ]
                    )->createView(),
                ],
                'contentTemplate' => 'MauticLeadBundle:Batch:form.html.php',
                'passthroughVars' => [
                    'activeLink'    => '#mautic_contact_index',
                    'mauticContent' => 'leadBatch',
                    'route'         => $route,
                ],
            ]
        );
    }
}
