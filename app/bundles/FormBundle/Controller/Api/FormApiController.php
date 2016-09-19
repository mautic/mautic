<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\FormBundle\Controller\Api;

use Mautic\ApiBundle\Controller\CommonApiController;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use FOS\RestBundle\Util\Codes;

/**
 * Class FormApiController
 */
class FormApiController extends CommonApiController
{

    /**
     * {@inheritdoc}
     */
    public function initialize (FilterControllerEvent $event)
    {
        parent::initialize($event);
        $this->model            = $this->getModel('form');
        $this->entityClass      = 'Mautic\FormBundle\Entity\Form';
        $this->entityNameOne    = 'form';
        $this->entityNameMulti  = 'forms';
        $this->permissionBase   = 'form:forms';
        $this->serializerGroups = array('formDetails', 'categoryList', 'publishDetails');
    }

    /**
     * Obtains a list of forms
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getEntitiesAction ()
    {
        if (!$this->security->isGranted('form:forms:viewother')) {
            $this->listFilters = array(
                'column' => 'f.createdBy',
                'expr'   => 'eq',
                'value'  => $this->factory->getUser()->getId()
            );
        }

        return parent::getEntitiesAction();
    }

    /**
     * {@inheritdoc}
     */
    protected function preSerializeEntity (&$entity, $action = 'view')
    {
        $entity->automaticJs = '<script type="text/javascript" src="' . $this->generateUrl('mautic_form_generateform', array('id' => $entity->getId()), true) . '"></script>';
    }

    /**
     * {@inheritdoc}
     */
    protected function preSaveEntity(&$entity, $form, $parameters, $action = 'edit')
    {
        $method = $this->request->getMethod();

        // Set clean alias to prevent SQL errors
        $alias = $this->model->cleanAlias($entity->getName(), '', 10);
        $entity->setAlias($alias);

        // Set timestamps
        $this->model->setTimestamps($entity, true, false);

        if (!$entity->getId()) {
            // Save the form first to get the form ID.
            // Using the repository function to not trigger the listeners twice.
            $this->model->getRepository()->saveEntity($entity);
        }

        $formId = $entity->getId();

        if (!empty($parameters['fields']) && is_array($parameters['fields'])) {
            $fieldModel = $this->getModel('form.field');
            $aliases = $entity->getFieldAliases();

            foreach ($parameters['fields'] as &$fieldParams) {
                
                if (empty($fieldParams['id'])) {
                    // Create an unique ID if not set - the following code requires one
                    $fieldParams['id'] = 'new' . hash('sha1', uniqid(mt_rand()));
                    $fieldEntity = $fieldModel->getEntity();
                } else {
                    $fieldEntity = $fieldModel->getEntity($fieldParams['id']);
                }

                $fieldEntityArray = $fieldEntity->convertToArray();
                $fieldEntityArray['formId'] = $formId;
                $fieldEntityArray['alias'] = $fieldParams['alias'] = $fieldModel->generateAlias($fieldEntityArray['label'], $aliases);

                $fieldForm = $this->createEntityForm($fieldEntityArray, $fieldModel);
                $fieldForm->submit($fieldParams, 'PATCH' !== $method);

                if (!$fieldForm->isValid()) {
                    $formErrors = $this->getFormErrorMessages($fieldForm);
                    $msg        = $this->getFormErrorMessage($formErrors);
                    throw new \Exception('Fields: '.$msg);
                }
            }

            $this->model->setFields($entity, $parameters['fields']);
        }

        if (!empty($parameters['actions']) && is_array($parameters['actions'])) {
            $actionModel = $this->getModel('form.action');

            foreach ($parameters['actions'] as &$actionParams) {
                if (empty($actionParams['id'])) {
                    $actionParams['id'] = 'new' . hash('sha1', uniqid(mt_rand()));
                    $actionEntity = $actionModel->getEntity();
                } else {
                    $actionEntity = $actionModel->getEntity($actionParams['id']);
                }

                $actionEntity->setForm($entity);

                $actionForm = $this->createEntityForm($actionEntity, $actionModel);
                $actionForm->submit($actionParams, 'PATCH' !== $method);

                if (!$actionForm->isValid()) {
                    $formErrors = $this->getFormErrorMessages($actionForm);
                    $msg        = $this->getFormErrorMessage($formErrors);
                    throw new \Exception('Actions: '.$msg);
                }
            }

            // Save the form first and new actions so that new fields are available to actions.
            // Using the repository function to not trigger the listeners twice.
            $this->model->getRepository()->saveEntity($entity);
            $this->model->setActions($entity, $parameters['actions']);
        }
    }
}
