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
use Mautic\CampaignBundle\Executioner\ContactFinder\InactiveContacts;
use Mautic\CampaignBundle\Executioner\ContactFinder\Limiter\ContactLimiter;
use Mautic\CampaignBundle\Executioner\Exception\NoContactsFound;
use Mautic\CampaignBundle\Executioner\Exception\NoEventsFound;
use Mautic\CampaignBundle\Executioner\Helper\InactiveHelper;
use Mautic\CampaignBundle\Executioner\Result\Counter;
use Mautic\CampaignBundle\Executioner\Scheduler\EventScheduler;
use Mautic\CoreBundle\Helper\ProgressBarHelper;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Translation\TranslatorInterface;

class InactiveExecutioner implements ExecutionerInterface
{
    /**
     * @var Campaign
     */
    private $campaign;

    /**
     * @var ContactLimiter
     */
    private $limiter;

    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ProgressBar
     */
    private $progressBar;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var EventScheduler
     */
    private $scheduler;

    /**
     * @var EventExecutioner
     */
    private $executioner;

    /**
     * @var Counter
     */
    private $counter;

    /**
     * @var InactiveContacts
     */
    private $inactiveContacts;

    /**
     * @var ArrayCollection
     */
    private $decisions;

    /**
     * @var InactiveHelper
     */
    private $helper;

    /**
     * InactiveExecutioner constructor.
     *
     * @param InactiveContacts    $inactiveContacts
     * @param LoggerInterface     $logger
     * @param TranslatorInterface $translator
     * @param EventScheduler      $scheduler
     * @param InactiveHelper      $helper
     * @param EventExecutioner    $executioner
     */
    public function __construct(
        InactiveContacts $inactiveContacts,
        LoggerInterface $logger,
        TranslatorInterface $translator,
        EventScheduler $scheduler,
        InactiveHelper $helper,
        EventExecutioner $executioner
    ) {
        $this->inactiveContacts = $inactiveContacts;
        $this->logger           = $logger;
        $this->translator       = $translator;
        $this->scheduler        = $scheduler;
        $this->helper           = $helper;
        $this->executioner      = $executioner;
    }

    /**
     * @param Campaign             $campaign
     * @param ContactLimiter       $limiter
     * @param OutputInterface|null $output
     *
     * @return Counter|mixed
     *
     * @throws Dispatcher\Exception\LogNotProcessedException
     * @throws Dispatcher\Exception\LogPassedAndFailedException
     * @throws Exception\CannotProcessEventException
     * @throws Scheduler\Exception\NotSchedulableException
     */
    public function execute(Campaign $campaign, ContactLimiter $limiter, OutputInterface $output = null)
    {
        $this->campaign = $campaign;
        $this->limiter  = $limiter;
        $this->output   = ($output) ? $output : new NullOutput();
        $this->counter  = new Counter();

        try {
            $this->decisions = $this->campaign->getEventsByType(Event::TYPE_DECISION);

            $this->prepareForExecution();
            $this->executeEvents();
        } catch (NoContactsFound $exception) {
            $this->logger->debug('CAMPAIGN: No more contacts to process');
        } catch (NoEventsFound $exception) {
            $this->logger->debug('CAMPAIGN: No events to process');
        } finally {
            if ($this->progressBar) {
                $this->progressBar->finish();
                $this->output->writeln("\n");
            }
        }

        return $this->counter;
    }

    /**
     * @param                      $decisionId
     * @param ContactLimiter       $limiter
     * @param OutputInterface|null $output
     *
     * @return Counter
     *
     * @throws Dispatcher\Exception\LogNotProcessedException
     * @throws Dispatcher\Exception\LogPassedAndFailedException
     * @throws Exception\CannotProcessEventException
     * @throws Scheduler\Exception\NotSchedulableException
     */
    public function validate($decisionId, ContactLimiter $limiter, OutputInterface $output = null)
    {
        $this->limiter = $limiter;
        $this->output  = ($output) ? $output : new NullOutput();
        $this->counter = new Counter();

        try {
            $this->decisions = $this->helper->getCollectionByDecisionId($decisionId);
            if ($this->decisions->count()) {
                $this->campaign = $this->decisions->first()->getCampaign();
                if (!$this->campaign->isPublished()) {
                    throw new NoEventsFound();
                }
            }

            $this->prepareForExecution();
            $this->executeEvents();
        } catch (NoContactsFound $exception) {
            $this->logger->debug('CAMPAIGN: No more contacts to process');
        } catch (NoEventsFound $exception) {
            $this->logger->debug('CAMPAIGN: No events to process');
        } finally {
            if ($this->progressBar) {
                $this->progressBar->finish();
                $this->output->writeln("\n");
            }
        }

        return $this->counter;
    }

