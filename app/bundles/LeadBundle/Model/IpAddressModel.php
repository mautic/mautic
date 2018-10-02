<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Model;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManager;
use Mautic\LeadBundle\Entity\Lead;
use Psr\Log\LoggerInterface;

class IpAddressModel
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @param EntityManager   $entityManager
     * @param LoggerInterface $logger
     */
    public function __construct(EntityManager $entityManager, LoggerInterface $logger)
    {
        $this->entityManager = $entityManager;
        $this->logger        = $logger;
    }

    /**
     * Saving IP Address references sometimes throws UniqueConstraintViolationException exception on Lead entity save.
     * Rather pre-save theme here, catch the exception and remove the IP from the Lead the entity.
     *
     * @param Lead $contact
     */
    public function saveIpAddressesReferencesForContact(Lead $contact)
    {
        $ipAddresses = $contact->getIpAddresses();

        foreach ($ipAddresses as $key => $ipAddress) {
            if ($ipAddress->getId() && $contact->getId()) {
                $this->insertIpAddressReference($ipAddress->getId(), $contact->getId());

                // Remove the IP Address from the Lead entity as it has been handled here.
                $ipAddresses->remove($key);
            }
        }
    }

    /**
     * Tries to insert the Lead/IP relation and continues even if UniqueConstraintViolationException is thrown.
     *
     * @param int $contactId
     * @param int $ipId
     */
    private function insertIpAddressReference($contactId, $ipId)
    {
        $qb     = $this->entityManager->getConnection()->createQueryBuilder();
        $values = [
            'lead_id' => ':leadId',
            'ip_id'   => ':ipId',
        ];

        $qb->insert(MAUTIC_TABLE_PREFIX.'lead_ips_xref');
        $qb->values($values);
        $qb->setParameter('leadId', $contactId);
        $qb->setParameter('ipId', $ipId);

        try {
            $qb->execute();
        } catch (UniqueConstraintViolationException $e) {
            $this->logger->warning("The reference for contact $contactId and IP address $ipId is already there.");
        }
    }
}
