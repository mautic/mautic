<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Controller;

use Mautic\LeadBundle\Entity\Tag;
use Mautic\LeadBundle\Entity\UtmTag;
use Mautic\PluginBundle\Helper\IntegrationHelper;
use Mautic\CoreBundle\Controller\AjaxController as CommonAjaxController;
use Mautic\CoreBundle\Helper\BuilderTokenHelper;
use Mautic\CoreBundle\Helper\InputHelper;
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
        $results   = $this->getModel('lead.lead')->getLookupResults('user', $filter);
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
                $results = $this->getModel('lead.lead')->getLookupResults('user', $filter);
                foreach ($results as $r) {
                    $name        = $r['firstName'] . ' ' . $r['lastName'];
                    $dataArray[] = array(
                        "value" => $name,
                        "id"    => $r['id']
                    );
                }
            } 
            elseif ($field == "hit_url") {
                $dataArray[] = array(
                    'value' => ''
                );
            } else {
                $results = $this->getModel('lead.field')->getLookupResults($field, $filter);
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
            $model = $this->getModel('lead.lead');
            $lead  = $model->getEntity($leadId);

            if ($lead !== null && $this->factory->getSecurity()->hasEntityAccess('lead:leads:editown', 'lead:leads:editown', $lead->getOwner())) {
                $fields            = $lead->getFields();
                /** @var \Mautic\PluginBundle\Helper\IntegrationHelper $integrationHelper */
                $integrationHelper = $this->factory->getHelper('integration');
                $socialProfiles    = $integrationHelper->getUserProfiles($lead, $fields, true, $network);
                $socialProfileUrls = $integrationHelper->getSocialProfileUrlRegex(false);
                $integrations      = array();
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
                        if ($integrationObject = $integrationHelper->getIntegrationObject($name)) {
                            if ($template = $integrationObject->getSocialProfileTemplate()) {
                                $integrations[$name]['newContent'] = $this->renderView(
                                    $template,
                                    array(
                                        'lead'              => $lead,
                                        'details'           => $details,
                                        'integrationName'   => $name,
                                        'socialProfileUrls' => $socialProfileUrls
                                    )
                                );
                            }
                        }
                    }
                    $dataArray['profiles'] = $integrations;
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
            $model = $this->getModel('lead.lead');
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
            $model = $this->getModel('lead.lead');
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

                $timeline = $this->renderView('MauticLeadBundle:Lead:history.html.php', array(
                        'events'       => $events,
                        'eventTypes'   => $eventTypes,
                        'eventFilters' => $filter,
                        'lead'         => $lead
                    )
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
            $leadModel = $this->getModel('lead');
            $listModel = $this->getModel('lead.list');

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
            $leadModel     = $this->getModel('lead');
            $campaignModel = $this->getModel('campaign');

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
            /** @var \Mautic\LeadBundle\Model\LeadModel $model */
            $model = $this->getModel('lead');
            /** @var \Mautic\LeadBundle\Entity\DoNotContact $dnc */
            $dnc = $this->getEntityManager()->getRepository('MauticLeadBundle:DoNotContact')->findOneBy(
                array(
                    'id' => $dncId
                )
            );

            $lead = $dnc->getLead();
            if ($lead) {
                // Use lead model to trigger listeners
                $model->removeDncForLead($lead, 'email');
            } else {
                $this->getModel('email')->getRepository()->deleteDoNotEmailEntry($dncId);
            }

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
            $model   = $this->getModel('lead.lead');
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
                $emailRepo = $this->getModel('email')->getRepository();
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
        $model    = $this->getModel('email');

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

    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    protected function updateLeadTagsAction(Request $request)
    {
        /** @var \Mautic\LeadBundle\Model\LeadModel $leadModel */
        $leadModel   = $this->getModel('lead');
        $post        = $request->request->get('lead_tags', array(), true);
        $lead        = $leadModel->getEntity((int) $post['id']);
        $updatedTags = (!empty($post['tags']) && is_array($post['tags'])) ? $post['tags'] : array();
        $data        = array('success' => 0);

        if ($lead !== null && $this->factory->getSecurity()->hasEntityAccess('lead:leads:editown', 'lead:leads:editother', $lead->getOwner())) {

            $leadModel->setTags($lead, $updatedTags, true);

            /** @var \Doctrine\ORM\PersistentCollection $leadTags */
            $leadTags    = $lead->getTags();
            $leadTagKeys = $leadTags->getKeys();

            // Get an updated list of tags
            $tags       = $leadModel->getTagRepository()->getSimpleList(null, array(), 'tag');
            $tagOptions = '';

            foreach ($tags as $tag) {
                $selected = (in_array($tag['label'], $leadTagKeys)) ? ' selected="selected"' : '';
                $tagOptions .= '<option' . $selected. ' value="' . $tag['value'] . '">' . $tag['label'] . '</option>';
            }

            $data['success'] = 1;
            $data['tags'] = $tagOptions;
        }

        return $this->sendJsonResponse($data);
    }

    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    protected function addLeadTagsAction(Request $request)
    {
        $tags = $request->request->get('tags');
        $tags = json_decode($tags, true);

        if (is_array($tags)) {
            $newTags = array();
            foreach ($tags as $tag) {
                if (!is_numeric($tag)) {
                    // New tag
                    $tagEntity = new Tag();
                    $tagEntity->setTag(InputHelper::clean($tag));
                    $newTags[] = $tagEntity;
                }
            }

            $leadModel = $this->getModel('lead');

            if (!empty($newTags)) {
                $leadModel->getTagRepository()->saveEntities($newTags);
            }

            // Get an updated list of tags
            $allTags    = $leadModel->getTagRepository()->getSimpleList(null, array(), 'tag');
            $tagOptions = '';

            foreach ($allTags as $tag) {
                $selected = (in_array($tag['value'], $tags) || in_array($tag['label'], $tags)) ? ' selected="selected"' : '';
                $tagOptions .= '<option'.$selected.' value="'.$tag['value'].'">'.$tag['label'].'</option>';
            }

            $data = array(
                'success' => 1,
                'tags'    => $tagOptions
            );
        } else {
            $data = array('success' => 0);
        }

        return $this->sendJsonResponse($data);
    }

    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    protected function addLeadUtmTagsAction(Request $request)
    {
        $utmTags = $request->request->get('utmtags');
        $utmTags = json_decode($utmTags, true);

        if (is_array($utmTags)) {
            $newUtmTags = array();
            foreach ($utmTags as $utmTag) {
                if (!is_numeric($utmTag)) {
                    // New tag
                    $utmTagEntity = new UtmTag();
                    $utmTagEntity->setUtmTag(InputHelper::clean($utmTag));
                    $newUtmTags[] = $utmTagEntity;
                }
            }

            $leadModel = $this->factory->getModel('lead');

            if (!empty($newUtmTags)) {
                $leadModel->getUtmTagRepository()->saveEntities($newUtmTags);
            }

            // Get an updated list of tags
            $allUtmTags    = $leadModel->getUtmTagRepository()->getSimpleList(null, array(), 'utmtag');
            $utmTagOptions = '';

            foreach ($allUtmTags as $utmTag) {
                $selected = (in_array($utmTag['value'], $utmTags) || in_array($utmTag['label'], $utmTags)) ? ' selected="selected"' : '';
                $utmTagOptions .= '<option'.$selected.' value="'.$utmTag['value'].'">'.$utmTag['label'].'</option>';
            }

            $data = array(
                'success' => 1,
                'tags'    => $utmTagOptions
            );
        } else {
            $data = array('success' => 0);
        }

        return $this->sendJsonResponse($data);
    }

    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    protected function reorderAction(Request $request)
    {
        $dataArray   = array('success' => 0);
        $order       = InputHelper::clean($request->request->get('field'));
        $page        = InputHelper::int($request->get('page'));
        $limit       = InputHelper::int($request->get('limit'));

        if (!empty($order)) {
            /** @var \Mautic\LeadBundle\Model\FieldModel $model */
            $model = $this->getModel('lead.field');

            $startAt = ($page > 1) ? ($page * $limit) + 1 : 1;
            $model->reorderFieldsByList($order, $startAt);
            $dataArray['success'] = 1;
        }

        return $this->sendJsonResponse($dataArray);
    }

    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    protected function updateLeadFieldValuesAction(Request $request)
    {
        $alias       = InputHelper::clean($request->request->get('alias'));
        $dataArray   = array('success' => 0, 'options' => null);
        $leadField   = $this->getModel('lead.field')->getRepository()->findOneBy(array('alias' => $alias));
        $choiceTypes = array('boolean', 'country', 'region', 'lookup', 'timezone', 'select', 'radio');

        if ($leadField && in_array($leadField->getType(), $choiceTypes)) {
            $properties = $leadField->getProperties();
            $fieldType  = $leadField->getType();
            $options    = [];
            if (!empty($properties['list'])) {
                // Lookup/Select options
                $options = explode('|', $properties['list']);
                $options = array_combine($options, $options);
            } elseif (!empty($properties) && $fieldType == 'boolean') {
                // Boolean options
                $options = array(
                    0 => $properties['no'],
                    1 => $properties['yes']
                );
            } elseif (!empty($properties)) {
                // fallback
                $options = $properties;
            }
            $dataArray['options'] = $options;
        }

        $dataArray['success']  = 1;

        return $this->sendJsonResponse($dataArray);
    }
}
