<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PluginBundle\Model;

use Mautic\CoreBundle\Model\FormModel;

/**
 * Class PluginModel
 */
class PluginModel extends FormModel
{

    /**
     * {@inheritdoc}
     *
     * @return \Mautic\PluginBundle\Entity\PluginRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository('MauticPluginBundle:Plugin');
    }

    /**
     * {@inheritdoc}
     */
    public function getPermissionBase()
    {
        return 'plugin:plugins';
    }


    /**
     * Get lead fields used in selects/matching
     */
    public function getLeadFields()
    {
        return $this->factory->getModel('lead.field')->getFieldList();
    }
}
