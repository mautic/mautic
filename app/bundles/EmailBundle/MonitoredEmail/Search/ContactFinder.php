<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\MonitoredEmail\Search;

use Mautic\EmailBundle\Entity\Stat;
use Mautic\EmailBundle\Entity\StatRepository;
use Mautic\EmailBundle\MonitoredEmail\Processor\Address;
use Mautic\LeadBundle\Entity\LeadRepository;
use Mautic\LeadBundle\Model\LeadModel;
use Psr\Log\LoggerInterface;

class ContactFinder
{
    /**
     * @var StatRepository
     */
    protected $statRepository;

    /**
     * @var LeadRepository
     */
    protected $leadRepository;

    /**
     * @var LeadModel
     */
    protected $leadModel;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * ContactSearch constructor.
     *
     * @param StatRepository $statRepository
     * @param LeadRepository $leadRepository
     * @param LeadModel      $leadModel
     */
    public function __construct(StatRepository $statRepository, LeadRepository $leadRepository, LeadModel $leadModel, LoggerInterface $logger)
    {
        $this->statRepository = $statRepository;
        $this->leadRepository = $leadRepository;
        $this->leadModel      = $leadModel;
        $this->logger         = $logger;
    }

    /**
     * @param      $returnPathEmail
     * @param null $contactEmail
     *
     * @return Result
     */
    public function find($contactEmail, $returnPathEmail = null)
    {
        $result = new Result();

        $this->logger->debug("MONITORED EMAIL: Searching for a contact $contactEmail/$returnPathEmail");

        // We have a return path email so let's figure out who it originated to
        if ($returnPathEmail && $hash = Address::parseAddressForStatHash($returnPathEmail)) {
            /** @var Stat $stat */
            $stat = $this->statRepository->findOneBy(['trackingHash' => $hash]);
            $this->logger->debug("MONITORED EMAIL: HashId of $hash found in return path");
            if ($stat) {
                $this->logger->debug("MONITORED EMAIL: Stat ID {$stat->getId()} found for hash $hash");
                $result->setStat($stat);
            }
        }

        if (!$result->getContacts()) {
            // Search by email address
            if ($contacts = $this->leadRepository->getContactsByEmail($contactEmail)) {
                $result->setContacts($contacts);
                $this->logger->debug('MONITORED EMAIL: '.count($contacts).' contacts found');
            }
        }

        return $result;
    }
}
