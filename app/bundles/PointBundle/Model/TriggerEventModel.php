<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PointBundle\Model;

use Mautic\CoreBundle\Model\FormModel as CommonFormModel;
use Mautic\PointBundle\Entity\TriggerEvent;

/**
 * Class TriggerEventModel
 * {@inheritdoc}
 * @package Mautic\CoreBundle\Model\FormModel
 */
class TriggerEventModel extends CommonFormModel
{

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getRepository()
    {
        return $this->em->getRepository('MauticPointBundle:TriggerEvent');
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getPermissionBase()
    {
        return 'point:triggers';
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
            return new TriggerEvent();
        }

        $entity = parent::getEntity($id);

        return $entity;
    }
}