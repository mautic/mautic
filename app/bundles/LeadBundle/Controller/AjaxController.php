<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Controller;

use Mautic\AddonBundle\Helper\IntegrationHelper;
use Mautic\CoreBundle\Controller\AjaxController as CommonAjaxController;
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
                /** @var \Mautic\AddonBundle\Helper\IntegrationHelper $integrationHelper */
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
                /** @var \Mautic\AddonBundle\Helper\IntegrationHelper $helper */
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
}
