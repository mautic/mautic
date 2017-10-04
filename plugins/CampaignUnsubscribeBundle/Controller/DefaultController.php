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

use Mautic\CoreBundle\Controller\CommonController;
use Mautic\LeadBundle\Entity\DoNotContact;
use MauticPlugin\CampaignUnsubscribeBundle\Entity\Unsubscribe;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use MauticPlugin\CampaignUnsubscribeBundle\CampaignUnsubscribeEvents;

/**
 * Class DefaultController
 * @package MauticPlugin\CampaignUnsubscribeBundle\Controller
 */
class DefaultController extends CommonController
{

    /**
     * @param $idHash
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function unsubscribeAction($idHash)
    {
        $config = $this->get('mautic.helper.core_parameters');

        $request = Request::createFromGlobals();

        // get the model instances
        $leadModel = $this->getModel('lead');
        $campaignModel = $this->getModel('campaign');
        $campEventModel = $this->getModel('campaign.event');

        $repository = $this->getDoctrine()->getRepository('CampaignUnsubscribeBundle:CampaignName');


        $model = $this->getModel('email');
        $stat = $model->getEmailStatus($idHash);

        if ($stat) {
            $leadId = $stat->getLead()->getId();
        } else {
            $leadId = false;
        }


        // check if lead is set, and if either subscriptions or the donotcontact is selected
        if ($request->request->get('form[lead]', null, true) && (
                $request->request->get('form[subscriptions]', null, true) ||
                $request->request->get('form[donotcontact]', null, true)
            )
        ) {
            // get lead instance and set as current lead so the event will be triggered for this lead
            $lead = $leadModel->getEntity($request->request->get('form[lead]', null, true));
            $leadModel->setCurrentLead($lead);

            // if donotcontact is selected unsubscribe the remaining campaigns
            if ($request->request->get('form[donotcontact]', null, true)) {

                $leadModel->addDncForLead($lead, 'email', '', DoNotContact::UNSUBSCRIBED);
            }

            // trigger unsubscribeEvent
            $campEventModel->triggerEvent(
                CampaignUnsubscribeEvents::UNSUBSCRIBE,
                [
                    'toBeUnsubscribed' => $request->request->get('form[subscriptions]', null, true),
                    'doNotContact' => $request->request->get('form[donotcontact]', null, true) ? true : false
                ]
            );

            // redirect to unsubscribe_thanks page
            return $this->postActionRedirect([
                'returnUrl' => $this->generateUrl('unsubscribe_thanks', ['idHash' => $idHash])
            ]);
        }

        if ($lead = $leadModel->getEntity($leadId)) {

            // get all campaigns for the lead
            $campaigns = $campaignModel->getLeadCampaigns($lead, true);

            // prepare campaignInputs array for formBuilder
            $campaignInputs = [];
            foreach ($campaigns as $campaign) {
                $campaignName = $repository->findOneBy(['campaign' => $campaign['id']]);
                if ($campaignName) {
                    $campaignInputs[$campaignName->getName()] = $campaign['id'];
                }
            }

            // create new Unsubscribe instance and set lead and subscriptions
            $unsubscribe = new Unsubscribe();
            $unsubscribe->setLead($lead->getId());
            $unsubscribe->setSubscriptions($campaignInputs);

            // create form
            $form = $this->createFormBuilder($unsubscribe)
                ->add('lead', HiddenType::class)
                ->add('subscriptions', ChoiceType::class, [
                    'expanded' => true,
                    'multiple' => true,
                    'choices' => $unsubscribe->getSubscriptions(),
                    'choices_as_values' => true,
                    'label' => !empty($campaignInputs) ? $config->getParameter('campaign_unsubscribe_campaign_list_label') : false,
                    'required' => !empty($campaignInputs),
                    'data' => []
                ])
                ->add('donotcontact', CheckboxType::class, ['label' => $config->getParameter('campaign_unsubscribe_donotcontact_label')])
                ->add('save', SubmitType::class, ['label' => $config->getParameter('campaign_unsubscribe_submit_label')])
                ->getForm();

            $messageTitle = str_replace(
                [
                    '|EMAIL|'
                ],
                [
                    $lead->getEmail()
                ],
                $config->getParameter('campaign_unsubscribe_message_title')
            );

            $messageBody = str_replace(
                [
                    '|EMAIL|'
                ],
                [
                    $lead->getEmail()
                ],
                !empty($campaignInputs) ? $config->getParameter('campaign_unsubscribe_message_body') : $config->getParameter('campaign_unsubscribe_message_body_no_campaigns')
            );

            $logoUrl = $config->getParameter('campaign_unsubscribe_logo_url');

            // render unsubscribe view
            return $this->delegateView([
                'contentTemplate' => 'CampaignUnsubscribeBundle:Unsubscribe:form.html.php',
                'viewParameters' => [
                    'form' => $form->createView(),
                    'lead' => isset($lead) ? $lead->getFields() : false,
                    'campaigns' => isset($campaigns) ? $campaigns : false,
                    'messageTitle' => $messageTitle,
                    'messageBody' => $messageBody,
                    'logoUrl' => !empty($logoUrl) ? $logoUrl : false
                ],
            ]);
        }

        return $this->delegateView([
            'contentTemplate' => 'CampaignUnsubscribeBundle:Unsubscribe:form.html.php',
            'viewParameters' => [
                'lead' => false,
            ],
        ]);
    }

    /**
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function thanksAction($idHash)
    {
        $config = $this->get('mautic.helper.core_parameters');

        // get the model instances
        $leadModel = $this->getModel('lead');

        $model = $this->getModel('email');
        $stat = $model->getEmailStatus($idHash);

        if ($stat) {
            $leadId = $stat->getLead()->getId();
        } else {
            $leadId = false;
        }

        $lead = $leadModel->getEntity($leadId);

        $confirmationTitle = str_replace(
            [
                '|EMAIL|'
            ],
            [
                $lead->getEmail()
            ],
            $config->getParameter('campaign_unsubscribe_confirmation_title')
        );

        $confirmationBody = str_replace(
            [
                '|EMAIL|'
            ],
            [
                $lead->getEmail()
            ],
            $config->getParameter('campaign_unsubscribe_confirmation_body')
        );

        $logoUrl = $config->getParameter('campaign_unsubscribe_logo_url');

        return $this->delegateView([
            'contentTemplate' => 'CampaignUnsubscribeBundle:Unsubscribe:thanks.html.php',
            'viewParameters' => [
                'confirmationTitle' => $confirmationTitle,
                'confirmationBody' => $confirmationBody,
                'logoUrl' => !empty($logoUrl) ? $logoUrl : false
            ]
        ]);
    }
}