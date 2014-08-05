<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Entity;

use Doctrine\ORM\Tools\Pagination\Paginator;
use Mautic\CoreBundle\Entity\CommonRepository;
use Doctrine\ORM\Query;

/**
 * LeadFieldRepository
 */
class LeadFieldRepository extends CommonRepository
{

    /**
     * Get a list of entities
     *
     * @param array      $args
     * @return Paginator
     */
    public function getEntities($args = array())
    {
        $q = $this->_em
            ->createQueryBuilder('f')
            ->select('f')
            ->from('MauticLeadBundle:LeadField', 'f', 'f.alias');
        $this->buildWhereClause($q, $args);

        if (isset($args['hydration_mode'])) {
            $mode = strtoupper($args['hydration_mode']);
            $results = $q->getQuery()->getResult(constant("\\Doctrine\\ORM\\Query::$mode"));
        } else {
            $results = $q->getQuery()->getResult();
        }

        return $results;
    }

    /**
     * Retrieves array of aliases used to ensure unique alias for new fields
     *
     * @param $exludingId
     * @return array
     */
    public function getAliases($exludingId)
    {
        $q = $this->createQueryBuilder('l')
            ->select('l.alias');
        if (!empty($exludingId)) {
        $q->where('l.id != :id')
            ->setParameter('id', $exludingId);
        }

        $results = $q->getQuery()->getArrayResult();
        $aliases = array();
        foreach($results as $item) {
            $aliases[] = $item['alias'];
        }

        //add lead main column names to prevent attempt to create a field with the same name
        $leadRepo = $this->_em->getRepository('MauticLeadBundle:Lead')->getBaseColumns(true);
        $aliases = array_merge($aliases, $leadRepo);

        return $aliases;
    }
}
