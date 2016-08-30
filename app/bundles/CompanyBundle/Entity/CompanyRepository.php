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
    public function getCompanies($user = false, $id = '')
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

    /**
     * Get a count of leads that belong to the company
     *
     * @param $companyIds
     *
     * @return array
     */
    public function getLeadCount($companyIds)
    {
        $q = $this->_em->getConnection()->createQueryBuilder();

        $q->select('count(cl.lead_id) as thecount, cl.company_id')
            ->from(MAUTIC_TABLE_PREFIX.'companies_leads', 'cl');

        $returnArray = (is_array($companyIds));

        if (!$returnArray) {
            $companyIds = array($companyIds);
        }

        $q->where(
            $q->expr()->in('cl.company_id', $companyIds),
            $q->expr()->eq('cl.manually_removed', ':false')
        )
            ->setParameter('false', false, 'boolean')
            ->groupBy('cl.company_id');

        $result = $q->execute()->fetchAll();

        $return = array();
        foreach ($result as $r) {
            $return[$r['company_id']] = $r['thecount'];
        }

        // Ensure lists without leads have a value
        foreach ($companyIds as $l) {
            if (!isset($return[$l])) {
                $return[$l] = 0;
            }
        }

        return ($returnArray) ? $return : $return[$companyIds[0]];
    }
}
