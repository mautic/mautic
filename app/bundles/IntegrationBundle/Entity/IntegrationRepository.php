<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\IntegrationBundle\Entity;

use Doctrine\ORM\Tools\Pagination\Paginator;
use Mautic\CoreBundle\Entity\CommonRepository;

/**
 * Class IntegrationRepository
 */
class IntegrationRepository extends CommonRepository
{

    /**
     * Find an integration record by bundle name
     *
     * @param string $bundle
     *
     * @return mixed
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findByBundle($bundle)
    {
        $q = $this->createQueryBuilder($this->getTableAlias());
        $q->where($q->expr()->eq('i.bundle', ':bundle'))
            ->setParameter('bundle', $bundle);

        return $q->getQuery()->getOneOrNullResult();
    }

    /**
     * Retrieves the enabled status of all addon bundles
     *
     * @return array
     */
    public function getBundleStatus()
    {
        $q = $this->createQueryBuilder($this->getTableAlias())
            ->select('i.bundle AS bundle, i.isEnabled AS enabled');

        $results = $q->getQuery()->getArrayResult();

        $return = array();

        foreach ($results as $result) {
            $return[$result['bundle']] = $result['enabled'];
        }

        return $return;
    }

    /**
     * {@inheritdoc}
     */
    public function getEntities($args = array())
    {
        $q = $this->createQueryBuilder($this->getTableAlias());

        $this->buildClauses($q, $args);

        $query = $q->getQuery();

        if (isset($args['hydration_mode'])) {
            $mode = strtoupper($args['hydration_mode']);
            $query->setHydrationMode(constant("\\Doctrine\\ORM\\Query::$mode"));
        }

        return new Paginator($query);
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultOrder()
    {
        return array(
            array('i.title', 'ASC')
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getTableAlias()
    {
        return 'i';
    }
}