    /**
     * @throws NoContactsFound
     * @throws NoEventsFound
     */
    private function prepareForExecution()
    {
        $this->logger->debug('CAMPAIGN: Triggering inaction events');

        $this->helper->removeDecisionsWithoutNegativeChildren($this->decisions);

        $totalDecisions = $this->decisions->count();
        if (!$totalDecisions) {
            throw new NoEventsFound();
        }

        $totalContacts = $this->inactiveContacts->getContactCount($this->campaign->getId(), $this->decisions->getKeys(), $this->limiter);
        $this->output->writeln(
            $this->translator->trans(
                'mautic.campaign.trigger.decision_count_analyzed',
                [
                    '%decisions%' => $totalDecisions,
                    '%leads%'     => $totalContacts,
                    '%batch%'     => $this->limiter->getBatchLimit(),
                ]
            )
        );

        if (!$totalContacts) {
            throw new NoContactsFound();
        }

        // Approximate total count because the query to fetch contacts will filter out those that have not arrived to this point in the campaign yet
        $this->progressBar = ProgressBarHelper::init($this->output, $totalContacts * $totalDecisions);
        $this->progressBar->start();
    }

    /**
     * @throws Dispatcher\Exception\LogNotProcessedException
     * @throws Dispatcher\Exception\LogPassedAndFailedException
     * @throws Exception\CannotProcessEventException
     * @throws NoContactsFound
     * @throws Scheduler\Exception\NotSchedulableException
     */
    private function executeEvents()
    {
        // Use the same timestamp across all contacts processed
        $now = new \DateTime();

        /** @var Event $decisionEvent */
        foreach ($this->decisions as $decisionEvent) {
            // We need the parent ID of the decision in order to fetch the time the contact executed this event
            $parentEvent   = $decisionEvent->getParent();
            $parentEventId = ($parentEvent) ? $parentEvent->getId() : null;

            // Because timing may not be appropriate, the starting row of the query may or may not change.
            // So use the max contact ID to filter/sort results.
            $startAtContactId = $this->limiter->getMinContactId() ?: 0;

            // Ge the first batch of contacts
            $contacts = $this->inactiveContacts->getContacts($this->campaign->getId(), $decisionEvent, $startAtContactId, $this->limiter);

            // Loop over all contacts till we've processed all those applicable for this decision
            while ($contacts->count()) {
                // Get the max contact ID before any are removed
                $startAtContactId = max($contacts->getKeys());

                $this->progressBar->advance($contacts->count());
                $this->counter->advanceEvaluated($contacts->count());

                $inactiveEvents             = $decisionEvent->getNegativeChildren();
                $earliestLastActiveDateTime = $this->helper->removeContactsThatAreNotApplicable($now, $contacts, $parentEventId, $inactiveEvents);

                $this->logger->debug(
                    'CAMPAIGN: ('.$decisionEvent->getId().') Earliest date for inactivity for this batch of contacts is '.
                    $earliestLastActiveDateTime->format('Y-m-d H:i:s T')
                );

                if ($contacts->count()) {
                    // Execute or schedule the events attached to the inactive side of the decision
                    $this->executioner->executeContactsForInactiveChildren($inactiveEvents, $contacts, $this->counter, $earliestLastActiveDateTime);
                    // Record decision for these contacts
                    $this->executioner->recordLogsAsExecutedForEvent($decisionEvent, $contacts, true);
                }

                // Clear contacts from memory
                $this->inactiveContacts->clear();

                if ($this->limiter->getContactId()) {
                    // No use making another call
                    break;
                }

                // Get the next batch, starting with the max contact ID
                $contacts = $this->inactiveContacts->getContacts($this->campaign->getId(), $decisionEvent, $startAtContactId, $this->limiter);
            }
        }
    }
}
