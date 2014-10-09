<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\FormBundle\Model;

use Mautic\CoreBundle\Model\FormModel as CommonFormModel;
use Mautic\FormBundle\Entity\Action;

/**
 * Class ActionModel
 * {@inheritdoc}
 * @package Mautic\CoreBundle\Model\FormModel
 */
class ActionModel extends CommonFormModel
{

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getRepository()
    {
        return $this->em->getRepository('MauticFormBundle:Action');
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getPermissionBase()
    {
        return 'form:forms';
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
            return new Action();
        }

        $entity = parent::getEntity($id);

        return $entity;
    }
}