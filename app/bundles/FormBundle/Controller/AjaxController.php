<?php

namespace Mautic\FormBundle\Controller;

use Doctrine\Persistence\ManagerRegistry;
use Mautic\CoreBundle\Controller\AjaxController as CommonAjaxController;
use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\CoreBundle\Factory\ModelFactory;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Service\FlashBag;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\FormBundle\Collector\AlreadyMappedFieldCollectorInterface;
use Mautic\FormBundle\Collector\FieldCollectorInterface;
use Mautic\FormBundle\Crate\FieldCrate;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class AjaxController extends CommonAjaxController
{
    /**
     * @var FieldCollectorInterface
     */
    private $fieldCollector;

    /**
     * @var AlreadyMappedFieldCollectorInterface
     */
    private $mappedFieldCollector;

    public function __construct(FieldCollectorInterface $fieldCollector, AlreadyMappedFieldCollectorInterface $mappedFieldCollector, ManagerRegistry $doctrine, MauticFactory $factory, ModelFactory $modelFactory, UserHelper $userHelper, CoreParametersHelper $coreParametersHelper, EventDispatcherInterface $dispatcher, Translator $translator, FlashBag $flashBag, RequestStack $requestStack, CorePermissions $security)
    {
        $this->fieldCollector       = $fieldCollector;
        $this->mappedFieldCollector = $mappedFieldCollector;

        parent::__construct($doctrine, $factory, $modelFactory, $userHelper, $coreParametersHelper, $dispatcher, $translator, $flashBag, $requestStack, $security);
    }

    /**
     * @param string $name
     *
     * @return JsonResponse
     */
    public function reorderFieldsAction(Request $request, $bundle, $name = 'fields')
    {
        if ('form' === $name) {
            $name = 'fields';
        }
        $dataArray   = ['success' => 0];
        $sessionId   = InputHelper::clean($request->request->get('formId'));
        $sessionName = 'mautic.form.'.$sessionId.'.'.$name.'.modified';
        $session     = $request->getSession();
        $orderName   = ('fields' == $name) ? 'mauticform' : 'mauticform_action';
        $order       = InputHelper::clean($request->request->get($orderName));
        $components  = $session->get($sessionName);

        if (!empty($order) && !empty($components)) {
            $components = array_replace(array_flip($order), $components);
            $session->set($sessionName, $components);
            $dataArray['success'] = 1;
        }

        return $this->sendJsonResponse($dataArray);
    }

    /**
     * @return JsonResponse
     */
    public function getFieldsForObjectAction(Request $request)
    {
        $formId       = $request->get('formId');
        $mappedObject = $request->get('mappedObject');
        $mappedField  = $request->get('mappedField');
        $mappedFields = $this->mappedFieldCollector->getFields($formId, $mappedObject);
        $fields       = $this->fieldCollector->getFields($mappedObject);
        $fields       = $fields->removeFieldsWithKeys($mappedFields, $mappedField);

        return $this->sendJsonResponse(
            [
                'fields' => array_map(
                    function (FieldCrate $field) {
                        return [
                            'label'      => $field->getName(),
                            'value'      => $field->getKey(),
                            'isListType' => $field->isListType(),
                        ];
                    },
                    $fields->getArrayCopy()
                ),
            ]
        );
    }

    /**
     * @return JsonResponse
     */
    public function reorderActionsAction(Request $request)
    {
        return $this->reorderFieldsAction($request, 'actions');
    }

    /**
     * @return JsonResponse
     */
    public function updateFormFieldsAction(Request $request)
    {
        $formId     = (int) $request->request->get('formId');
        $dataArray  = ['success' => 0];
        $model      = $this->getModel('form');
        $entity     = $model->getEntity($formId);
        $formFields = empty($entity) ? [] : $entity->getFields();
        $fields     = [];

        foreach ($formFields as $field) {
            if ('button' != $field->getType()) {
                $properties = $field->getProperties();
                $options    = [];

                if (!empty($properties['list']['list'])) {
                    // If the field is a SELECT field then the data gets stored in [list][list]
                    $optionList = $properties['list']['list'];
                } elseif (!empty($properties['optionlist']['list'])) {
                    // If the field is a radio or a checkbox then it will be stored in [optionlist][list]
                    $optionList = $properties['optionlist']['list'];
                }
                if (!empty($optionList)) {
                    foreach ($optionList as $listItem) {
                        if (is_array($listItem) && isset($listItem['value']) && isset($listItem['label'])) {
                            // The select box needs values to be [value] => label format so make sure we have that style then put it in
                            $options[$listItem['value']] = $listItem['label'];
                        } elseif (!is_array($listItem)) {
                            // Keeping for BC
                            $options[] = $listItem;
                        }
                    }
                }

                $fields[] = [
                    'id'      => $field->getId(),
                    'label'   => $field->getLabel(),
                    'alias'   => $field->getAlias(),
                    'type'    => $field->getType(),
                    'options' => $options,
                ];

                // Be sure to not pollute the symbol table.
                unset($optionList);
            }
        }

        $dataArray['fields']  = $fields;
        $dataArray['success'] = 1;

        return $this->sendJsonResponse($dataArray);
    }

    /**
     * Ajax submit for forms.
     *
     * @return JsonResponse
     */
    public function submitAction(Request $request)
    {
        $response     = $this->forwardWithPost('Mautic\FormBundle\Controller\PublicController::submitAction', $request->request->all(), [], ['ajax' => true]);
        $responseData = json_decode($response->getContent(), true);
        $success      = (!in_array($response->getStatusCode(), [404, 500]) && empty($responseData['errorMessage'])
            && empty($responseData['validationErrors']));

        $message = '';
        $type    = '';
        if (isset($responseData['successMessage'])) {
            $message = $responseData['successMessage'];
            $type    = 'notice';
        } elseif (isset($responseData['errorMessage'])) {
            $message = $responseData['errorMessage'];
            $type    = 'error';
        }

        $data = array_merge($responseData, ['message' => $message, 'type' => $type, 'success' => $success]);

        return $this->sendJsonResponse($data);
    }
}
