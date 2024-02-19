<?php

namespace Mautic\LeadBundle\Controller;

use Mautic\CampaignBundle\Membership\MembershipManager;
use Mautic\CampaignBundle\Model\CampaignModel;
use Mautic\CoreBundle\Controller\AjaxController as CommonAjaxController;
use Mautic\CoreBundle\Controller\AjaxLookupControllerTrait;
use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\CoreBundle\Helper\Tree\JsPlumbFormatter;
use Mautic\EmailBundle\Model\EmailModel;
use Mautic\LeadBundle\Entity\DoNotContact;
use Mautic\LeadBundle\Entity\UtmTag;
use Mautic\LeadBundle\Form\Type\FilterPropertiesType;
use Mautic\LeadBundle\Helper\FormFieldHelper;
use Mautic\LeadBundle\Model\CompanyModel;
use Mautic\LeadBundle\Model\DoNotContact as DoNotContactModel;
use Mautic\LeadBundle\Model\FieldModel;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\LeadBundle\Model\ListModel;
use Mautic\LeadBundle\Provider\FormAdjustmentsProviderInterface;
use Mautic\LeadBundle\Segment\Stat\SegmentCampaignShare;
use Mautic\LeadBundle\Services\ContactColumnsDictionary;
use Mautic\LeadBundle\Services\SegmentDependencyTreeFactory;
use Mautic\PluginBundle\Helper\IntegrationHelper;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class AjaxController extends CommonAjaxController
{
    use AjaxLookupControllerTrait;

    public function userListAction(Request $request): JsonResponse
    {
        $filter    = InputHelper::clean($request->query->get('filter'));
        $leadModel = $this->getModel('lead.lead');
        \assert($leadModel instanceof LeadModel);
        $results   = $leadModel->getLookupResults('user', $filter);
        $dataArray = [];
        foreach ($results as $r) {
            $name        = $r['firstName'].' '.$r['lastName'];
            $dataArray[] = [
                'label' => $name,
                'value' => $r['id'],
            ];
        }

        return $this->sendJsonResponse($dataArray);
    }

    public function getLeadIdsByFieldValueAction(Request $request): JsonResponse
    {
        $field     = InputHelper::clean($request->query->get('field'));
        $value     = InputHelper::clean($request->query->get('value'));
        $ignore    = (int) $request->query->get('ignore');
        $dataArray = ['items' => []];

        if ($field && $value) {
            $leadModel = $this->getModel('lead.lead');
            \assert($leadModel instanceof LeadModel);
            $repo                       = $leadModel->getRepository();
            $leads                      = $repo->getLeadsByFieldValue($field, $value, $ignore);
            $dataArray['existsMessage'] = $this->translator->trans('mautic.lead.exists.by.field').': ';

            foreach ($leads as $lead) {
                $fields = $repo->getFieldValues($lead->getId());
                $lead->setFields($fields);
                $name = $lead->getName();

                if (!$name) {
                    $name = $lead->getEmail();
                }

                if (!$name) {
                    $name = $this->translator->trans('mautic.lead.lead.anonymous');
                }

                $leadLink = $this->generateUrl('mautic_contact_action', ['objectAction' => 'view', 'objectId' => $lead->getId()]);

                $dataArray['items'][] = [
                    'name' => $name,
                    'id'   => $lead->getId(),
                    'link' => $leadLink,
                ];
            }
        }

        return $this->sendJsonResponse($dataArray);
    }

    public function fieldListAction(Request $request): JsonResponse
    {
        $dataArray  = ['success' => 1];
        $filter     = InputHelper::clean($request->query->get('filter'));
        $fieldAlias = InputHelper::alphanum($request->query->get('field'), false, null, ['_']);

        /** @var FieldModel $fieldModel */
        $fieldModel = $this->getModel('lead.field');

        /** @var LeadModel $contactModel */
        $contactModel = $this->getModel('lead.lead');

        /** @var CompanyModel $companyModel */
        $companyModel = $this->getModel('lead.company');

        if (empty($fieldAlias)) {
            $dataArray['error']   = 'Alias cannot be empty';
            $dataArray['success'] = 0;

            return $this->sendJsonResponse($dataArray);
        }

        if ('owner_id' === $fieldAlias) {
            $results = $contactModel->getLookupResults('user', $filter);
            foreach ($results as $r) {
                $name        = $r['firstName'].' '.$r['lastName'];
                $dataArray[] = [
                    'value' => $name,
                    'id'    => $r['id'],
                ];
            }

            return $this->sendJsonResponse($dataArray);
        }

        $field      = $fieldModel->getEntityByAlias($fieldAlias);
        $isBehavior = empty($field);

        if ($isBehavior) {
            return $this->sendJsonResponse($dataArray);
        }

        // Selet field types that make sense to provide typeahead for.
        $isLookup     = in_array($field->getType(), ['lookup']);
        $shouldLookup = in_array($field->getAlias(), ['city', 'company', 'title']);

        if (!$isLookup && !$shouldLookup) {
            return $this->sendJsonResponse($dataArray);
        }

        if ($isLookup && !empty($field->getProperties()['list'])) {
            foreach ($field->getProperties()['list'] as $predefinedValue) {
                $dataArray[] = ['value' => $predefinedValue];
            }
        }

        if ('company' === $field->getObject()) {
            $results = $companyModel->getLookupResults('companyfield', [$fieldAlias, $filter]);
            foreach ($results as $r) {
                $dataArray[] = ['value' => $r['label']];
            }
        } elseif ('lead' === $field->getObject()) {
            $results = $fieldModel->getLookupResults($fieldAlias, $filter);
            foreach ($results as $r) {
                $dataArray[] = ['value' => $r[$fieldAlias]];
            }
        }

        return $this->sendJsonResponse($dataArray);
    }

    public function loadSegmentFilterFormAction(
        Request $request,
        FormFactoryInterface $formFactory,
        FormAdjustmentsProviderInterface $formAdjustmentsProvider,
        ListModel $listModel
    ): JsonResponse {
        $fieldAlias  = InputHelper::clean($request->request->get('fieldAlias'));
        $fieldObject = InputHelper::clean($request->request->get('fieldObject'));
        $operator    = InputHelper::clean($request->request->get('operator'));
        $search      = InputHelper::clean($request->request->get('search'));
        $filterNum   = (int) $request->request->get('filterNum');

        $form = $formFactory->createNamed('RENAME', FilterPropertiesType::class);

        if ($fieldAlias && $operator) {
            $formAdjustmentsProvider->adjustForm(
                $form,
                $fieldAlias,
                $fieldObject,
                $operator,
                $listModel->getChoiceFields($search)[$fieldObject][$fieldAlias]
            );
        }

        $formHtml = $this->renderView(
            '@MauticLead/List/filterpropform.html.twig',
            [
                // 'form' => $this->setFormTheme($form, '@MauticLead/List/filterpropform.html.twig', []),
                'form' => $form->createView(),
            ]
        );

        $formHtml = str_replace('id="RENAME', "id=\"leadlist_filters_{$filterNum}_properties", $formHtml);
        $formHtml = str_replace('name="RENAME', "name=\"leadlist[filters][{$filterNum}][properties]", $formHtml);

        return $this->sendJsonResponse(
            [
                'viewParameters' => [
                    'form' => $formHtml,
                ],
            ]
        );
    }

    /**
     * Updates the cache and gets returns updated HTML.
     */
    public function updateSocialProfileAction(Request $request): JsonResponse
    {
        $dataArray = ['success' => 0];
        $network   = InputHelper::clean($request->request->get('network'));
        $leadId    = InputHelper::clean($request->request->get('lead'));

        if (!empty($leadId)) {
            // find the lead
            $model = $this->getModel('lead.lead');
            $lead  = $model->getEntity($leadId);

            if (null !== $lead && $this->security->hasEntityAccess('lead:leads:editown', 'lead:leads:editown', $lead->getPermissionUser())) {
                $leadFields = $lead->getFields();
                /** @var IntegrationHelper $integrationHelper */
                $integrationHelper = $this->factory->getHelper('integration');
                $socialProfiles    = $integrationHelper->getUserProfiles($lead, $leadFields, true, $network);
                $socialProfileUrls = $integrationHelper->getSocialProfileUrlRegex(false);
                $integrations      = [];
                $socialCount       = count($socialProfiles);
                if (empty($network) || empty($socialCount)) {
                    $dataArray['completeProfile'] = $this->renderView(
                        '@MauticLead/Social/index.html.twig',
                        [
                            'socialProfiles'    => $socialProfiles,
                            'lead'              => $lead,
                            'socialProfileUrls' => $socialProfileUrls,
                        ]
                    );
                    $dataArray['socialCount'] = $socialCount;
                } else {
                    foreach ($socialProfiles as $name => $details) {
                        if ($integrationObject = $integrationHelper->getIntegrationObject($name)) {
                            if ($template = $integrationObject->getSocialProfileTemplate()) {
                                $integrations[$name]['newContent'] = $this->renderView(
                                    $template,
                                    [
                                        'lead'              => $lead,
                                        'details'           => $details,
                                        'integrationName'   => $name,
                                        'socialProfileUrls' => $socialProfileUrls,
                                    ]
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
     * Clears the cache for a network.
     */
    public function clearSocialProfileAction(Request $request): JsonResponse
    {
        $dataArray = ['success' => 0];
        $network   = InputHelper::clean($request->request->get('network'));
        $leadId    = InputHelper::clean($request->request->get('lead'));

        if (!empty($leadId)) {
            // find the lead
            $model = $this->getModel('lead.lead');
            $lead  = $model->getEntity($leadId);

            if (null !== $lead && $this->security->hasEntityAccess('lead:leads:editown', 'lead:leads:editown', $lead->getPermissionUser())) {
                $dataArray['success'] = 1;
                /** @var \Mautic\PluginBundle\Helper\IntegrationHelper $helper */
                $helper         = $this->factory->getHelper('integration');
                $socialProfiles = $helper->clearIntegrationCache($lead, $network);
                $socialCount    = count($socialProfiles);

                if (empty($socialCount)) {
                    $dataArray['completeProfile'] = $this->renderView(
                        '@MauticLead/Social/index.html.twig',
                        [
                            'socialProfiles'    => $socialProfiles,
                            'lead'              => $lead,
                            'socialProfileUrls' => $helper->getSocialProfileUrlRegex(false),
                        ]
                    );
                }

                $dataArray['socialCount'] = $socialCount;
            }
        }

        return $this->sendJsonResponse($dataArray);
    }

    public function toggleLeadListAction(Request $request): JsonResponse
    {
        $dataArray = ['success' => 0];
        $leadId    = (int) $request->request->get('leadId');
        $listId    = (int) $request->request->get('listId');
        $action    = InputHelper::clean($request->request->get('listAction'));

        if (!empty($leadId) && !empty($listId) && in_array($action, ['remove', 'add'])) {
            $leadModel = $this->getModel('lead');
            $listModel = $this->getModel('lead.list');

            $lead = $leadModel->getEntity($leadId);
            $list = $listModel->getEntity($listId);

            if (null !== $lead && null !== $list) {
                $class = 'add' == $action ? 'addToLists' : 'removeFromLists';
                $leadModel->$class($lead, $list);
                $dataArray['success'] = 1;
            }
        }

        return $this->sendJsonResponse($dataArray);
    }

    public function togglePreferredLeadChannelAction(Request $request): JsonResponse
    {
        $dataArray = ['success' => 0];
        $leadId    = (int) $request->request->get('leadId');
        $channel   = InputHelper::clean($request->request->get('channel'));
        $action    = InputHelper::clean($request->request->get('channelAction'));

        if (!empty($leadId) && !empty($channel) && in_array($action, ['remove', 'add'])) {
            $leadModel = $this->getModel('lead');
            /** @var DoNotContactModel $doNotContact */
            $doNotContact = $this->getModel('lead.dnc');

            $lead = $leadModel->getEntity($leadId);

            if (null !== $lead && null !== $channel) {
                if ('remove' === $action) {
                    $doNotContact->addDncForContact($leadId, $channel, DoNotContact::MANUAL, 'user');
                } elseif ('add' === $action) {
                    $doNotContact->removeDncForContact($leadId, $channel);
                }
                $dataArray['success'] = 1;
            }
        }

        return $this->sendJsonResponse($dataArray);
    }

    public function toggleLeadCampaignAction(Request $request, MembershipManager $membershipManager): JsonResponse
    {
        $dataArray  = ['success' => 0];
        $leadId     = (int) $request->request->get('leadId');
        $campaignId = (int) $request->request->get('campaignId');
        $action     = InputHelper::clean($request->request->get('campaignAction'));

        if (empty($leadId) || empty($campaignId) || !in_array($action, ['remove', 'add'])) {
            return $this->sendJsonResponse($dataArray);
        }

        /** @var LeadModel $leadModel */
        $leadModel = $this->getModel('lead');

        /** @var CampaignModel $campaignModel */
        $campaignModel = $this->getModel('campaign');

        $lead     = $leadModel->getEntity($leadId);
        $campaign = $campaignModel->getEntity($campaignId);

        if (null === $lead || null === $campaign) {
            return $this->sendJsonResponse($dataArray);
        }

        if ('add' === $action) {
            $membershipManager->addContact($lead, $campaign);
        }

        if ('remove' === $action) {
            $membershipManager->removeContact($lead, $campaign);
        }

        $dataArray['success'] = 1;

        return $this->sendJsonResponse($dataArray);
    }

    public function toggleCompanyLeadAction(Request $request): JsonResponse
    {
        $dataArray = ['success' => 0];
        $leadId    = (int) $request->request->get('leadId');
        $companyId = (int) $request->request->get('companyId');
        $action    = InputHelper::clean($request->request->get('companyAction'));

        if (!empty($leadId) && !empty($companyId) && in_array($action, ['remove', 'add'])) {
            $leadModel    = $this->getModel('lead');
            $companyModel = $this->getModel('lead.company');

            $lead    = $leadModel->getEntity($leadId);
            $company = $companyModel->getEntity($companyId);

            if (null !== $lead && null !== $company) {
                $class = 'add' == $action ? 'addLeadToCompany' : 'removeLeadFromCompany';
                $companyModel->$class($company, $lead);
                $dataArray['success'] = 1;
            }
        }

        return $this->sendJsonResponse($dataArray);
    }

    public function getImportProgressAction(Request $request): JsonResponse
    {
        $dataArray = ['success' => 1];

        if ($this->security->isGranted('lead:leads:create')) {
            $session               = $request->getSession();
            $dataArray['progress'] = $session->get('mautic.lead.import.progress', [0, 0]);
            $dataArray['percent']  = ($dataArray['progress'][1]) ? ceil(($dataArray['progress'][0] / $dataArray['progress'][1]) * 100) : 100;
        }

        return $this->sendJsonResponse($dataArray);
    }

    public function removeBounceStatusAction(Request $request): JsonResponse
    {
        $dataArray   = ['success' => 0];
        $dncId       = $request->request->get('id');
        $channel     = $request->request->get('channel', 'email');

        if (!empty($dncId)) {
            /** @var \Mautic\LeadBundle\Model\LeadModel $model */

            /** @var DoNotContactModel $doNotContact */
            $doNotContact = $this->getModel('lead.dnc');

            /** @var DoNotContactModel $dnc */
            $dnc = $this->doctrine->getManager()->getRepository(\Mautic\LeadBundle\Entity\DoNotContact::class)->findOneBy(
                [
                    'id' => $dncId,
                ]
            );

            $lead = $dnc->getLead();
            if ($lead) {
                // Use lead model to trigger listeners
                $doNotContact->removeDncForContact($lead->getId(), $channel);
            } else {
                $emailModel = $this->getModel('email');
                \assert($emailModel instanceof EmailModel);
                $emailModel->getRepository()->deleteDoNotEmailEntry($dncId);
            }

            $dataArray['success'] = 1;
        }

        return $this->sendJsonResponse($dataArray);
    }

    /**
     * Get the rows for new leads.
     *
     * @return array|\Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function getNewLeadsAction(Request $request, ContactColumnsDictionary $contactColumnsDictionary)
    {
        $dataArray = ['success' => 0];
        $maxId     = $request->get('maxId');

        if (!empty($maxId)) {
            // set some permissions
            $permissions = $this->security->isGranted(
                [
                    'lead:leads:viewown',
                    'lead:leads:viewother',
                    'lead:leads:create',
                    'lead:leads:editown',
                    'lead:leads:editother',
                    'lead:leads:deleteown',
                    'lead:leads:deleteother',
                ],
                'RETURN_ARRAY'
            );

            if (!$permissions['lead:leads:viewown'] && !$permissions['lead:leads:viewother']) {
                return $this->accessDenied(true);
            }

            /** @var \Mautic\LeadBundle\Model\LeadModel $model */
            $model   = $this->getModel('lead.lead');
            $session = $request->getSession();

            $search = $session->get('mautic.lead.filter', '');

            $filter     = ['string' => $search, 'force' => []];
            $translator = $this->translator;
            $anonymous  = $translator->trans('mautic.lead.lead.searchcommand.isanonymous');
            $mine       = $translator->trans('mautic.core.searchcommand.ismine');
            $indexMode  = $session->get('mautic.lead.indexmode', 'list');

            $session->set('mautic.lead.indexmode', $indexMode);

            // (strpos($search, "$isCommand:$anonymous") === false && strpos($search, "$listCommand:") === false)) ||
            if ('list' != $indexMode) {
                // remove anonymous leads unless requested to prevent clutter
                $filter['force'][] = "!$anonymous";
            }

            if (!$permissions['lead:leads:viewother']) {
                $filter['force'][] = $mine;
            }

            $filter['force'][] = [
                'column' => 'l.id',
                'expr'   => 'gt',
                'value'  => $maxId,
            ];

            $results = $model->getEntities(
                [
                    'filter'         => $filter,
                    'withTotalCount' => true,
                ]
            );
            $count = $results['count'];

            if (!empty($count)) {
                // Get the max ID of the latest lead added
                $maxLeadId = $model->getRepository()->getMaxLeadId();

                // We need the EmailRepository to check if a lead is flagged as do not contact
                /** @var \Mautic\EmailBundle\Entity\EmailRepository $emailRepo */
                $emailRepo          = $this->getModel('email')->getRepository();
                $indexMode          = $request->get('view', $session->get('mautic.lead.indexmode', 'list'));
                $template           = ('list' == $indexMode) ? 'list_rows' : 'grid_cards';
                $dataArray['leads'] = $this->render(
                    "@MauticLead/Lead/{$template}.html.twig",
                    [
                        'items'         => $results['results'],
                        'noContactList' => $emailRepo->getDoNotEmailList(array_keys($results['results'])),
                        'permissions'   => $permissions,
                        'security'      => $this->security,
                        'highlight'     => true,
                        'currentList'   => null,
                        'columns'       => $contactColumnsDictionary->getColumns(),
                    ]
                )->getContent();
                $dataArray['indexMode'] = $indexMode;
                $dataArray['maxId']     = $maxLeadId;
                $dataArray['success']   = 1;
            }
        }

        return $this->sendJsonResponse($dataArray);
    }

    public function getEmailTemplateAction(Request $request): JsonResponse
    {
        $data    = ['success' => 1, 'body' => '', 'subject' => ''];
        $emailId = $request->query->get('template');

        /** @var \Mautic\EmailBundle\Model\EmailModel $model */
        $model = $this->getModel('email');

        /** @var \Mautic\EmailBundle\Entity\Email $email */
        $email = $model->getEntity($emailId);

        if (null !== $email
            && $this->security->hasEntityAccess(
                'email:emails:viewown',
                'email:emails:viewother',
                $email->getCreatedBy()
            )
        ) {
            $mailer = $this->factory->getMailer();
            $mailer->setEmail($email, true, [], [], true);

            $data['body']    = $mailer->getBody();
            $data['subject'] = $mailer->getSubject();
        }

        return $this->sendJsonResponse($data);
    }

    public function updateLeadTagsAction(Request $request): JsonResponse
    {
        /** @var \Mautic\LeadBundle\Model\LeadModel $leadModel */
        $leadModel   = $this->getModel('lead');
        $post        = $request->request->all()['lead_tags'] ?? [];
        $lead        = $leadModel->getEntity((int) $post['id']);
        $updatedTags = (!empty($post['tags']) && is_array($post['tags'])) ? $post['tags'] : [];
        $data        = ['success' => 0];

        if (null !== $lead && $this->security->hasEntityAccess('lead:leads:editown', 'lead:leads:editother', $lead->getPermissionUser())) {
            $leadModel->setTags($lead, $updatedTags, true);

            /** @var \Doctrine\ORM\PersistentCollection $leadTags */
            $leadTags    = $lead->getTags();
            $leadTagKeys = $leadTags->getKeys();

            // Get an updated list of tags
            $tags       = $leadModel->getTagRepository()->getSimpleList(null, [], 'tag');
            $tagOptions = '';

            foreach ($tags as $tag) {
                $selected = (in_array($tag['label'], $leadTagKeys)) ? ' selected="selected"' : '';
                $tagOptions .= '<option'.$selected.' value="'.$tag['value'].'">'.$tag['label'].'</option>';
            }

            $data['success'] = 1;
            $data['tags']    = $tagOptions;
        }

        return $this->sendJsonResponse($data);
    }

    public function addLeadTagsAction(Request $request): JsonResponse
    {
        $tags = $request->request->get('tags');
        $tags = json_decode($tags, true);

        if (is_array($tags)) {
            $leadModel = $this->getModel('lead');
            \assert($leadModel instanceof LeadModel);
            $newTags   = [];

            foreach ($tags as $tag) {
                if (!is_numeric($tag)) {
                    $newTags[] = $leadModel->getTagRepository()->getTagByNameOrCreateNewOne($tag);
                }
            }

            if (!empty($newTags)) {
                $leadModel->getTagRepository()->saveEntities($newTags);
            }

            // Get an updated list of tags
            $allTags    = $leadModel->getTagRepository()->getSimpleList(null, [], 'tag');
            $tagOptions = '';

            foreach ($allTags as $tag) {
                $selected = (in_array($tag['value'], $tags) || in_array($tag['label'], $tags)) ? ' selected="selected"' : '';
                $tagOptions .= '<option'.$selected.' value="'.$tag['value'].'">'.$tag['label'].'</option>';
            }

            $data = [
                'success' => 1,
                'tags'    => $tagOptions,
            ];
        } else {
            $data = ['success' => 0];
        }

        return $this->sendJsonResponse($data);
    }

    public function addLeadUtmTagsAction(Request $request): JsonResponse
    {
        $utmTags = $request->request->get('utmtags');
        $utmTags = json_decode($utmTags, true);

        if (is_array($utmTags)) {
            $newUtmTags = [];
            foreach ($utmTags as $utmTag) {
                if (!is_numeric($utmTag)) {
                    // New tag
                    $utmTagEntity = new UtmTag();
                    $utmTagEntity->setUtmTag(InputHelper::clean($utmTag));
                    $newUtmTags[] = $utmTagEntity;
                }
            }

            $leadModel = $this->getModel('lead');
            \assert($leadModel instanceof LeadModel);

            if (!empty($newUtmTags)) {
                $leadModel->getUtmTagRepository()->saveEntities($newUtmTags);
            }

            // Get an updated list of tags
            $allUtmTags    = $leadModel->getUtmTagRepository()->getSimpleList(null, [], 'utmtag');
            $utmTagOptions = '';

            foreach ($allUtmTags as $utmTag) {
                $selected = (in_array($utmTag['value'], $utmTags) || in_array($utmTag['label'], $utmTags)) ? ' selected="selected"' : '';
                $utmTagOptions .= '<option'.$selected.' value="'.$utmTag['value'].'">'.$utmTag['label'].'</option>';
            }

            $data = [
                'success' => 1,
                'tags'    => $utmTagOptions,
            ];
        } else {
            $data = ['success' => 0];
        }

        return $this->sendJsonResponse($data);
    }

    public function reorderAction(Request $request): JsonResponse
    {
        $dataArray = ['success' => 0];
        $order     = InputHelper::clean($request->request->get('field'));
        $page      = (int) $request->get('page');
        $limit     = (int) $request->get('limit');

        if (!empty($order)) {
            /** @var \Mautic\LeadBundle\Model\FieldModel $model */
            $model = $this->getModel('lead.field');

            $startAt = ($page > 1) ? ($page * $limit) + 1 : 1;
            $model->reorderFieldsByList($order, $startAt);
            $dataArray['success'] = 1;
        }

        return $this->sendJsonResponse($dataArray);
    }

    public function updateLeadFieldValuesAction(Request $request): JsonResponse
    {
        $alias     = InputHelper::clean($request->request->get('alias'));
        $operator  = InputHelper::clean($request->request->get('operator'));
        $changed   = InputHelper::clean($request->request->get('changed'));
        $dataArray = ['success' => 0, 'options' => null, 'optionsAttr' => [], 'operators' => null, 'disabled' => false];
        $leadField = $this->getModel('lead.field')->getRepository()->findOneBy(['alias' => $alias]);

        if ($leadField) {
            $options       = null;
            $leadFieldType = $leadField->getType();

            $properties = $leadField->getProperties();
            if (!empty($properties['list'])) {
                // Lookup/Select options
                $options = FormFieldHelper::parseList($properties['list']);
            } elseif (!empty($properties) && 'boolean' == $leadFieldType) {
                // Boolean options
                $options = [
                    0 => $properties['no'],
                    1 => $properties['yes'],
                ];
            } else {
                switch ($leadFieldType) {
                    case 'country':
                        $options = FormFieldHelper::getCountryChoices();
                        break;
                    case 'region':
                        $options = FormFieldHelper::getRegionChoices();
                        break;
                    case 'timezone':
                        $options = FormFieldHelper::getTimezonesChoices();
                        break;
                    case 'locale':
                        $options = array_flip(FormFieldHelper::getLocaleChoices());
                        break;
                    case 'date':
                    case 'datetime':
                        if ('date' == $operator) {
                            $fieldHelper = new FormFieldHelper();
                            $fieldHelper->setTranslator($this->translator);
                            $options = $fieldHelper->getDateChoices();
                            $options = array_merge(
                                [
                                    'custom' => $this->translator->trans('mautic.campaign.event.timed.choice.custom'),
                                ],
                                $options
                            );

                            $dataArray['optionsAttr']['custom'] = [
                                'data-custom' => 1,
                            ];
                        }
                        break;
                    default:
                        $options = (!empty($properties)) ? $properties : [];
                }
            }

            $dataArray['fieldType'] = $leadFieldType;
            $dataArray['options']   = $options;

            if ('field' === $changed) {
                $leadModel = $this->getModel('lead');
                \assert($leadModel instanceof LeadModel);
                $dataArray['operators'] = $leadModel->getOperatorsForFieldType($leadFieldType, ['date']);
                foreach ($dataArray['operators'] as $value => $label) {
                    $dataArray['operators'][$value] = $this->translator->trans($label);
                }
                $operator = array_key_first($dataArray['operators']);
            }

            $disabled = false;
            switch ($operator) {
                case 'empty':
                case '!empty':
                    $disabled             = true;
                    $dataArray['options'] = null;
                    break;
                case 'regexp':
                case '!regexp':
                    $dataArray['options'] = null;
                    break;
            }
            $dataArray['disabled'] = $disabled;
        }

        $dataArray['success'] = 1;

        return $this->sendJsonResponse($dataArray);
    }

    public function setAsPrimaryCompanyAction(Request $request): JsonResponse
    {
        $dataArray['success'] = 1;
        $companyId            = InputHelper::clean($request->request->get('companyId'));
        $leadId               = InputHelper::clean($request->request->get('leadId'));

        $leadModel = $this->getModel('lead');
        \assert($leadModel instanceof LeadModel);
        $primaryCompany = $leadModel->setPrimaryCompany($companyId, $leadId);

        $dataArray = array_merge($dataArray, $primaryCompany);

        return $this->sendJsonResponse($dataArray);
    }

    public function getCampaignShareStatsAction(Request $request, SegmentCampaignShare $segmentCampaignShareService): JsonResponse
    {
        $ids      = $request->query->all()['ids'] ?? [];
        $entityid = $request->query->get('entityId');

        $data = $segmentCampaignShareService->getCampaignsSegmentShare((int) $entityid, $ids);

        $data = [
            'success' => 1,
            'stats'   => $data,
        ];

        return new JsonResponse($data);
    }

    /**
     * @throws \Exception
     */
    public function getLeadCountAction(Request $request): JsonResponse
    {
        $id = (int) InputHelper::clean($request->get('id'));

        /** @var ListModel $model */
        $model          = $this->getModel('lead.list');
        $leadListExists = $model->leadListExists($id);

        if (!$leadListExists) {
            return new JsonResponse($this->prepareJsonResponse(0), Response::HTTP_NOT_FOUND);
        }

        $leadCounts = $model->getSegmentContactCount([$id]);
        $leadCount  = $leadCounts[$id];

        return new JsonResponse($this->prepareJsonResponse($leadCount));
    }

    public function getSegmentDependencyTreeAction(Request $request, SegmentDependencyTreeFactory $segmentDependencyTreeFactory): JsonResponse
    {
        /** @var ListModel $model */
        $model   = $this->getModel('lead.list');
        $id      = (int) $request->get('id');
        $segment = $model->getEntity($id);

        if (!$segment) {
            return new JsonResponse(['message' => "Segment {$id} could not be found."], Response::HTTP_NOT_FOUND);
        }

        $parentNode = $segmentDependencyTreeFactory->buildTree($segment);
        $formatter  = new JsPlumbFormatter();

        return new JsonResponse($formatter->format($parentNode));
    }

    /**
     * @return array<string, mixed>
     */
    private function prepareJsonResponse(int $leadCount): array
    {
        return [
            'html' => $this->translator->trans(
                'mautic.lead.list.viewleads_count',
                ['%count%' => $leadCount]
            ),
            'leadCount' => $leadCount,
        ];
    }
}
