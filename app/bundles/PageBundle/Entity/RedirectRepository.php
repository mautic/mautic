<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PageBundle\Entity;

use Mautic\CoreBundle\Entity\CommonRepository;
use Mautic\EmailBundle\Entity\Email;

/**
 * Class RedirectRepository.
 */
class RedirectRepository extends CommonRepository
{
    /**
     * @param array $urls
     *
     * @return array
     */
    public function findByUrls(array $urls)
    {
        $q = $this->createQueryBuilder('r');

        $expr = $q->expr()->andX(
            $q->expr()->in('r.url', ':urls')
        );

        $q->where($expr)
            ->setParameter('urls', $urls);

        return $q->getQuery()->getResult();
    }

    /**
     * @param array $ids
     * @param Email $email
     *
     * @return array
     */
    public function findByIds(array $ids, Email $email = null)
    {
        $q = $this->createQueryBuilder('r');

        $expr = $q->expr()->andX(
            $q->expr()->in('r.id', ':ids')
        );

        if ($email === null) {
            $expr->add(
                $q->expr()->isNull('r.email')
            );
        } else {
            $expr->add(
                $q->expr()->eq('r.email', ':email')
            );
            $q->setParameter('email', $email);
        }

        $q->where($expr)
            ->setParameter('ids', $ids);

        return $q->getQuery()->getResult();
    }

    /**
     * Up the hit count.
     *
     * @param            $id
     * @param int        $increaseBy
     * @param bool|false $unique
     */
    public function upHitCount($id, $increaseBy = 1, $unique = false)
    {
        $q = $this->getEntityManager()->getConnection()->createQueryBuilder();

        $q->update(MAUTIC_TABLE_PREFIX.'page_redirects')
            ->set('hits', 'hits + '.(int) $increaseBy)
            ->where('id = '.(int) $id);

        if ($unique) {
            $q->set('unique_hits', 'unique_hits + '.(int) $increaseBy);
        }

        $q->execute();
    }

    /**
     * @param int       $limit
     * @param \DateTime $dateFrom
     * @param \DateTime $dateTo
     * @param int|null  $createdByUserId
     * @param int|null  $companyId
     *
     * @return array
     */
    public function getMostHitEmailRedirects($limit, \DateTime $dateFrom, \DateTime $dateTo, $createdByUserId = null, $companyId = null)
    {
        $q = $this->_em->getConnection()->createQueryBuilder();
        $q->addSelect('pr.id')
            ->addSelect('pr.url')
            ->addSelect('pr.hits')
            ->addSelect('pr.unique_hits')
            ->from(MAUTIC_TABLE_PREFIX.'page_redirects', 'pr')
            ->innerJoin('pr', MAUTIC_TABLE_PREFIX.'page_hits', 'ph', 'pr.redirect_id = ph.redirect_id')
            ->innerJoin('ph', MAUTIC_TABLE_PREFIX.'emails', 'e', 'ph.email_id = e.id')
            ->addSelect('e.id AS email_id')
            ->addSelect('e.name AS email_name');
        if ($createdByUserId !== null) {
            $q->andWhere('e.created_by = :userId')
                ->setParameter('userId', $createdByUserId);
        }
        $q->andWhere('pr.date_added BETWEEN :dateFrom AND :dateTo')
            ->setParameter('dateFrom', $dateFrom->format('Y-m-d H:i:s'))
            ->setParameter('dateTo', $dateTo->format('Y-m-d H:i:s'));
        if ($companyId !== null) {
            $q->innerJoin('ph', MAUTIC_TABLE_PREFIX.'companies_leads', 'cl', 'ph.lead_id = cl.lead_id')
                ->andWhere('cl.company_id = :companyId')
                ->setParameter('companyId', $companyId);
        }
        $q->setMaxResults($limit);

        return $q->execute()->fetchAll();
    }
}
