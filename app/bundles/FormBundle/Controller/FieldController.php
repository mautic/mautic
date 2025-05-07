<?php

namespace Mautic\FormBundle\Controller;

use Doctrine\Persistence\ManagerRegistry;
use Mautic\CoreBundle\Controller\FormController as CommonFormController;
use Mautic\CoreBundle\Factory\ModelFactory;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Service\FlashBag;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\FormBundle\Collector\AlreadyMappedFieldCollectorInterface;
use Mautic\FormBundle\Collector\MappedObjectCollectorInterface;
use Mautic\FormBundle\Entity\Field;
use Mautic\FormBundle\Event\FormBuilderEvent;
use Mautic\FormBundle\FormEvents;
use Mautic\FormBundle\Helper\FormFieldHelper;
use Mautic\FormBundle\Model\FieldModel;
use Mautic\FormBundle\Model\FormModel;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

class FieldController extends CommonFormController
{
    public function __construct(
        private FormModel $formModel,
        private FieldModel $formFieldModel,
        FormFieldHelper $fieldHelper,
        FormFactoryInterface $formFactory,
        private MappedObjectCollectorInterface $mappedObjectCollector,
        private AlreadyMappedFieldCollectorInterface $alreadyMappedFieldCollector,
        ManagerRegistry $doctrine,
        ModelFactory $modelFactory,
        UserHelper $userHelper,
        CoreParametersHelper $coreParametersHelper,
        EventDispatcherInterface $dispatcher,
        Translator $translator,
        FlashBag $flashBag,
        RequestStack $requestStack,
        CorePermissions $security,
    ) {
        $this->fieldHelper                 = $fieldHelper;
        $this->formFactory                 = $formFactory;

        parent::__construct($formFactory, $fieldHelper, $doctrine, $modelFactory, $userHelper, $coreParametersHelper, $dispatcher, $translator, $flashBag, $requestStack, $security);
    }

