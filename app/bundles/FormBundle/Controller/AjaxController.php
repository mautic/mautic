<?php

namespace Mautic\FormBundle\Controller;

use Mautic\CoreBundle\Controller\AjaxController as CommonAjaxController;
use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\FormBundle\Collector\AlreadyMappedFieldCollectorInterface;
use Mautic\FormBundle\Collector\FieldCollectorInterface;
use Mautic\FormBundle\Crate\FieldCrate;
use Mautic\FormBundle\Model\FormModel;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Service\Attribute\Required;

final class AjaxController extends CommonAjaxController
{
    private FieldCollectorInterface $fieldCollector;

    private AlreadyMappedFieldCollectorInterface $mappedFieldCollector;

    private FormModel $formModel;

    #[Required]
    public function autowireFormAjaxController(
        FieldCollectorInterface $fieldCollector,
        AlreadyMappedFieldCollectorInterface $mappedFieldCollector,
        FormModel $formModel,
    ): void {
        $this->fieldCollector       = $fieldCollector;
        $this->mappedFieldCollector = $mappedFieldCollector;
        $this->formModel            = $formModel;
    }

    public function reorderFieldsAction(Request $request, string $name = 'fields'): JsonResponse
    {
        if ('form' === $name) {
            $name = 'fields';
        }
        $dataArray   = ['success' => 0];
        $sessionId   = InputHelper::clean($request->request->get('formId'));
        $sessionName = 'mautic.form.'.$sessionId.'.'.$name.'.modified';
        $session     = $request->getSession();
        $orderName   = ('fields' === $name) ? 'mauticform' : 'mauticform_action';
        $order       = InputHelper::clean($request->request->all()[$orderName]);
        $components  = $session->get($sessionName);

        if (!empty($order) && !empty($components)) {
            $components = array_replace(array_flip($order), $components);
            $session->set($sessionName, $components);
            $dataArray['success'] = 1;
        }

        return $this->sendJsonResponse($dataArray);
    }

    public function getFieldsForObjectAction(Request $request): JsonResponse
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
                    fn (FieldCrate $field): array => [
                        'label'      => $field->getName(),
                        'value'      => $field->getKey(),
                        'isListType' => $field->isListType(),
                    ],
                    $fields->getArrayCopy()
                ),
            ]
        );
    }

    public function reorderActionsAction(Request $request): JsonResponse
    {
        return $this->reorderFieldsAction($request, 'actions');
    }

    public function updateFormFieldsAction(Request $request): JsonResponse
    {
        $formId     = (int) $request->request->get('formId');
        $dataArray  = ['success' => 0];
        $entity     = $this->formModel->getEntity($formId);
        $formFields = $entity instanceof \Mautic\FormBundle\Entity\Form ? $entity->getFields() : [];
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
     */
    #[Route('/form/submit/ajax', name: 'mautic_form_postresults_ajax')]
    public function submitAction(Request $request): JsonResponse
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

        $data = is_array($responseData)
            ? array_merge($responseData, ['message' => $message, 'type' => $type, 'success' => $success])
            : [];

        return $this->sendJsonResponse($data);
    }
}
