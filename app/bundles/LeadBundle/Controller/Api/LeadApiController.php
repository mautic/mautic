<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Controller\Api;

use FOS\RestBundle\Util\Codes;
use JMS\Serializer\SerializationContext;
use Mautic\ApiBundle\Controller\CommonApiController;
use Mautic\CoreBundle\Helper\DateTimeHelper;
use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\LeadBundle\Controller\FrequencyRuleTrait;
use Mautic\LeadBundle\Controller\LeadDetailsTrait;
use Mautic\LeadBundle\Entity\DoNotContact;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\LeadModel;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;

/**
 * Class LeadApiController.
 *
 * @property LeadModel $model
 */
class LeadApiController extends CommonApiController
{
    use CustomFieldsApiControllerTrait;
    use FrequencyRuleTrait;
    use LeadDetailsTrait;

    const MODEL_ID = 'lead.lead';

    /**
     * @param FilterControllerEvent $event
     */
    public function initialize(FilterControllerEvent $event)
    {
        $this->model            = $this->getModel(self::MODEL_ID);
        $this->entityClass      = Lead::class;
        $this->entityNameOne    = 'contact';
        $this->entityNameMulti  = 'contacts';
        $this->serializerGroups = ['leadDetails', 'frequencyRulesList', 'doNotContactList', 'userList', 'stageList', 'publishDetails', 'ipAddress', 'tagList', 'utmtagsList'];

        parent::initialize($event);
    }

    /**
     * Creates new entity from provided params.
     *
     * @param array $parameters
     *
     * @return Lead
     */
    public function getNewEntity(array $parameters)
    {
        return $this->getModel(self::MODEL_ID)->checkForDuplicateContact($parameters);
    }

    /**
     * Get existing duplicated contact based on unique fields and the request data.
     *
     * @param array $parameters
     * @param null  $id
     *
     * @return null|Lead
     *
     * @deprecated since 2.12.2, to be removed in 3.0.0. Use $model->checkForDuplicateContact directly instead
     */
    protected function getExistingLead(array $parameters, $id = null)
    {
        $model   = $this->getModel(self::MODEL_ID);
        $contact = $id ? $model->getEntity($id) : null;

        return $model->checkForDuplicateContact($parameters, $contact);
    }

