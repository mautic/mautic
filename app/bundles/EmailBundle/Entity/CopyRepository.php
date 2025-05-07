<?php

namespace Mautic\EmailBundle\Entity;

use Doctrine\ORM\NoResultException;
use Mautic\CoreBundle\Entity\CommonRepository;

/**
 * @extends CommonRepository<Copy>
 */
class CopyRepository extends CommonRepository
{
    /**
     * @param string $hash
     * @param string $subject
     * @param string $body
     * @param string $bodyText
     */
    public function saveCopy($hash, $subject, $body, $bodyText): bool
    {
        $db = $this->getEntityManager()->getConnection();

        try {
            $db->insert(
                MAUTIC_TABLE_PREFIX.'email_copies',
                [
                    'id'           => $hash,
                    'body'         => $body,
                    'body_text'    => $bodyText,
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
     * @param string $string md5 hash or content
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
        $hash = (32 !== strlen($string)) ? $hash = md5($string) : $string;

        $q = $this->createQueryBuilder($this->getTableAlias());
        $q->where(
            $q->expr()->eq($this->getTableAlias().'.id', ':id')
        )
            ->setParameter('id', $hash);

        try {
            $result = $q->getQuery()->getSingleResult();
        } catch (NoResultException) {
            $result = null;
        }

        return $result;
    }

    public function getTableAlias(): string
    {
        return 'ec';
    }
}
