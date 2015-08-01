<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Controller;

use Mautic\PluginBundle\Helper\IntegrationHelper;
use Mautic\CoreBundle\Controller\AjaxController as CommonAjaxController;
use Mautic\CoreBundle\Helper\BuilderTokenHelper;
use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\CoreBundle\Event\IconEvent;
use Mautic\CoreBundle\CoreEvents;
use Symfony\Component\HttpFoundation\Request;
use Mautic\LeadBundle\LeadEvents;
use Mautic\LeadBundle\Event\LeadTimelineEvent;

/**
 * Class AjaxController
 *
 * @package Mautic\LeadBundle\Controller
 */
class AjaxController extends CommonAjaxController
{

    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    protected function userListAction (Request $request)
    {
        $filter    = InputHelper::clean($request->query->get('filter'));
        $results   = $this->factory->getModel('lead.lead')->getLookupResults('user', $filter);
        $dataArray = array();
        foreach ($results as $r) {
            $name        = $r['firstName'] . ' ' . $r['lastName'];
            $dataArray[] = array(
                "label" => $name,
                "value" => $r['id']
            );
        }

        return $this->sendJsonResponse($dataArray);
    }

    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    protected function fieldListAction (Request $request)
    {
        $dataArray = array('success' => 0);
        $filter    = InputHelper::clean($request->query->get('filter'));
        $field     = InputHelper::clean($request->query->get('field'));
        if (!empty($field)) {
            if ($field == "owner_id") {
                $results = $this->factory->getModel('lead.lead')->getLookupResults('user', $filter);
                foreach ($results as $r) {
                    $name        = $r['firstName'] . ' ' . $r['lastName'];
                    $dataArray[] = array(
                        "value" => $name,
                        "id"    => $r['id']
                    );
                }
            } else {
                $results = $this->factory->getModel('lead.field')->getLookupResults($field, $filter);
                foreach ($results as $r) {
                    $dataArray[] = array('value' => $r[$field]);
                }
            }
        }

        return $this->sendJsonResponse($dataArray);
    }

    /**
     * Updates the cache and gets returns updated HTML
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    protected function updateSocialProfileAction (Request $request)
    {
        $dataArray = array('success' => 0);
        $network   = InputHelper::clean($request->request->get('network'));
        $leadId    = InputHelper::clean($request->request->get('lead'));

        if (!empty($leadId)) {
            //find the lead
            $model = $this->factory->getModel('lead.lead');
            $lead  = $model->getEntity($leadId);

            if ($lead !== null && $this->factory->getSecurity()->hasEntityAccess('lead:leads:editown', 'lead:leads:editown', $lead->getOwner())) {
                $fields            = $lead->getFields();
                /** @var \Mautic\PluginBundle\Helper\IntegrationHelper $integrationHelper */
                $integrationHelper = $this->factory->getHelper('integration');
                $socialProfiles    = $integrationHelper->getUserProfiles($lead, $fields, true, $network);
                $socialProfileUrls = $integrationHelper->getSocialProfileUrlRegex(false);
                $networks          = array();
                $socialCount       = count($socialProfiles);
                if (empty($network) || empty($socialCount)) {
                    $dataArray['completeProfile'] = $this->renderView('MauticLeadBundle:Social:index.html.php', array(
                        'socialProfiles'    => $socialProfiles,
                        'lead'              => $lead,
                        'socialProfileUrls' => $socialProfileUrls
                    ));
                    $dataArray['socialCount']     = $socialCount;
                } else {
                    foreach ($socialProfiles as $name => $details) {
                        $networks[$name]['newContent'] = $this->renderView('MauticLeadBundle:Social/' . $name . ':view.html.php', array(
                            'lead'              => $lead,
                            'details'           => $details,
                            'network'           => $name,
                            'socialProfileUrls' => $socialProfileUrls
                        ));
                    }
                    $dataArray['profiles'] = $networks;
                }

