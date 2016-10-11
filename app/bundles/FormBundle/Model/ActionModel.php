<?php
/**
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
}
