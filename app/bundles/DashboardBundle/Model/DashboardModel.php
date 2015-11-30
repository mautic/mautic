<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\DashboardBundle\Model;

use Mautic\CoreBundle\Model\FormModel;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Mautic\DashboardBundle\Entity\Module;

/**
 * Class DashboardModel
 */
class DashboardModel extends FormModel
{
    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getRepository()
    {
        return $this->em->getRepository('MauticDashboardBundle:Module');
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getPermissionBase()
    {
        return 'dashboard:modules';
    }

    /**
     * Get a specific entity or generate a new one if id is empty
     *
     * @param $id
     * @return null|object
     */
    public function getEntity($id = null)
    {
        if ($id === null) {
            return new Module();
        }

        $entity = parent::getEntity($id);

        return $entity;
    }

    /**
     * {@inheritdoc}
     *
     * @param Lead                                $entity
     * @param \Symfony\Component\Form\FormFactory $formFactory
     * @param string|null                         $action
     * @param array                               $options
     *
     * @return \Symfony\Component\Form\Form
     * @throws \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException
     */
    public function createForm($entity, $formFactory, $action = null, $options = array())
    {
        if (!$entity instanceof Module) {
            throw new MethodNotAllowedHttpException(array('Module'), 'Entity must be of class Module()');
        }

        if (!empty($action))  {
            $options['action'] = $action;
        }

        return $formFactory->create('module', $entity, $options);
    }
}
