<?php

/*
 * @copyright   2015 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Entity;

use Doctrine\ORM\NoResultException;
use Mautic\CoreBundle\Entity\CommonRepository;
use Mautic\CoreBundle\Helper\EmojiHelper;

/**
 * Class CopyRepository.
 */
class CopyRepository extends CommonRepository
{
    /**
     * @param $hash
     * @param $subject
     * @param $body
     */
    public function saveCopy($hash, $subject, $body)
    {
        $db = $this->getEntityManager()->getConnection();

        try {
            $body    = EmojiHelper::toShort($body);
            $subject = EmojiHelper::toShort($subject);
            $db->insert(
                MAUTIC_TABLE_PREFIX.'email_copies',
                [
                    'id'           => $hash,
                    'body'         => $body,
                    'subject'      => $subject,
                    'date_created' => (new \DateTime())->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s'),
                ]
            );

            return true;
        } catch (\Exception $e) {
            error_log($e);

            return false;
        }
    }

    /**
     * @param string $string  md5 hash or content
     * @param null   $subject If $string is the content, pass the subject to include it in the hash
     *
     * @return array
     */
    public function findByHash($string, $subject = null)
    {
        if (null !== $subject) {
            // Combine subject with $string and hash together
            $string = $subject.$string;
        }

        // Assume that $string is already a md5 hash if 32 characters
        $hash = (strlen($string) !== 32) ? $hash = md5($string) : $string;

        $q = $this->createQueryBuilder($this->getTableAlias());
        $q->where(
            $q->expr()->eq($this->getTableAlias().'.id', ':id')
        )
            ->setParameter('id', $hash);

        try {
            $result = $q->getQuery()->getSingleResult();
        } catch (NoResultException $exception) {
            $result = null;
        }

        return $result;
    }

    /**
     * @return string
     */
    public function getTableAlias()
    {
        return 'ec';
    }
}
