<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ApiBundle\Entity\oAuth2;

use Mautic\CoreBundle\Entity\CommonRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Mautic\UserBundle\Entity\User;

/**
 * ClientRepository
 */
class ClientRepository extends CommonRepository
{

    public function getUserClients(User $user)
    {
        $result = $this->createQueryBuilder('c')
            ->join('c.users', 'u')
            ->where("u.id = " . $user->getId())
            ->getQuery()
            ->getResult();
        return $result;
    }

    /**
     * {@inheritdoc}
     *
     * @param array $args
     * @return Paginator
     */
    public function getEntities($args = array())
    {
        $q = $this
            ->createQueryBuilder('c');

        if (!$this->buildClauses($q, $args)) {
            return array();
        }

        $query = $q->getQuery();
        $result = new Paginator($query);
        return $result;
    }

    protected function addCatchAllWhereClause(&$q, $filter)
    {
        $unique  = $this->generateRandomParameterName(); //ensure that the string has a unique parameter identifier
        $string  = ($filter->strict) ? $filter->string : "%{$filter->string}%";

        $expr = $q->expr()->orX(
            $q->expr()->like('c.name',  ':'.$unique),
            $q->expr()->like('c.redirectUris', ':'.$unique)
        );

        if ($filter->not) {
            $expr = $q->expr()->not($expr);
        }

        return array(
            $expr,
            array("$unique" => $string)
        );
    }

    protected function getDefaultOrder()
    {
        return array(
            array('c.name', 'ASC')
        );
    }

}
