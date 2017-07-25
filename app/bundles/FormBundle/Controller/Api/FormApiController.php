<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\FormBundle\Controller\Api;

use Mautic\ApiBundle\Controller\CommonApiController;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;

/**
 * Class FormApiController.
 */
class FormApiController extends CommonApiController
{
    /**
     * {@inheritdoc}
     */
    public function initialize(FilterControllerEvent $event)
    {
        $this->model            = $this->getModel('form');
        $this->entityClass      = 'Mautic\FormBundle\Entity\Form';
        $this->entityNameOne    = 'form';
        $this->entityNameMulti  = 'forms';
        $this->serializerGroups = ['formDetails', 'categoryList', 'publishDetails'];

        parent::initialize($event);
    }

    /**
     * {@inheritdoc}
     */
    protected function preSerializeEntity(&$entity, $action = 'view')
    {
        $entity->automaticJs = '<script type="text/javascript" src="'.$this->generateUrl('mautic_form_generateform', ['id' => $entity->getId()], true).'"></script>';
    }

    /**
     * Delete fields from a form.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function deleteFieldsAction($formId)
    {
        if (!$this->security->isGranted(['form:forms:editown', 'form:forms:editother'], 'MATCH_ONE')) {
            return $this->accessDenied();
        }

        $entity = $this->model->getEntity($formId);

        if ($entity === null) {
            return $this->notFound();
        }

        $fieldsToDelete = $this->request->get('fields');

        if (!is_array($fieldsToDelete)) {
            return $this->badRequest('The fields attribute must be array.');
        }

        $this->model->deleteFields($entity, $fieldsToDelete);

        $view = $this->view([$this->entityNameOne => $entity]);

        return $this->handleView($view);
    }

    /**
     * Delete fields from a form.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function deleteActionsAction($formId)
    {
        if (!$this->security->isGranted(['form:forms:editown', 'form:forms:editother'], 'MATCH_ONE')) {
            return $this->accessDenied();
        }

        $entity = $this->model->getEntity($formId);

        if ($entity === null) {
            return $this->notFound();
        }

        $actionsToDelete = $this->request->get('actions');

        if (!is_array($actionsToDelete)) {
            return $this->badRequest('The actions attribute must be array.');
        }

        $currentActions = $entity->getActions();

        foreach ($currentActions as $currentAction) {
            if (in_array($currentAction->getId(), $actionsToDelete)) {
                $entity->removeAction($currentAction);
            }
        }

        $this->model->saveEntity($entity);

        $view = $this->view([$this->entityNameOne => $entity]);

        return $this->handleView($view);
    }

    /**
     * {@inheritdoc}
     */
    protected function preSaveEntity(&$entity, $form, $parameters, $action = 'edit')
    {
        $method      = $this->request->getMethod();
        $fieldModel  = $this->getModel('form.field');
        $actionModel = $this->getModel('form.action');
        $isNew       = false;

        // Set clean alias to prevent SQL errors
        $alias = $this->model->cleanAlias($entity->getName(), '', 10);
        $entity->setAlias($alias);

        // Set timestamps
        $this->model->setTimestamps($entity, true, false);

        if (!$entity->getId()) {
            $isNew = true;

            // Save the form first to get the form ID.
            // Using the repository function to not trigger the listeners twice.
            $this->model->getRepository()->saveEntity($entity);
        }

        $formId           = $entity->getId();
        $requestFieldIds  = [];
        $requestActionIds = [];
        $currentFields    = $entity->getFields();
        $currentActions   = $entity->getActions();

        // Add fields from the request
        if (!empty($parameters['fields']) && is_array($parameters['fields'])) {
            $aliases = $entity->getFieldAliases();

            foreach ($parameters['fields'] as &$fieldParams) {
                if (empty($fieldParams['id'])) {
                    // Create an unique ID if not set - the following code requires one
                    $fieldParams['id'] = 'new'.hash('sha1', uniqid(mt_rand()));
                    $fieldEntity       = $fieldModel->getEntity();
                } else {
                    $fieldEntity       = $fieldModel->getEntity($fieldParams['id']);
                    $requestFieldIds[] = $fieldParams['id'];
                }

                $fieldEntityArray           = $fieldEntity->convertToArray();
                $fieldEntityArray['formId'] = $formId;
                $escapedAlias               = str_replace('"', '', $fieldParams['alias']);

                if (!in_array($escapedAlias, $aliases)) {
                    $fieldEntityArray['alias'] = $escapedAlias;
                } else {
                    $fieldEntityArray['alias'] = $fieldModel->generateAlias($fieldEntityArray['label'], $aliases);
                }

                $fieldForm = $this->createFieldEntityForm($fieldEntityArray);
                $fieldForm->submit($fieldParams, 'PATCH' !== $method);

                if (!$fieldForm->isValid()) {
                    $formErrors = $this->getFormErrorMessages($fieldForm);
                    $msg        = $this->getFormErrorMessage($formErrors);

                    return $this->returnError($msg, Codes::HTTP_BAD_REQUEST);
                }
            }

            $this->model->setFields($entity, $parameters['fields']);
        }

        // Remove fields which weren't in the PUT request
        if (!$isNew && $method === 'PUT') {
            $fieldsToDelete = [];

            foreach ($currentFields as $currentField) {
                if (!in_array($currentField->getId(), $requestFieldIds)) {
                    $fieldsToDelete[] = $currentField->getId();
                }
            }

            if ($fieldsToDelete) {
                $this->model->deleteFields($entity, $fieldsToDelete);
            }
        }

        // Add actions from the request
        if (!empty($parameters['actions']) && is_array($parameters['actions'])) {
            foreach ($parameters['actions'] as &$actionParams) {
                if (empty($actionParams['id'])) {
                    $actionParams['id'] = 'new'.hash('sha1', uniqid(mt_rand()));
                    $actionEntity       = $actionModel->getEntity();
                } else {
                    $actionEntity       = $actionModel->getEntity($actionParams['id']);
                    $requestActionIds[] = $actionParams['id'];
                }

                $actionEntity->setForm($entity);

                $actionForm = $this->createActionEntityForm($actionEntity);
                $actionForm->submit($actionParams, 'PATCH' !== $method);

                if (!$actionForm->isValid()) {
                    $formErrors = $this->getFormErrorMessages($actionForm);
                    $msg        = $this->getFormErrorMessage($formErrors);

                    return $this->returnError($msg, Codes::HTTP_BAD_REQUEST);
                }
            }

            // Save the form first and new actions so that new fields are available to actions.
            // Using the repository function to not trigger the listeners twice.
            $this->model->getRepository()->saveEntity($entity);
            $this->model->setActions($entity, $parameters['actions']);
        }

        // Remove actions which weren't in the PUT request
        if (!$isNew && $method === 'PUT') {
            foreach ($currentActions as $currentAction) {
                if (!in_array($currentAction->getId(), $requestActionIds)) {
                    $entity->removeAction($currentAction);
                }
            }
        }
    }

    /**
     * Creates the form instance.
     *
     * @param $entity
     *
     * @return Form
     */
    protected function createActionEntityForm($entity)
    {
        return $this->getModel('form.action')->createForm(
            $entity,
            $this->get('form.factory'),
            null,
            [
                'csrf_protection'    => false,
                'allow_extra_fields' => true,
            ]
        );
    }

    /**
     * Creates the form instance.
     *
     * @param $entity
     *
     * @return Form
     */
    protected function createFieldEntityForm($entity)
    {
        return $this->getModel('form.field')->createForm(
            $entity,
            $this->get('form.factory'),
            null,
            [
                'csrf_protection'    => false,
                'allow_extra_fields' => true,
            ]
        );
    }
}
