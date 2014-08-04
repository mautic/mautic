<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ChatBundle\Model;

use Mautic\ChatBundle\Entity\Channel;
use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\CoreBundle\Model\FormModel;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

/**
 * Class ChannelModel
 * {@inheritdoc}
 * @package Mautic\CoreBundle\Model\FormModel
 */
class ChannelModel extends FormModel
{

    /**
     * {@inheritdoc}
     *
     * @return \Mautic\ChatBundle\Entity\ChannelRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository('MauticChatBundle:Channel');
    }

    /**
     * @return array
     */
    public function getMyChannels()
    {
        return $this->getRepository()->getUserChannels(
            $this->factory->getUser()
        );
    }

    /**
     * {@inheritdoc}
     *
     * @param      $entity
     * @param      $formFactory
     * @param null $action
     * @param array $options
     * @return mixed
     */
    public function createForm($entity, $formFactory, $action = null, $options = array())
    {
        if ($entity == null) {
            $entity = new Channel();
        }

        $params = (!empty($action)) ? array('action' => $action) : array();
        return $formFactory->create('chatchannel', $entity, $params);
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
            return new Channel();
        }

        $entity = parent::getEntity($id);

        return $entity;
    }


    /**
     * @param       $entity
     * @param       $unlock
     * @return mixed
     */
    public function saveEntity($entity, $unlock = true)
    {
        if (!$entity instanceof Channel) {
            throw new MethodNotAllowedHttpException(array('Channel'));
        }

        $isNew = ($entity->getId()) ? false : true;

        //set some defaults
        $this->setTimestamps($entity, $isNew, $unlock);

        $name = $entity->getName();
        $name = strtolower(InputHelper::alphanum($name));
            //remove appended numbers
        $name = preg_replace('#[0-9]+$#', '', $name);

        //make sure alias is not already taken
        $repo      = $this->getRepository();
        $testName  = $name;
        $names     = $repo->getNames($entity->getId());
        $count     = (int)in_array($testName, $names);
        $nameTag   = $count;

        while ($count) {
            $testAlias = $testName . $nameTag;
            $count     = (int)in_array($testAlias, $names);
            $nameTag++;
        }

        if ($testName != $name) {
            $name = $testName;
        }
        $entity->setName($name);

        $event = $this->dispatchEvent("pre_save", $entity, $isNew);
        $this->getRepository()->saveEntity($entity);
        $this->dispatchEvent("post_save", $entity, $isNew, $event);
    }

    /**
     * Archive the channel
     *
     * @param  $entity
     */
    public function archiveChannel($entity)
    {
        $this->getRepository()->archiveChannel($entity->getId());
    }
}
