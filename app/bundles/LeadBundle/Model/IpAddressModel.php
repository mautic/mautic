<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Model;

use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\Entity\IpAddress;
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
     * Rather pre-save the IP references here and catch the exception.
     *
     * @param Lead $contact
     */
    public function saveIpAddressesReferencesForContact(Lead $contact)
    {
        foreach ($contact->getIpAddresses() as $key => $ipAddress) {
            $this->insertIpAddressReference($contact, $ipAddress);
        }
    }

    /**
     * Tries to insert the Lead/IP relation and continues even if UniqueConstraintViolationException is thrown.
     *
     * @param Lead      $contact
     * @param IpAddress $ipAddress
     */
    private function insertIpAddressReference(Lead $contact, IpAddress $ipAddress)
    {
        $ipAddressAdded = isset($contact->getChanges()['ipAddressList'][$ipAddress->getIpAddress()]);
        if (!$ipAddressAdded || !$ipAddress->getId() || !$contact->getId()) {
            return;
        }

        $qb     = $this->entityManager->getConnection()->createQueryBuilder();
        $values = [
            'lead_id' => ':leadId',
            'ip_id'   => ':ipId',
        ];

        $qb->insert(MAUTIC_TABLE_PREFIX.'lead_ips_xref');
        $qb->values($values);
        $qb->setParameter('leadId', $contact->getId());
        $qb->setParameter('ipId', $ipAddress->getId());

        try {
            $qb->execute();
        } catch (UniqueConstraintViolationException $e) {
            $this->logger->warning("The reference for contact {$contact->getId()} and IP address {$ipAddress->getId()} is already there. (Unique constraint)");
        } catch (ForeignKeyConstraintViolationException $e) {
            $this->logger->warning("The reference for contact {$contact->getId()} and IP address {$ipAddress->getId()} is already there. (Foreign key constraint)");
        }

        $this->entityManager->detach($ipAddress);
    }
}
