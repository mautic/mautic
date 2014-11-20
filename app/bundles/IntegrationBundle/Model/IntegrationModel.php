<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\IntegrationBundle\Model;

use Mautic\CoreBundle\Model\FormModel;

/**
 * Class IntegrationModel
 */
class IntegrationModel extends FormModel
{

    /**
     * {@inheritdoc}
     *
     * @return \Mautic\IntegrationBundle\Entity\IntegrationRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository('MauticIntegrationBundle:Integration');
    }

    /**
     * {@inheritdoc}
     */
    public function getPermissionBase()
    {
        return 'integration:integrations';
    }

    /**
     * {@inheritdoc}
     */
    public function getNameGetter()
    {
        return 'getName';
    }
}
