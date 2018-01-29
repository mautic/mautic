<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\Executioner;

use Doctrine\Common\Collections\ArrayCollection;
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Executioner\ContactFinder\KickoffContacts;
use Mautic\CampaignBundle\Executioner\Exception\NoContactsFound;
use Mautic\CampaignBundle\Executioner\Exception\NoEventsFound;
use Mautic\CampaignBundle\Executioner\Scheduler\EventScheduler;
use Mautic\CampaignBundle\Executioner\Scheduler\Exception\NotSchedulableException;
use Mautic\CoreBundle\Helper\ProgressBarHelper;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Translation\TranslatorInterface;

class KickoffExecutioner
{
    /**
     * @var null|int
     */
    private $contactId;

    /**
     * @var Campaign
     */
    private $campaign;

    /**
     * @var int
     */
    private $eventLimit = 100;

    /**
     * @var int|null
     */
    private $maxEventsToExecute;

    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var KickoffContacts
     */
    private $kickoffContacts;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var EventExecutioner
     */
    private $executioner;

    /**
     * @var EventScheduler
     */
    private $scheduler;

    /**
     * @var ProgressBar
     */
    private $progressBar;

    /**
     * @var int
     */
    private $batchCounter = 0;

    /**
     * @var ArrayCollection
     */
    private $rootEvents;

    /**
     * KickoffExecutioner constructor.
     *
     * @param LoggerInterface     $logger
     * @param KickoffContacts     $kickoffContacts
     * @param TranslatorInterface $translator
     * @param EventExecutioner    $executioner
     * @param EventScheduler      $scheduler
     */
    public function __construct(
        LoggerInterface $logger,
        KickoffContacts $kickoffContacts,
        TranslatorInterface $translator,
        EventExecutioner $executioner,
        EventScheduler $scheduler
    ) {
        $this->logger          = $logger;
        $this->kickoffContacts = $kickoffContacts;
        $this->translator      = $translator;
        $this->executioner     = $executioner;
        $this->scheduler       = $scheduler;
    }

    /**
     * @param Campaign             $campaign
     * @param int                  $eventLimit
     * @param null                 $maxEventsToExecute
     * @param OutputInterface|null $output
     *
     * @throws NoContactsFound
     * @throws NoEventsFound
     * @throws NotSchedulableException
     */
    public function executeForCampaign(Campaign $campaign, $eventLimit = 100, $maxEventsToExecute = null, OutputInterface $output = null)
    {
        $this->campaign           = $campaign;
        $this->contactId          = null;
        $this->eventLimit         = $eventLimit;
        $this->maxEventsToExecute = $maxEventsToExecute;
        $this->output             = ($output) ? $output : new NullOutput();

        $this->prepareForExecution();
        $this->executeOrSchedule();
    }

    /**
     * @param Campaign             $campaign
     * @param                      $contactId
     * @param OutputInterface|null $output
     *
     * @throws NoContactsFound
     * @throws NoEventsFound
     * @throws NotSchedulableException
     */
    public function executeForContact(Campaign $campaign, $contactId, OutputInterface $output = null)
    {
        $this->campaign  = $campaign;
        $this->contactId = $contactId;
        $this->output    = ($output) ? $output : new NullOutput();

        // Process all events for this contact
        $this->eventLimit         = null;
        $this->maxEventsToExecute = null;

        $this->prepareForExecution();
        $this->executeOrSchedule();
    }

    /**
     * @throws NoEventsFound
     */
    private function prepareForExecution()
    {
        $this->logger->debug('CAMPAIGN: Triggering kickoff events');

        defined('MAUTIC_CAMPAIGN_SYSTEM_TRIGGERED') or define('MAUTIC_CAMPAIGN_SYSTEM_TRIGGERED', 1);

        $this->batchCounter = 0;
        $this->rootEvents   = $this->campaign->getRootEvents();
        $totalRootEvents    = $this->rootEvents->count();
        $this->logger->debug('CAMPAIGN: Processing the following events: '.implode(', ', $this->rootEvents->getKeys()));

        $totalContacts      = $this->kickoffContacts->getContactCount($this->campaign->getId(), $this->rootEvents->getKeys(), $this->contactId);
        $totalKickoffEvents = $totalRootEvents * $totalContacts;
        $this->output->writeln(
            $this->translator->trans(
                'mautic.campaign.trigger.event_count',
                [
                    '%events%' => $totalKickoffEvents,
                    '%batch%'  => $this->eventLimit,
                ]
            )
        );

        if (!$totalKickoffEvents) {
            $this->logger->debug('CAMPAIGN: No contacts/events to process');

            throw new NoEventsFound();
        }

        if (!$this->maxEventsToExecute) {
            $this->maxEventsToExecute = $totalKickoffEvents;
        }

        $this->progressBar = ProgressBarHelper::init($this->output, $this->maxEventsToExecute);
        $this->progressBar->start();
    }

    /**
     * @throws Dispatcher\LogNotProcessedException
     * @throws Dispatcher\LogPassedAndFailedException
     * @throws NotSchedulableException
     */
    private function executeOrSchedule()
    {
        // Use the same timestamp across all contacts processed
        $now = new \DateTime();

        // Loop over contacts until the entire campaign is executed
        try {
            while ($contacts = $this->kickoffContacts->getContacts($this->campaign->getId(), $this->eventLimit, $this->contactId)) {
                /** @var Event $event */
                foreach ($this->rootEvents as $event) {
                    // Check if the event should be scheduled (let the scheduler's do the debug logging)
                    $executionDate = $this->scheduler->getExecutionDateTime($event, $now);
                    if ($executionDate > $now) {
                        $this->scheduler->schedule($event, $executionDate, $contacts);
                        continue;
                    }

                    // Execute the event for the batch of contacts
                    $this->executioner->execute($event, $contacts);
                }

                $this->kickoffContacts->clear();
            }
        } catch (NoContactsFound $exception) {
            // We're done here
        }
    }
}
