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
use Mautic\DashboardBundle\Event\ModuleDetailEvent;
use Mautic\DashboardBundle\DashboardEvents;

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
     * Load modules for the current user from database and dispatch the onModuleDetailGenerate trigger
     *
     * @return array
     */
    public function getModules()
    {
        $modules = $this->getEntities(array(
            'filter' => array(
                'force' => array(
                    'column' => 'm.createdBy',
                    'expr'   => 'eq',
                    'value'  => $this->factory->getUser()->getId()
                )
            )
        ));

        foreach ($modules as &$module) {
            $dispatcher = $this->factory->getDispatcher();
            $event      = new ModuleDetailEvent();
            $event->setType($module->getType());
            $event->setModule($module);
            $dispatcher->dispatch(DashboardEvents::DASHBOARD_ON_MODULE_DETAIL_GENERATE, $event);
            $module->setErrorMessage($event->getErrorMessage());
            $module->setTemplate($event->getTemplate());
            $module->setTemplateData($event->getTemplateData());
        }

        return $modules;
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