    /**
     * Generates new form and processes post data.
     *
     * @return Response
     */
    public function newAction(Request $request, Environment $twig)
    {
        $success = 0;
        $valid   = $cancelled   = false;
        $method  = $request->getMethod();
        $session = $request->getSession();

        if ('POST' == $method) {
            $formField = $request->request->all()['formfield'] ?? [];
            $fieldType = $formField['type'];
            $formId    = $formField['formId'];
        } else {
            $fieldType = $request->query->get('type');
            $formId    = $request->query->get('formId');
            $formField = [
                'type'     => $fieldType,
                'formId'   => $formId,
                'parent'   => $request->query->get('parent'),
            ];
        }

        $customComponents = $this->formModel->getCustomComponents();
        $customParams     = $customComponents['fields'][$fieldType] ?? false;
        // ajax only for form fields
        if (!$fieldType
            || !$request->isXmlHttpRequest()
            || !$this->security->isGranted(['form:forms:editown', 'form:forms:editother', 'form:forms:create'], 'MATCH_ONE')
        ) {
            return $this->modalAccessDenied();
        }

        // Generate the form
        $form = $this->getFieldForm($formId, $formField);

        if (!empty($customParams)) {
            $formField['isCustom']         = true;
            $formField['customParameters'] = $customParams;
        }

        // Check for a submitted form and process it
        if ('POST' == $method) {
            if (!$cancelled = $this->isFormCancelled($form)) {
                if ($valid = $this->isFormValid($form)) {
                    $success = 1;

                    // form is valid so process the data
                    $keyId = 'new'.hash('sha1', uniqid(mt_rand()));

                    // save the properties to session
                    $fields          = $session->get('mautic.form.'.$formId.'.fields.modified', []);
                    $formData        = $form->getData();
                    $formField       = array_merge($formField, $formData);
                    $formField['id'] = $keyId;

                    // Get aliases in order to generate a new one for the new field
                    $aliases = [];
                    foreach ($fields as $f) {
                        $aliases[] = $f['alias'];
                    }

                    // Generate or ensure a unique alias
                    $alias          = empty($formField['alias']) ? $formField['label'] : $formField['alias'];
                    $formFieldModel = $this->getModel('form.field');
                    \assert($formFieldModel instanceof FieldModel);
                    $formField['alias'] = $formFieldModel->generateAlias($alias, $aliases);

                    // Force required for captcha if not a honeypot
                    if ('captcha' == $formField['type']) {
                        $formField['isRequired'] = !empty($formField['properties']['captcha']);
                    }

                    // Add it to the next to last assuming the last is the submit button
                    if (count($fields)) {
                        $lastField = end($fields);
                        $lastKey   = key($fields);
                        array_pop($fields);

                        $fields[$keyId]   = $formField;
                        $fields[$lastKey] = $lastField;
                    } else {
                        $fields[$keyId] = $formField;
                    }

                    $session->set('mautic.form.'.$formId.'.fields.modified', $fields);

                    // Keep track of used lead fields
                    if (!empty($formField['mappedObject']) && !empty($formField['mappedField']) && empty($formData['parent'])) {
                        $this->alreadyMappedFieldCollector->addField($formId, $formField['mappedObject'], $formField['mappedField']);
                    }
                } else {
                    $success = 0;
                }
            }
        }

        $viewParams = ['type' => $fieldType];
        if ($cancelled || $valid) {
            $closeModal = true;
        } else {
            $closeModal                = false;
            $viewParams['tmpl']        = 'field';
            $viewParams['form']        = (isset($customParams['formTheme'])) ? $this->setFormTheme($form, $twig, $customParams['formTheme']) : $form->createView();
            $viewParams['fieldHeader'] = (!empty($customParams)) ? $this->translator->trans($customParams['label']) : $this->translator->transConditional('mautic.core.type.'.$fieldType, 'mautic.form.field.type.'.$fieldType);
        }

        $passthroughVars = [
            'mauticContent' => 'formField',
            'success'       => $success,
            'route'         => false,
        ];

        if (!empty($keyId)) {
            $entity     = new Field();
            $blank      = $entity->convertToArray();
            $formField  = array_merge($blank, $formField);
            $formEntity = $this->formModel->getEntity($formId);

            $passthroughVars['parent']    = $formField['parent'];
            $passthroughVars['fieldId']   = $keyId;
            $template                     = (!empty($customParams)) ? $customParams['template'] : '@MauticForm/Field/'.$fieldType.'.html.twig';
            $leadFieldModel               = $this->getModel('lead.field');
            \assert($leadFieldModel instanceof \Mautic\LeadBundle\Model\FieldModel);
            $passthroughVars['fieldHtml'] = $this->renderView(
                '@MauticForm/Builder/_field_wrapper.html.twig',
                [
                    'isConditional'        => !empty($formField['parent']),
                    'template'             => $template,
                    'inForm'               => true,
                    'field'                => $formField,
                    'id'                   => $keyId,
                    'formId'               => $formId,
                    'formName'             => null === $formEntity ? 'newform' : $formEntity->generateFormName(),
                    'mappedFields'         => $this->mappedObjectCollector->buildCollection((string) $formField['mappedObject']),
                    'inBuilder'            => true,
                    'fields'               => $this->fieldHelper->getChoiceList($customComponents['fields']),
                    'viewOnlyFields'       => $customComponents['viewOnlyFields'],
                    'formFields'           => $fields,
                ]
            );
        }

        if ($closeModal) {
            // just close the modal
            $passthroughVars['closeModal'] = 1;

            return new JsonResponse($passthroughVars);
        }

        return $this->ajaxAction($request, [
            'contentTemplate' => '@MauticForm/Builder/'.$viewParams['tmpl'].'.html.twig',
            'viewParameters'  => $viewParams,
            'passthroughVars' => $passthroughVars,
        ]);
    }