                $dataArray['success'] = 1;
            }
        }

        return $this->sendJsonResponse($dataArray);
    }

    /**
     * Clears the cache for a network
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    protected function clearSocialProfileAction (Request $request)
    {
        $dataArray = array('success' => 0);
        $network   = InputHelper::clean($request->request->get('network'));
        $leadId    = InputHelper::clean($request->request->get('lead'));

        if (!empty($leadId)) {
            //find the lead
            $model = $this->factory->getModel('lead.lead');
            $lead  = $model->getEntity($leadId);

            if ($lead !== null && $this->factory->getSecurity()->hasEntityAccess('lead:leads:editown', 'lead:leads:editown', $lead->getOwner())) {
                $dataArray['success'] = 1;
                /** @var \Mautic\PluginBundle\Helper\IntegrationHelper $helper */
                $helper         = $this->factory->getHelper('integration');
                $socialProfiles = $helper->clearIntegrationCache($lead, $network);
                $socialCount    = count($socialProfiles);

                if (empty($socialCount)) {
                    $dataArray['completeProfile'] = $this->renderView('MauticLeadBundle:Social:index.html.php', array(
                        'socialProfiles'    => $socialProfiles,
                        'lead'              => $lead,
                        'socialProfileUrls' => $helper->getSocialProfileUrlRegex(false)
                    ));
                }

                $dataArray['socialCount'] = $socialCount;
            }
        }

        return $this->sendJsonResponse($dataArray);
    }

    /**
     * Updates the timeline events and gets returns updated HTML
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    protected function updateTimelineAction (Request $request)
    {
        $dataArray     = array('success' => 0);
        $includeEvents = InputHelper::clean($request->request->get('includeEvents', array()));
        $excludeEvents = InputHelper::clean($request->request->get('excludeEvents', array()));
        $search        = InputHelper::clean($request->request->get('search'));
        $leadId        = InputHelper::int($request->request->get('leadId'));

        if (!empty($leadId)) {
            //find the lead
            $model = $this->factory->getModel('lead.lead');
            $lead  = $model->getEntity($leadId);

            if ($lead !== null) {

                $session = $this->factory->getSession();

                $filter = array(
                    'search'        => $search,
                    'includeEvents' => $includeEvents,
                    'excludeEvents' => $excludeEvents
                );

                $session->set('mautic.lead.' . $leadId . '.timeline.filters', $filter);

                // Trigger the TIMELINE_ON_GENERATE event to fetch the timeline events from subscribed bundles
                $dispatcher = $this->factory->getDispatcher();
                $event      = new LeadTimelineEvent($lead, $filter);
                $dispatcher->dispatch(LeadEvents::TIMELINE_ON_GENERATE, $event);

                $events     = $event->getEvents();
                $eventTypes = $event->getEventTypes();

                $event = new IconEvent($this->factory->getSecurity());
                $this->factory->getDispatcher()->dispatch(CoreEvents::FETCH_ICONS, $event);
                $icons = $event->getIcons();

                $timeline = $this->renderView('MauticLeadBundle:Lead:history.html.php', array(
                        'events'       => $events,
                        'eventTypes'   => $eventTypes,
                        'eventFilters' => $filter,
                        'icons'        => $icons,
                        'lead'         => $lead)
                );

                $dataArray['success']      = 1;
                $dataArray['timeline']     = $timeline;
                $dataArray['historyCount'] = count($events);
            }
        }

        return $this->sendJsonResponse($dataArray);
    }

    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    protected function toggleLeadListAction (Request $request)
    {
        $dataArray = array('success' => 0);
        $leadId    = InputHelper::int($request->request->get('leadId'));
        $listId    = InputHelper::int($request->request->get('listId'));
        $action    = InputHelper::clean($request->request->get('listAction'));

        if (!empty($leadId) && !empty($listId) && in_array($action, array('remove', 'add'))) {
            $leadModel = $this->factory->getModel('lead');
            $listModel = $this->factory->getModel('lead.list');

            $lead = $leadModel->getEntity($leadId);
            $list = $listModel->getEntity($listId);

            if ($lead !== null && $list !== null) {
                $class = $action == 'add' ? 'addToLists' : 'removeFromLists';
                $leadModel->$class($lead, $list);
                $dataArray['success'] = 1;
            }
        }

        return $this->sendJsonResponse($dataArray);
    }

    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    protected function toggleLeadCampaignAction (Request $request)
    {
        $dataArray  = array('success' => 0);
        $leadId     = InputHelper::int($request->request->get('leadId'));
        $campaignId = InputHelper::int($request->request->get('campaignId'));
        $action     = InputHelper::clean($request->request->get('campaignAction'));

        if (!empty($leadId) && !empty($campaignId) && in_array($action, array('remove', 'add'))) {
            $leadModel     = $this->factory->getModel('lead');
            $campaignModel = $this->factory->getModel('campaign');

            $lead     = $leadModel->getEntity($leadId);
            $campaign = $campaignModel->getEntity($campaignId);

            if ($lead !== null && $campaign !== null) {
                $class = "{$action}Lead";
                $campaignModel->$class($campaign, $lead, true);
                $dataArray['success'] = 1;
            }
        }

        return $this->sendJsonResponse($dataArray);
    }

    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    protected function getImportProgressAction(Request $request)
    {
        $dataArray = array('success' => 1);

        if ($this->factory->getSecurity()->isGranted('lead:leads:create')) {
            $session               = $this->factory->getSession();
            $dataArray['progress'] = $session->get('mautic.lead.import.progress', array(0, 0));
            $dataArray['percent']  = ($dataArray['progress'][1]) ? ceil(($dataArray['progress'][0] / $dataArray['progress'][1]) * 100) : 100;
        }

        return $this->sendJsonResponse($dataArray);
    }

    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    protected function removeBounceStatusAction(Request $request)
    {
        $dataArray = array('success' => 0);
        $dncId     = $request->request->get('id');

        if (!empty($dncId)) {
            $this->factory->getModel('email')->getRepository()->deleteDoNotEmailEntry($dncId);

            $dataArray['success'] = 1;
        }

        return $this->sendJsonResponse($dataArray);
    }

    /**
     * Get the rows for new leads
     *
     * @param Request $request
     *
     * @return array|\Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    protected function getNewLeadsAction(Request $request)
    {
        $dataArray = array('success' => 0);
        $maxId     = $request->get('maxId');

        if (!empty($maxId)) {
            //set some permissions
            $permissions = $this->factory->getSecurity()->isGranted(array(
                'lead:leads:viewown',
                'lead:leads:viewother',
                'lead:leads:create',
                'lead:leads:editown',
                'lead:leads:editother',
                'lead:leads:deleteown',
                'lead:leads:deleteother'
            ), "RETURN_ARRAY");

            if (!$permissions['lead:leads:viewown'] && !$permissions['lead:leads:viewother']) {
                return $this->accessDenied(true);
            }

            /** @var \Mautic\LeadBundle\Model\LeadModel $model */
            $model   = $this->factory->getModel('lead.lead');
            $session = $this->factory->getSession();

            $search = $session->get('mautic.lead.filter', '');

            $filter     = array('string' => $search, 'force' => array());
            $translator = $this->factory->getTranslator();
            $anonymous  = $translator->trans('mautic.lead.lead.searchcommand.isanonymous');
            $mine       = $translator->trans('mautic.core.searchcommand.ismine');
            $indexMode  = $session->get('mautic.lead.indexmode', 'list');

            $session->set('mautic.lead.indexmode', $indexMode);

            // (strpos($search, "$isCommand:$anonymous") === false && strpos($search, "$listCommand:") === false)) ||
            if ($indexMode != 'list') {
                //remove anonymous leads unless requested to prevent clutter
                $filter['force'][] = "!$anonymous";
            }

            if (!$permissions['lead:leads:viewother']) {
                $filter['force'][] = $mine;
            }

            $filter['force'][] = array(
                'column' => 'l.id',
                'expr'   => 'gt',
                'value'  => $maxId
            );

            $results = $model->getEntities(
                array(
                    'filter'         => $filter,
                    'withTotalCount' => true
                )
            );
            $count = $results['count'];

            if (!empty($count)) {
                // Get the max ID of the latest lead added
                $maxLeadId = $model->getRepository()->getMaxLeadId();

                // We need the EmailRepository to check if a lead is flagged as do not contact
                /** @var \Mautic\EmailBundle\Entity\EmailRepository $emailRepo */
                $emailRepo = $this->factory->getModel('email')->getRepository();
                $indexMode = $this->request->get('view', $session->get('mautic.lead.indexmode', 'list'));
                $template  = ($indexMode == 'list') ? 'list_rows' : 'grid_cards';
                $dataArray['leads'] = $this->factory->getTemplating()->render("MauticLeadBundle:Lead:{$template}.html.php", array(
                    'items'         => $results['results'],
                    'noContactList' => $emailRepo->getDoNotEmailList(),
                    'permissions'   => $permissions,
                    'security'      => $this->factory->getSecurity(),
                    'highlight'      => true
                ));
                $dataArray['indexMode'] = $indexMode;
                $dataArray['maxId']     = $maxLeadId;
                $dataArray['success']   = 1;
            }
        }

        return $this->sendJsonResponse($dataArray);
    }

    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    protected function getEmailTemplateAction(Request $request)
    {
        $data    = array('success' => 1, 'body' => '', 'subject' => '');
        $emailId = $request->get('template');

        /** @var \Mautic\EmailBundle\Model\EmailModel $model */
        $model    = $this->factory->getModel('email');

        /** @var \Mautic\EmailBundle\Entity\Email $email */
        $email    = $model->getEntity($emailId);

        if ($email !== null && $this->factory->getSecurity()->hasEntityAccess(
            'email:emails:viewown',
            'email:emails:viewother',
            $email->getCreatedBy()
        )
        ) {

            $mailer = $this->factory->getMailer();
            $mailer->setEmail($email, true, array(), array(), true);

            $data['body']    = $mailer->getBody();
            $data['subject'] = $mailer->getSubject();

            // Parse tokens into view data
            $tokens = $model->getBuilderComponents($email, array('tokens', 'visualTokens'));

            BuilderTokenHelper::replaceTokensWithVisualPlaceholders($tokens, $data['body']);
        }

        return $this->sendJsonResponse($data);
    }
}
