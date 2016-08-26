<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributorcomp. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CompanyBundle\Entity;

use Doctrine\ORM\Query;
use Mautic\CoreBundle\Entity\CommonRepository;

/**
 * Class CompanyRepository
 */
class CompanyRepository extends CommonRepository
{

    /**
     * {@inheritdoc}
     */
    public function getEntities($args = array())
    {
        $q = $this
            ->createQueryBuilder($this->getTableAlias());

        $args['qb'] = $q;

        return parent::getEntities($args);
    }

    /**
     * {@inheritdoc}
     */
    public function getTableAlias()
    {
        return 'comp';
    }


    /**
     * {@inheritdoc}
     */
    protected function addCatchAllWhereClause(&$q, $filter)
    {
        return $this->addStandardCatchAllWhereClause($q, $filter, array(
            'comp.name',
            'comp.description'
        ));
    }

    /**
     * {@inheritdoc}
     */
    protected function addSearchCommandWhereClause(&$q, $filter)
    {
        return $this->addStandardSearchCommandWhereClause($q, $filter);
    }

    /**
     * {@inheritdoc}
     */
    public function getSearchCommands()
    {
        return $this->getStandardSearchCommands();
    }

    /**
     * Get a list of lists
     *
     * @param bool   $user
     * @param string $alias
     * @param string $id
     *
     * @return array
     */
    public function getCompanys($user = false, $id = '')
    {
        static $companys = array();

        if (is_object($user)) {
            $user = $user->getId();
        }

        $key = (int) $user.$id;
        if (isset($companys[$key])) {
            return $companys[$key];
        }

        $q = $this->_em->createQueryBuilder()
            ->from('MauticCompanyBundle:Company', 'comp', 'comp.id');

        $q->select('partial comp.{id, name}');

        if (!empty($user)) {
            $q->orWhere('comp.createdBy = :user');
            $q->setParameter('user', $user);
        }

        if (!empty($id)) {
            $q->andWhere(
                $q->expr()->neq('comp.id', $id)
            );
        }

        $q->orderBy('comp.name');

        $results = $q->getQuery()->getArrayResult();

        $companys[$key] = $results;

        return $results;
    }

    /**
     * Get a list of lists
     *
     * @param string $name
     *
     * @return array
     */
    public function getCompanyByName($companyName)
    {
        static $companys = array();

        if (!$companyName) {
            return false;
        }

        $q = $this->_em->createQueryBuilder()
            ->from('MauticCompanyBundle:Company', 'comp', 'comp.id');

        $q->select('partial comp.{id, name}');
        $q->andWhere(
            $q->expr()->like('comp.name', $companyName)
        );

        $results = $q->getQuery()->getArrayResult();

        return $results;
    }
}
