<?php

namespace Mautic\EmailBundle\MonitoredEmail\Search;

use Mautic\EmailBundle\Entity\Stat;
use Mautic\EmailBundle\Entity\StatRepository;
use Mautic\EmailBundle\MonitoredEmail\Processor\Address;
use Mautic\LeadBundle\Entity\LeadRepository;
use Psr\Log\LoggerInterface;

class ContactFinder
{
    public function __construct(
        private StatRepository $statRepository,
        private LeadRepository $leadRepository,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @param string $returnPathEmail
     *
     * @return Result
     */
    public function find($contactEmail, $returnPathEmail = null)
    {
        $this->logger->debug("MONITORED EMAIL: Searching for a contact $contactEmail/$returnPathEmail");

        // We have a return path email so let's figure out who it originated to
        if ($returnPathEmail && $hash = Address::parseAddressForStatHash($returnPathEmail)) {
            $result = $this->findByHash($hash);
            if ($result->getStat()) {
                // A stat was found so need to search by email
                return $result;
            }
        }

        return $this->findByAddress($contactEmail);
    }

    /**
     * @param string $hash
     */
    public function findByHash($hash): Result
    {
        $result = new Result();
        $this->logger->debug('MONITORED EMAIL: Searching for a contact by hash '.$hash);

        /** @var Stat $stat */
        $stat = $this->statRepository->findOneBy(['trackingHash' => $hash]);
        $this->logger->debug("MONITORED EMAIL: HashId of $hash found in return path");
        if ($stat && $stat->getLead()) {
            $this->logger->debug("MONITORED EMAIL: Stat ID {$stat->getId()} found for hash $hash");
            $result->setStat($stat);
        }

        return $result;
    }

    public function findByAddress($address): Result
    {
        $result = new Result();
        // Search by email address
        if ($contacts = $this->leadRepository->getContactsByEmail($address)) {
            $result->setContacts($contacts);
            $this->logger->debug('MONITORED EMAIL: '.count($contacts).' contacts found');
        }

        return $result;
    }
}
