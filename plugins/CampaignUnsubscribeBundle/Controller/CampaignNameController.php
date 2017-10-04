<?php

/*
 * @copyright   2017 Partout D.N.A. All rights reserved
 * @author      Partout D.N.A.
 *
 * @link        https://partout.nl
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\CampaignUnsubscribeBundle\Controller;

use MauticPlugin\CampaignUnsubscribeBundle\Entity\CampaignName;
use Mautic\FormBundle\Controller\FormController;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class CampaignNameController
 * @package MauticPlugin\CampaignUnsubscribeBundle\Controller
 */
class CampaignNameController extends FormController
{
    /**
     * @param $objectAction
     * @param int $objectId
     * @return array|JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function executeAction($objectAction, $objectId = 0, $objectSubId = 0, $objectModel = '')
    {
        if (method_exists($this, "{$objectAction}Action")) {
            return $this->{"{$objectAction}Action"}($objectId);
        } else {
            return $this->accessDenied();
        }
    }

    /**
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function indexAction($page = 1)
    {
        $em = $this->getDoctrine()->getManager();
        $session = $this->get('session');

        $repository = $em->getRepository('CampaignUnsubscribeBundle:CampaignName');

        if ($this->request->getMethod() == 'POST') {
            $this->setListFilters();
        }

        $orderBy = $session->get('mautic.unsubscribeCampaignName.orderby', 'ucn.id');
        $orderByDir = $session->get('mautic.unsubscribeCampaignName.orderbydir', 'DESC');

        $entities = $repository->createQueryBuilder('ucn')
            ->orderBy($orderBy, $orderByDir)->getQuery()->getResult();

        $tmpl = $this->request->isXmlHttpRequest() ? $this->request->get('tmpl', 'index') : 'index';

        return $this->delegateView(
            [
                'contentTemplate' => 'CampaignUnsubscribeBundle:CampaignName:list.html.php',
                'viewParameters' => [
                    'items' => $entities,
                    'tmpl' => $tmpl
                ]
            ]
        );
    }

    /**
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function newAction()
    {
        $campaignName = new CampaignName($this->getModel('campaign'));

        $method = $this->request->getMethod();
        $cancelled = $valid = false;

        $form = $this->get('form.factory')->create(
            'campaign_name',
            $campaignName,
            [
                'action' => $this->generateUrl('plugin_unsubscribe_new_campaign_name'),
            ]
        );

        if ($method == 'POST') {
            $valid = false;

            if (!$cancelled = $this->isFormCancelled($form)) {
                if ($valid = $this->isFormValid($form)) {
                    $success = 1;

                    $em = $this->getDoctrine()->getManager();

                    $campaignId = $campaignName->getCampaign();
                    $campaign = $this->getModel('campaign')->getEntity($campaignId);
                    $campaignName->setCampaign($campaign);

                    $em->persist($campaignName);
                    $em->flush();
                }
            } else {
                $success = 1;
            }
        }

        $closeModal = ($cancelled || ($valid && $form->get('buttons')->get('save')->isClicked()));

        if ($closeModal) {
            return $this->postActionRedirect([
                'returnUrl' => $this->generateUrl('plugin_unsubscribe_campaign_name_index'),
                'viewParameters' => [],
                'contentTemplate' => 'CampaignUnsubscribeBundle:CampaignName:index',
                'passthroughVars' => [
                    'closeModal' => 1
                ]
            ]);
        }

        return $this->delegateView(
            array(
                'contentTemplate' => 'CampaignUnsubscribeBundle:CampaignName:newfield.html.php',
                'viewParameters' => [
                    'form' => $form->createView(),
                ]
            )
        );
    }

    /**
     * @param int $objectId
     * @return JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function deleteAction($objectId)
    {
        $returnUrl = $this->generateUrl('plugin_unsubscribe_campaign_name_index');

        $flashes = [];

        $postActionVars = [
            'returnUrl' => $returnUrl,
            'viewParameters' => [],
            'contentTemplate' => 'CampaignUnsubscribeBundle:CampaignName:index',
            'passthroughVars' => [
                'activeLink' => '#mautic_form_index',
                'mauticContent' => 'form',
            ],
        ];

        if ($this->request->getMethod() == 'POST') {
            $em = $this->getDoctrine()->getManager();

            $repository = $em->getRepository('CampaignUnsubscribeBundle:CampaignName');

            $entity = $repository->findOneBy(['id' => $objectId]);

            if ($entity === null) {
                $flashes[] = [
                    'type' => 'error',
                    'msg' => 'mautic.form.error.notfound',
                    'msgVars' => ['%id%' => $objectId],
                ];
            }

            $em->remove($entity);
            $em->flush();

            $identifier = $this->get('translator')->trans($entity->getName());
            $flashes[] = [
                'type' => 'notice',
                'msg' => 'mautic.core.notice.deleted',
                'msgVars' => [
                    '%name%' => $identifier,
                    '%id%' => $objectId,
                ],
            ];
        } //else don't do anything

        return $this->postActionRedirect(
            array_merge(
                $postActionVars,
                [
                    'flashes' => $flashes
                ]
            )
        );
    }


    /**
     * @return JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function batchDeleteAction()
    {
        $em = $this->getDoctrine()->getManager();
        $repository = $em->getRepository('CampaignUnsubscribeBundle:CampaignName');

        if ($this->request->getMethod() == 'POST') {

            $ids = json_decode($this->request->query->get('ids', '{}'));
            foreach ($ids as $objectId) {
                $entity = $repository->findOneBy(['id' => $objectId]);

                $em->remove($entity);
            }

            $em->flush();
        }

        return $this->postActionRedirect(
            [
                'returnUrl' => $this->generateUrl('plugin_unsubscribe_campaign_name_index'),
                'contentTemplate' => 'CampaignUnsubscribeBundle:CampaignName:index',
            ]
        );
    }
}