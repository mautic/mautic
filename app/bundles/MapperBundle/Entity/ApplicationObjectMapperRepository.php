<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\MapperBundle\Entity;

use Mautic\CoreBundle\Entity\CommonRepository;

/**
 * ApplicationObjectMapperRepository
 */
class ApplicationObjectMapperRepository extends CommonRepository
{
    /**
     * @param $application_client_id
     * @param $object
     * @return mixed
     */
    public function getByClientAndObject($application_client_id, $object)
    {
        $q = $this->createQueryBuilder('e')
            ->select('e.id')
            ->where('e.application_client_id = :application_id')
            ->where('e.object_name = :object');
        $q->setParameter('application_id', $application_client_id);
        $q->setParameter('object', $object);

        $results = $q->getQuery()->getSingleResult();
        return $results['id'];
    }
}
