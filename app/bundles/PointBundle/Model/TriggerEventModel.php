<?php
/**
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
namespace Mautic\PointBundle\Model;

use Mautic\CoreBundle\Model\FormModel as CommonFormModel;
use Mautic\PointBundle\Entity\TriggerEvent;

/**
 * Class TriggerEventModel.
 */
class TriggerEventModel extends CommonFormModel
{
    /**
     * {@inheritdoc}
     *
     * @return \Mautic\PointBundle\Entity\TriggerEventRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository('MauticPointBundle:TriggerEvent');
    }

    /**
     * {@inheritdoc}
     */
    public function getPermissionBase()
    {
        return 'point:triggers';
    }

    /**
     * {@inheritdoc}
     *
     * @return TriggerEvent|null
     */
    public function getEntity($id = null)
    {
        if ($id === null) {
            return new TriggerEvent();
        }

        return parent::getEntity($id);
    }
}
