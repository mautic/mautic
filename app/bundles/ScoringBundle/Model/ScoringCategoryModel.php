<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ScoringBundle\Model;

use Mautic\CoreBundle\Model\FormModel as CommonFormModel;
use Mautic\ScoringBundle\Entity\ScoringCategory;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

use Mautic\ScoringBundle\Event\ScoringCategoryBuilderEvent;
use Mautic\ScoringBundle\Event\ScoringCategoryEvent;
use Mautic\ScoringBundle\ScoringEvents;

/**
 * Class ScoringCategoryModel.
 */
class ScoringCategoryModel extends CommonFormModel
{
    /**
     * @deprecated Remove in 2.0
     *
     * @var MauticFactory
     */
    protected $factory;

    /**
     * {@inheritdoc}
     *
     * @return \Mautic\ScoringBundle\Entity\ScoringCategoryRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository('MauticScoringBundle:ScoringCategory');
    }

    /**
     * {@inheritdoc}
     */
    public function getPermissionBase()
    {
        return 'scoring:scoringCategory';
    }
    
    
    /**
     * {@inheritdoc}
     *
     * @throws MethodNotAllowedHttpException
     */
    public function createForm($entity, $formFactory, $action = null, $options = [])
    {
        if (!$entity instanceof ScoringCategory) {
            throw new MethodNotAllowedHttpException(['ScoringCategory']);
        }

        if (!empty($action)) {
            $options['action'] = $action;
        }
        return $formFactory->create(\Mautic\ScoringBundle\Form\Type\ScoringCategoryType::class, $entity, $options);
    }

    /**
     * {@inheritdoc}
     *
     * @return ScoringCategory|null
     */
    public function getEntity($id = null)
    {
        if ($id === null) {
            return new ScoringCategory();
        }
        
        return parent::getEntity($id);
    }

    /**
     * {@inheritdoc}
     *
     * @throws MethodNotAllowedHttpException
     */
    protected function dispatchEvent($action, &$entity, $isNew = false, Event $event = null)
    {
        if (!$entity instanceof ScoringCategory) {
            throw new MethodNotAllowedHttpException(['ScoringCategory']);
        }

        switch ($action) {
            case 'pre_save':
                $name = ScoringEvents::SCORINGCATEGORY_PRE_SAVE;
                break;
            case 'post_save':
                $name = ScoringEvents::SCORINGCATEGORY_POST_SAVE;
                break;
            case 'pre_delete':
                $name = ScoringEvents::SCORINGCATEGORY_PRE_DELETE;
                break;
            case 'post_delete':
                $name = ScoringEvents::SCORINGCATEGORY_POST_DELETE;
                break;
            default:
                return null;
        }

        if ($this->dispatcher->hasListeners($name)) {
            if (empty($event)) {
                $event = new ScoringCategoryEvent($entity, $isNew);
                $event->setEntityManager($this->em);
            }

            $this->dispatcher->dispatch($name, $event);

            return $event;
        }

        return null;
    }

    /**
     * Gets array of custom actions from bundles subscribed ScoringEvents::SCORINGCATEGORY_ON_BUILD.
     *
     * @return mixed
     */
    public function getScoringCategoryActions()
    {
        static $actions;

        if (empty($actions)) {
            //build them
            $actions = [];
            $event   = new ScoringCategoryBuilderEvent($this->translator);
            $this->dispatcher->dispatch(ScoringEvents::SCORINGCATEGORY_ON_BUILD, $event);
            $actions['actions'] = $event->getActions();
            $actions['list']    = $event->getActionList();
            $actions['choices'] = $event->getActionChoices();
        }

        return $actions;
    }
}
