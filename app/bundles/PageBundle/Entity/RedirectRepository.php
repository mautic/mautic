<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PageBundle\Entity;

use Doctrine\ORM\Query;
use Mautic\CoreBundle\Entity\CommonRepository;
use Mautic\EmailBundle\Entity\Email;

/**
 * Class RedirectRepository
 */
class RedirectRepository extends CommonRepository
{
    /**
     * @param $source
     * @param $id
     *
     * @return mixed
     */
    public function findBySource($source, $id)
    {
        $column = ($source == 'email') ? 'r.email_id' : 'r.source';

        $q = $this->_em->getConnection()->createQueryBuilder();

        return $q->select('r.redirect_id, r.url, r.hits, r.unique_hits')
            ->from(MAUTIC_TABLE_PREFIX.'page_redirects', 'r')
            ->where(
                $q->expr()->eq($column, (int) $id)
            )
            ->orderBy('r.url')
            ->execute()->fetchAll();
    }

    /**
     * @param array $urls
     * @param Email $email
     *
     * @return array
     */
    public function findByUrls(array $urls, Email $email = null)
    {
        $q = $this->createQueryBuilder('r');

        $expr = $q->expr()->andX(
            $q->expr()->in('r.url', ':urls')
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
}