    /**
     * Obtains a list of users for lead owner edits.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getOwnersAction()
    {
        if (!$this->get('mautic.security')->isGranted(
            ['lead:leads:create', 'lead:leads:editown', 'lead:leads:editother'],
            'MATCH_ONE'
        )
        ) {
            return $this->accessDenied();
        }

        $filter  = $this->request->query->get('filter', null);
        $limit   = $this->request->query->get('limit', null);
        $start   = $this->request->query->get('start', null);
        $users   = $this->model->getLookupResults('user', $filter, $limit, $start);
        $view    = $this->view($users, Codes::HTTP_OK);
        $context = SerializationContext::create()->setGroups(['userList']);
        $view->setSerializationContext($context);

        return $this->handleView($view);
    }

    /**
     * Obtains a list of custom fields.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getFieldsAction()
    {
        if (!$this->get('mautic.security')->isGranted(['lead:leads:editown', 'lead:leads:editother'], 'MATCH_ONE')) {
            return $this->accessDenied();
        }

        $fields = $this->getModel('lead.field')->getEntities(
            [
                'filter' => [
                    'force' => [
                        [
                            'column' => 'f.isPublished',
                            'expr'   => 'eq',
                            'value'  => true,
                            'object' => 'lead',
                        ],
                    ],
                ],
            ]
        );

        $view    = $this->view($fields, Codes::HTTP_OK);
        $context = SerializationContext::create()->setGroups(['leadFieldList']);
        $view->setSerializationContext($context);

        return $this->handleView($view);
    }

    /**
     * Obtains a list of notes on a specific lead.
     *
     * @param $id
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getNotesAction($id)
    {
        $entity = $this->model->getEntity($id);

        if ($entity === null) {
            return $this->notFound();
        }

        if (!$this->get('mautic.security')->hasEntityAccess('lead:leads:viewown', 'lead:leads:viewother', $entity->getPermissionUser())) {
            return $this->accessDenied();
        }

        $results = $this->getModel('lead.note')->getEntities(
            [
                'start'  => $this->request->query->get('start', 0),
                'limit'  => $this->request->query->get('limit', $this->coreParametersHelper->getParameter('default_pagelimit')),
                'filter' => [
                    'string' => $this->request->query->get('search', ''),
                    'force'  => [
                        [
                            'column' => 'n.lead',
                            'expr'   => 'eq',
                            'value'  => $entity,
                        ],
                    ],
                ],
                'orderBy'    => $this->request->query->get('orderBy', 'n.dateAdded'),
                'orderByDir' => $this->request->query->get('orderByDir', 'DESC'),
            ]
        );

        list($notes, $count) = $this->prepareEntitiesForView($results);

        $view = $this->view(
            [
                'total' => $count,
                'notes' => $notes,
            ],
            Codes::HTTP_OK
        );

        $context = SerializationContext::create()->setGroups(['leadNoteDetails']);
        $view->setSerializationContext($context);

        return $this->handleView($view);
    }

    /**
     * Obtains a list of devices on a specific lead.
     *
     * @param $id
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getDevicesAction($id)
    {
        $entity = $this->model->getEntity($id);

        if ($entity === null) {
            return $this->notFound();
        }

        if (!$this->get('mautic.security')->hasEntityAccess('lead:leads:viewown', 'lead:leads:viewother', $entity->getPermissionUser())) {
            return $this->accessDenied();
        }

        $results = $this->getModel('lead.device')->getEntities(
            [
                'start'  => $this->request->query->get('start', 0),
                'limit'  => $this->request->query->get('limit', $this->coreParametersHelper->getParameter('default_pagelimit')),
                'filter' => [
                    'string' => $this->request->query->get('search', ''),
                    'force'  => [
                        [
                            'column' => 'd.lead',
                            'expr'   => 'eq',
                            'value'  => $entity,
                        ],
                    ],
                ],
                'orderBy'    => $this->request->query->get('orderBy', 'd.dateAdded'),
                'orderByDir' => $this->request->query->get('orderByDir', 'DESC'),
            ]
        );

        list($devices, $count) = $this->prepareEntitiesForView($results);

        $view = $this->view(
            [
                'total'   => $count,
                'devices' => $devices,
            ],
            Codes::HTTP_OK
        );

        $context = SerializationContext::create()->setGroups(['leadDeviceDetails']);
        $view->setSerializationContext($context);

        return $this->handleView($view);
    }

    /**
     * Obtains a list of contact segments the contact is in.
     *
     * @param $id
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getListsAction($id)
    {
        $entity = $this->model->getEntity($id);
        if ($entity !== null) {
            if (!$this->get('mautic.security')->hasEntityAccess('lead:leads:viewown', 'lead:leads:viewother', $entity->getPermissionUser())) {
                return $this->accessDenied();
            }

            $lists = $this->model->getLists($entity, true, true);

            foreach ($lists as &$l) {
                unset($l['leads'][0]['leadlist_id']);
                unset($l['leads'][0]['lead_id']);

                $l = array_merge($l, $l['leads'][0]);

                unset($l['leads']);
            }

            $view = $this->view(
                [
                    'total' => count($lists),
                    'lists' => $lists,
                ],
                Codes::HTTP_OK
            );

            return $this->handleView($view);
        }

        return $this->notFound();
    }

    /**
     * Obtains a list of contact companies the contact is in.
     *
     * @param $id
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getCompaniesAction($id)
    {
        $entity = $this->model->getEntity($id);

        if ($entity === null) {
            return $this->notFound();
        }

        if (!$this->get('mautic.security')->hasEntityAccess('lead:leads:viewown', 'lead:leads:viewother', $entity->getPermissionUser())) {
            return $this->accessDenied();
        }

        $companies = $this->model->getCompanies($entity);

        $view = $this->view(
            [
                'total'     => count($companies),
                'companies' => $companies,
            ],
            Codes::HTTP_OK
        );

        return $this->handleView($view);
    }

    /**
     * Obtains a list of campaigns the lead is part of.
     *
     * @param $id
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getCampaignsAction($id)
    {
        $entity = $this->model->getEntity($id);
        if ($entity !== null) {
            if (!$this->get('mautic.security')->hasEntityAccess('lead:leads:viewown', 'lead:leads:viewother', $entity->getPermissionUser())) {
                return $this->accessDenied();
            }

            /** @var \Mautic\CampaignBundle\Model\CampaignModel $campaignModel */
            $campaignModel = $this->getModel('campaign');
            $campaigns     = $campaignModel->getLeadCampaigns($entity, true);

            foreach ($campaigns as &$c) {
                if (!empty($c['lists'])) {
                    $c['listMembership'] = array_keys($c['lists']);
                    unset($c['lists']);
                }

                unset($c['leads'][0]['campaign_id']);
                unset($c['leads'][0]['lead_id']);

                $c = array_merge($c, $c['leads'][0]);

                unset($c['leads']);
            }

