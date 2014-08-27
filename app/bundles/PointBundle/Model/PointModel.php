<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PointBundle\Model;

use Mautic\CoreBundle\Model\FormModel;

/**
 * Class PointModel
 *
 * {@inheritdoc}
 * @package Mautic\CoreBundle\Model\FormModel
 */
class PointModel extends FormModel
{

    public function getRepository()
    {
        return $this->em->getRepository('MauticPointBundle:Point');
    }

    public function getPermissionBase()
    {
        return 'point:points';
    }
}