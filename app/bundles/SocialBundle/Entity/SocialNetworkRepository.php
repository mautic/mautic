<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\SocialBundle\Entity;

use Mautic\CoreBundle\Entity\CommonRepository;

/**
 * SocialNetworkRepository
 */
class SocialNetworkRepository extends CommonRepository
{

    public function getNetworkSettings()
    {
        $services = $this->createQueryBuilder('s')
            ->getQuery()
            ->getResult();

        $results = array();
        foreach ($services as $s) {
            $results[$s->getName()] = $s;
        }
        return $results;
    }
}
