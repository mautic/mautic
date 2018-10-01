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
     * Rather pre-save theme here, catch the exception and set the IP ID back to the entity.
     *
     * @param Lead $contact
     */
    public function saveIpAddressesReferencesForContact(Lead $contact)
    {
        foreach ($contact->getIpAddresses() as $key => $ipAddress) {
            if ($ipAddress->getId() && $contact->getId()) {
                $this->insertIpAddressReference($ipAddress->getId(), $contact->getId());

                // Remove the IP Address from the Lead entity as it has been handled here.
                $contact->getIpAddresses()->remove($key);
            }
        }
    }

    private function insertIpAddressReference($contactId, $ipId)
    {
        $qb     = $this->em->getConnection()->createQueryBuilder();
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