    /**
     * Generates edit form and processes post data.
     *
     * @param int $objectId
     *
     * @return Response
     */
    public function editAction(Request $request, Environment $twig, $objectId)
    {
        $session   = $request->getSession();
        $method    = $request->getMethod();
        $formfield = $request->request->all()['formfield'] ?? [];
        $formId    = 'POST' === $method ? ($formfield['formId'] ?? '') : $request->query->get('formId');
        $fields    = $session->get('mautic.form.'.$formId.'.fields.modified', []);
        $success   = 0;
        $valid     = $cancelled = false;
        $formField = array_key_exists($objectId, $fields) ? $fields[$objectId] : [];

        if ($formField) {
            $fieldType = $formField['type'];

            // ajax only for form fields
            if (!$fieldType
                || !$request->isXmlHttpRequest()
                || !$this->security->isGranted(['form:forms:editown', 'form:forms:editother', 'form:forms:create'], 'MATCH_ONE')
            ) {
                return $this->modalAccessDenied();
            }

            // Generate the form
            $form = $this->getFieldForm($formId, $formField);

            // Check for a submitted form and process it
            if ('POST' == $method) {
                if (!$cancelled = $this->isFormCancelled($form)) {
                    if ($valid = $this->isFormValid($form)) {
                        $success = 1;

                        // form is valid so process the data

                        // save the properties to session
                        $session  = $request->getSession();
                        $fields   = $session->get('mautic.form.'.$formId.'.fields.modified');
                        $formData = $form->getData();

                        // overwrite with updated data
                        $formField = array_merge($fields[$objectId], $formData);

                        if (str_contains((string) $objectId, 'new')) {
                            // Get aliases in order to generate update for this one
                            $aliases = [];
                            foreach ($fields as $k => $f) {
                                if ($k != $objectId) {
                                    $aliases[] = $f['alias'];
                                }
                            }
                            $formField['alias'] = $this->formFieldModel->generateAlias(
                                $formField['alias'] ?? $formField['label'] ?? '',
                                $aliases
                            );
                        }

                        // Force required for captcha if not a honeypot
                        if ('captcha' == $formField['type']) {
                            $formField['isRequired'] = !empty($formField['properties']['captcha']);
                        }

                        $fields[$objectId] = $formField;
                        $session->set('mautic.form.'.$formId.'.fields.modified', $fields);

                        // Keep track of used lead fields
                        if (!empty($formField['mappedObject']) && !empty($formField['mappedField']) && empty($formData['parent'])) {
                            $this->alreadyMappedFieldCollector->addField($formId, $formField['mappedObject'], $formField['mappedField']);
                        }
                    }
                }
            }

            $viewParams       = ['type' => $fieldType];
            $customComponents = $this->formModel->getCustomComponents();
            $customParams     = $customComponents['fields'][$fieldType] ?? false;

            if ($cancelled || $valid) {
                $closeModal = true;
            } else {
                $closeModal         = false;
                $viewParams['tmpl'] = 'field';
                $viewParams['form'] = (isset($customParams['formTheme'])) ? $this->setFormTheme(
                    $form,
                    $twig,
                    $customParams['formTheme']
                ) : $form->createView();
                $viewParams['fieldHeader'] = (!empty($customParams))
                    ? $this->translator->trans($customParams['label'])
                    : $this->translator->transConditional('mautic.core.type.'.$fieldType, 'mautic.form.field.type.'.$fieldType);
            }

            $passthroughVars = [
                'mauticContent' => 'formField',
                'success'       => $success,
                'route'         => false,
            ];

            $passthroughVars['fieldId'] = $objectId;
            $template                   = (!empty($customParams)) ? $customParams['template'] : '@MauticForm/Field/'.$fieldType.'.html.twig';

            // prevent undefined errors
            $entity       = new Field();
            $blank        = $entity->convertToArray();
            $formField    = array_merge($blank, $formField);

            $leadFieldModel = $this->getModel('lead.field');
            \assert($leadFieldModel instanceof \Mautic\LeadBundle\Model\FieldModel);
            $passthroughVars['fieldHtml'] = $this->renderView(
                '@MauticForm/Builder/_field_wrapper.html.twig',
                [
                    'isConditional'        => !empty($formField['parent']),
                    'template'             => $template,
                    'inForm'               => true,
                    'field'                => $formField,
                    'id'                   => $objectId,
                    'formId'               => $formId,
                    'mappedFields'         => $this->mappedObjectCollector->buildCollection((string) $formField['mappedObject']),
                    'inBuilder'            => true,
                    'fields'               => $this->fieldHelper->getChoiceList($customComponents['fields']),
                    'formFields'           => $fields,
                    'viewOnlyFields'       => $customComponents['viewOnlyFields'],
                ]
            );

            if ($closeModal) {
                // just close the modal
                $passthroughVars['closeModal'] = 1;

                return new JsonResponse($passthroughVars);
            }

            return $this->ajaxAction(
                $request,
                [
                    'contentTemplate' => '@MauticForm/Builder/'.$viewParams['tmpl'].'.html.twig',
                    'viewParameters'  => $viewParams,
                    'passthroughVars' => $passthroughVars,
                ]
            );
        }

        return new JsonResponse(['success' => 0]);
    }

