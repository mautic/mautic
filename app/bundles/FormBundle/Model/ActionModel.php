<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\FormBundle\Model;

use Mautic\CoreBundle\Model\FormModel as CommonFormModel;
use Mautic\FormBundle\Entity\Action;

/**
 * Class ActionModel.
 */
class ActionModel extends CommonFormModel
{
    /**
     * {@inheritdoc}
     *
     * @return \Mautic\FormBundle\Entity\ActionRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository('MauticFormBundle:Action');
    }

    /**
     * {@inheritdoc}
     */
    public function getPermissionBase()
    {
        return 'form:forms';
    }

    /**
     * {@inheritdoc}
     */
    public function getEntity($id = null)
    {
        if ($id === null) {
            return new Action();
        }

        return parent::getEntity($id);
    }

    /**
     * @param object                              $entity
     * @param \Symfony\Component\Form\FormFactory $formFactory
     * @param null                                $action
     * @param array                               $options
     */
    public function createForm($entity, $formFactory, $action = null, $options = [])
    {
        if (!$entity instanceof Action) {
            throw new \InvalidArgumentException('Entity must be of class Action');
        }

        if ($action) {
            $options['action'] = $action;
        }

        if (empty($options['formId']) && $entity->getForm() !== null) {
            $options['formId'] = $entity->getForm()->getId();
        }

        return $formFactory->create('formaction', $entity->convertToArray(), $options);
    }
}
