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
use Mautic\DashboardBundle\Entity\Widget;
use Mautic\DashboardBundle\Event\WidgetDetailEvent;
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
        return $this->em->getRepository('MauticDashboardBundle:Widget');
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getPermissionBase()
    {
        return 'dashboard:widgets';
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
            return new Widget();
        }

        $entity = parent::getEntity($id);

        return $entity;
    }

    /**
     * Load widgets for the current user from database and dispatch the onWidgetDetailGenerate trigger
     *
     * @return array
     */
    public function getWidgets()
    {
        $widgets = $this->getEntities(array(
            'filter' => array(
                'force' => array(
                    'column' => 'm.createdBy',
                    'expr'   => 'eq',
                    'value'  => $this->factory->getUser()->getId()
                )
            )
        ));

        foreach ($widgets as &$widget) {
            $this->populateWidgetContent($widget);
        }

        return $widgets;
    }

    /**
     * Load widget content from the onWidgetDetailGenerate event
     *
     * @return array
     */
    public function populateWidgetContent(Widget &$widget)
    {
        $cacheDir = $this->factory->getParameter('cached_data_dir', $this->factory->getSystemPath('cache', true));
        $dispatcher = $this->factory->getDispatcher();
        $event      = new WidgetDetailEvent();
        $event->setType($widget->getType());
        $event->setWidget($widget);
        $event->setCacheDir($cacheDir);
        $dispatcher->dispatch(DashboardEvents::DASHBOARD_ON_MODULE_DETAIL_GENERATE, $event);
        $widget->setErrorMessage($event->getErrorMessage());
        $widget->setTemplate($event->getTemplate());
        $widget->setTemplateData($event->getTemplateData());
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
        if (!$entity instanceof Widget) {
            throw new MethodNotAllowedHttpException(array('Widget'), 'Entity must be of class Widget()');
        }

        if (!empty($action))  {
            $options['action'] = $action;
        }

        return $formFactory->create('widget', $entity, $options);
    }
}