    /**
     * Deletes the entity.
     *
     * @param int $objectId
     *
     * @return JsonResponse
     */
    public function deleteAction(Request $request, $objectId)
    {
        $session = $request->getSession();
        $formId  = $request->query->get('formId');
        $fields  = $session->get('mautic.form.'.$formId.'.fields.modified', []);
        $delete  = $session->get('mautic.form.'.$formId.'.fields.deleted', []);

        // ajax only for form fields
        if (!$request->isXmlHttpRequest()
            || !$this->security->isGranted(['form:forms:editown', 'form:forms:editother', 'form:forms:create'], 'MATCH_ONE')
        ) {
            return $this->accessDenied();
        }

        $formField = (array_key_exists($objectId, $fields)) ? $fields[$objectId] : null;

        if ('POST' === $request->getMethod() && null !== $formField) {
            if ($formField['mappedObject'] && $formField['mappedField']) {
                // Allow to select the lead field from the delete field again
                $this->alreadyMappedFieldCollector->removeField($formId, $formField['mappedObject'], $formField['mappedField']);
            }

            // add the field to the delete list
            if (!in_array($objectId, $delete)) {
                $delete[] = $objectId;
                $session->set('mautic.form.'.$formId.'.fields.deleted', $delete);
            }

            $dataArray = [
                'mauticContent' => 'formField',
                'success'       => 1,
                'route'         => false,
            ];
        } else {
            $dataArray = ['success' => 0];
        }

        return new JsonResponse($dataArray);
    }

    /**
     * @param int     $formId
     * @param mixed[] $formField
     *
     * @return mixed
     */
    private function getFieldForm($formId, array $formField)
    {
        // fire the form builder event
        $formModel = $this->getModel('form.form');
        \assert($formModel instanceof FormModel);
        $customComponents = $this->formModel->getCustomComponents();
        $customParams     = $customComponents['fields'][$formField['type']] ?? false;

        $formFieldModel = $this->getModel('form.field');
        \assert($formFieldModel instanceof FieldModel);
        $form = $formFieldModel->createForm(
            $formField,
            $this->formFactory,
            (!empty($formField['id'])) ?
                $this->generateUrl('mautic_formfield_action', ['objectAction' => 'edit', 'objectId' => $formField['id']])
                : $this->generateUrl('mautic_formfield_action', ['objectAction' => 'new']),
            ['customParameters' => $customParams]
        );
        $form->get('formId')->setData($formId);

        $event      = new FormBuilderEvent($this->translator);
        $this->dispatcher->dispatch($event, FormEvents::FORM_ON_BUILD);
        $event->addValidatorsToBuilder($form);

        return $form;
    }
}
