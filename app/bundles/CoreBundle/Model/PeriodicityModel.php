<?php

/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
namespace Mautic\CoreBundle\Model;

use Mautic\CoreBundle\Entity\Periodicity;

/**
 * Class PeriodicityModel
 */
class PeriodicityModel extends CommonModel
{

    /**
     *
     * {@inheritdoc}
     *
     * @return \Mautic\CoreBundle\Entity\PeriodicityRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository('MauticCoreBundle:Periodicity');
    }
}