            $view = $this->view(
                [
                    'total'     => count($campaigns),
                    'campaigns' => $campaigns,
                ],
                Codes::HTTP_OK
            );

            return $this->handleView($view);
        }

        return $this->notFound();
    }

    /**
     * Obtains a list of contact events.
     *
     * @param $id
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getActivityAction($id)
    {
        $entity = $this->model->getEntity($id);

        if ($entity === null) {
            return $this->notFound();
        }

        if (!$this->checkEntityAccess($entity, 'view')) {
            return $this->accessDenied();
        }

        return $this->getAllActivityAction($entity);
    }

    /**
     * Obtains a list of contact events.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getAllActivityAction($lead = null)
    {
        $canViewOwn    = $this->security->isGranted('lead:leads:viewown');
        $canViewOthers = $this->security->isGranted('lead:leads:viewother');

        if (!$canViewOthers && !$canViewOwn) {
            return $this->accessDenied();
        }

        $filters = $this->sanitizeEventFilter(InputHelper::clean($this->request->get('filters', [])));
        $limit   = (int) $this->request->get('limit', 25);
        $page    = (int) $this->request->get('page', 1);
        $order   = InputHelper::clean($this->request->get('order', ['timestamp', 'DESC']));

        list($events, $serializerGroups) = $this->model->getEngagements($lead, $filters, $order, $page, $limit, false);

        $view    = $this->view($events);
        $context = SerializationContext::create()->setGroups($serializerGroups);
        $view->setSerializationContext($context);

        return $this->handleView($view);
    }

    /**
     * Adds a DNC to the contact.
     *
     * @param $id
     * @param $channel
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function addDncAction($id, $channel)
    {
        $entity = $this->model->getEntity((int) $id);

        if ($entity === null) {
            return $this->notFound();
        }

        if (!$this->checkEntityAccess($entity, 'edit')) {
            return $this->accessDenied();
        }

        $channelId = (int) $this->request->request->get('channelId');
        if ($channelId) {
            $channel = [$channel, $channelId];
        }
        $reason   = (int) $this->request->request->get('reason');
        $comments = InputHelper::clean($this->request->request->get('comments'));

        $this->model->addDncForLead($entity, $channel, $comments, $reason);
        $view = $this->view([$this->entityNameOne => $entity]);

        return $this->handleView($view);
    }

    /**
     * Removes a DNC from the contact.
     *
     * @param $id
     * @param $channel
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function removeDncAction($id, $channel)
    {
        $entity = $this->model->getEntity((int) $id);

        if ($entity === null) {
            return $this->notFound();
        }

        if (!$this->checkEntityAccess($entity, 'edit')) {
            return $this->accessDenied();
        }

        $result = $this->model->removeDncForLead($entity, $channel);
        $view   = $this->view(
            [
                'recordFound'        => $result,
                $this->entityNameOne => $entity,
            ]
        );

        return $this->handleView($view);
    }

    /**
     * Add/Remove a UTM Tagset to/from the contact.
     *
     * @param int       $id
     * @param string    $method
     * @param array/int $data
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function applyUtmTagsAction($id, $method, $data)
    {
        $entity = $this->model->getEntity((int) $id);

        if ($entity === null) {
            return $this->notFound();
        }

        if (!$this->checkEntityAccess($entity, 'edit')) {
            return $this->accessDenied();
        }

        // calls add/remove method as appropriate
        $result = $this->model->$method($entity, $data);

        if ($result === false) {
            return $this->badRequest();
        }

        if ('removeUtmTags' == $method) {
            $view = $this->view(
                [
                    'recordFound'        => $result,
                    $this->entityNameOne => $entity,
                ]
            );
        } else {
            $view = $this->view([$this->entityNameOne => $entity]);
        }

        return $this->handleView($view);
    }

    /**
     * Adds a UTM Tagset to the contact.
     *
     * @param int $id
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function addUtmTagsAction($id)
    {
        return $this->applyUtmTagsAction($id, 'addUTMTags', $this->request->request->all());
    }

    /**
     * Remove a UTM Tagset for the contact.
     *
     * @param int $id
     * @param int $utmid
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function removeUtmTagsAction($id, $utmid)
    {
        return $this->applyUtmTagsAction($id, 'removeUtmTags', (int) $utmid);
    }

    /**
     * Obtains a list of contact events.
     *
     * @deprecated 2.10.0 to be removed in 3.0
     *
     * @param $id
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getEventsAction($id)
    {
        $entity = $this->model->getEntity($id);

        if ($entity === null) {
            return $this->notFound();
        }

        if (!$this->checkEntityAccess($entity, 'view')) {
            return $this->accessDenied();
        }

        $filters = $this->sanitizeEventFilter(InputHelper::clean($this->request->get('filters', [])));
        $order   = InputHelper::clean($this->request->get('order', ['timestamp', 'DESC']));
        $page    = (int) $this->request->get('page', 1);
        $events  = $this->model->getEngagements($entity, $filters, $order, $page);

        return $this->handleView($this->view($events));
    }

    /**
     * {@inheritdoc}
     */
    protected function prepareParametersForBinding($parameters, $entity, $action)
    {
        // Unset the tags from params to avoid a validation error
        if (isset($parameters['tags'])) {
            unset($parameters['tags']);
        }

        return $parameters;
    }

    /**
     * {@inheritdoc}
     *
     * @param Lead   $entity
     * @param array  $parameters
     * @param        $form
     * @param string $action
     */
    protected function preSaveEntity(&$entity, $form, $parameters, $action = 'edit')
    {
        $originalParams = $this->request->request->all();

        // Merge existing duplicate contact based on unique fields if exist
        $existingContact = $this->model->checkForDuplicateContact($originalParams, $entity);
        if ($action === 'edit' && $existingContact->getId()) {
            $this->model->mergeLeads($existingContact, $entity, false);
        }

        if (isset($parameters['companies'])) {
            $this->model->modifyCompanies($entity, $parameters['companies']);
            unset($parameters['companies']);
        }

        if (isset($parameters['owner'])) {
            $owner = $this->getModel('user.user')->getEntity((int) $parameters['owner']);
            $entity->setOwner($owner);
            unset($parameters['owner']);
        }

        if (isset($parameters['stage'])) {
            $stage = $this->getModel('stage.stage')->getEntity((int) $parameters['stage']);
            $entity->setStage($stage);
            unset($parameters['stage']);
        }

        if (isset($parameters['color'])) {
            $entity->setColor($parameters['color']);
            unset($parameters['color']);
        }

        if (isset($originalParams['tags'])) {
            $this->model->modifyTags($entity, $originalParams['tags'], null, false);
        }

        //Since the request can be from 3rd party, check for an IP address if included
        if (isset($originalParams['ipAddress'])) {
            $ipAddress = $this->factory->getIpAddress($originalParams['ipAddress']);

            if (!$entity->getIpAddresses()->contains($ipAddress)) {
                $entity->addIpAddress($ipAddress);
            }

            unset($originalParams['ipAddress']);
        }

        // Check for lastActive date
        if (isset($originalParams['lastActive'])) {
            $lastActive = new DateTimeHelper($originalParams['lastActive']);
            $entity->setLastActive($lastActive->getDateTime());
            unset($originalParams['lastActive']);
        }

        if (!empty($parameters['doNotContact']) && is_array($parameters['doNotContact'])) {
            foreach ($parameters['doNotContact'] as $dnc) {
                $channel  = !empty($dnc['channel']) ? $dnc['channel'] : 'email';
                $comments = !empty($dnc['comments']) ? $dnc['comments'] : '';
                $reason   = !empty($dnc['reason']) ? $dnc['reason'] : DoNotContact::MANUAL;
                $this->model->addDncForLead($entity, $channel, $comments, $reason, false);
            }
            unset($parameters['doNotContact']);
        }

        if (!empty($parameters['frequencyRules'])) {
            $viewParameters = [];
            $data           = $this->getFrequencyRuleFormData($entity, null, null, false, $parameters['frequencyRules']);

            if (!$frequencyForm = $this->getFrequencyRuleForm($entity, $viewParameters, $data)) {
                $formErrors = $this->getFormErrorMessages($frequencyForm);
                $msg        = $this->getFormErrorMessage($formErrors);

                if (!$msg) {
                    $msg = $this->translator->trans('mautic.core.error.badrequest', [], 'flashes');
                }

                return $this->returnError($msg, Codes::HTTP_BAD_REQUEST, $formErrors);
            }

            unset($parameters['frequencyRules']);
        }

        $this->setCustomFieldValues($entity, $form, $parameters);
    }

    /**
     * Helper method to be used in FrequencyRuleTrait.
     *
     * @param Form $form
     *
     * @return bool
     */
    protected function isFormCancelled($form = null)
    {
        return false;
    }

    /**
     * Helper method to be used in FrequencyRuleTrait.
     *
     * @param Form  $form
     * @param array $data
     *
     * @return bool
     */
    protected function isFormValid(Form $form, array $data = null)
    {
        $form->submit($data, 'PATCH' !== $this->request->getMethod());

        return $form->isValid();
    }
}
