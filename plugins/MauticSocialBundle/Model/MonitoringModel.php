<?php

/*
 * @copyright   2016 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticSocialBundle\Model;

use Mautic\CoreBundle\Model\FormModel;
use MauticPlugin\MauticSocialBundle\Entity\Monitoring;
use MauticPlugin\MauticSocialBundle\Event as Events;
use MauticPlugin\MauticSocialBundle\SocialEvents;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

/**
 * Class MonitoringModel
 * {@inheritdoc}
 */
class MonitoringModel extends FormModel
{
    /**
     * {@inheritdoc}
     *
     * @param       $entity
     * @param       $formFactory
     * @param null  $action
     * @param array $options
     *
     * @return mixed
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function createForm($entity, $formFactory, $action = null, $params = [])
    {
        if (!$entity instanceof Monitoring) {
            throw new MethodNotAllowedHttpException(['Monitoring']);
        }

        if (!empty($action)) {
            $params['action'] = $action;
        }

        return $formFactory->create('monitoring', $entity, $params);
    }

    /**
     * Get a specific entity or generate a new one if id is empty.
     *
     * @param $id
     *
     * @return null|Monitoring
     */
    public function getEntity($id = null)
    {
        return $id ? parent::getEntity($id) : new Monitoring();
    }

    /**
     * {@inheritdoc}
     *
     * @param $action
     * @param $event
     * @param $entity
     * @param $isNew
     *
     * @throws \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException
     */
    protected function dispatchEvent($action, &$entity, $isNew = false, Event $event = null)
    {
        if (!$entity instanceof Monitoring) {
            throw new MethodNotAllowedHttpException(['Monitoring']);
        }

        switch ($action) {
            case 'pre_save':
                $name = SocialEvents::MONITOR_PRE_SAVE;
                break;
            case 'post_save':
                $name = SocialEvents::MONITOR_POST_SAVE;
                break;
            case 'pre_delete':
                $name = SocialEvents::MONITOR_PRE_DELETE;
                break;
            case 'post_delete':
                $name = SocialEvents::MONITOR_POST_DELETE;
                break;
            default:
                return null;
        }

        if ($this->dispatcher->hasListeners($name)) {
            if (empty($event)) {
                $event = new Events\SocialEvent($entity, $isNew);
            }

            $this->dispatcher->dispatch($name, $event);

            return $event;
        } else {
            return null;
        }
    }

    /**
     * {@inheritdoc}
     *
     * @var \MauticPlugin\MauticSocialBundle\Entity\Monitoring
     */
    public function saveEntity($monitoringEntity, $unlock = true)
    {
        // we're editing an existing record
        if (!$monitoringEntity->isNew()) {
            //increase the revision
            $revision = $monitoringEntity->getRevision();
            ++$revision;
            $monitoringEntity->setRevision($revision);
        } // is new
        else {
            $now = new \DateTime();
            $monitoringEntity->setDateAdded($now);
        }

        parent::saveEntity($monitoringEntity, $unlock);
    }

    /**
     * {@inheritdoc}
     */
    public function getRepository()
    {
        return $this->em->getRepository('MauticSocialBundle:Monitoring');
    }

    /**
     * @return string
     */
    public function getPermissionBase()
    {
        return 'plugin:mauticSocial:monitoring';
    }

    /**
     * @return array
     */
    public function getNetworkTypes()
    {
        $types = [
            'twitter_handle'  => 'mautic.social.monitoring.type.list.twitter.handle',
            'twitter_hashtag' => 'mautic.social.monitoring.type.list.twitter.hashtag',
        ];

        return $types;
    }
}
