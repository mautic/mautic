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

use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Executioner\Exception\NoEventsFound;
use Mautic\CampaignBundle\Executioner\Result\Counter;
use Mautic\CoreBundle\Helper\ProgressBarHelper;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;

class InactiveExecutioner implements ExecutionerInterface
{
    private $campaign;
    private $contactId;
    private $batchLimit;
    private $output;
    private $logger;
    private $progressBar;
    private $translator;

    public function executeForCampaign(Campaign $campaign, $batchLimit = 100, OutputInterface $output = null)
    {
        $this->campaign   = $campaign;
        $this->batchLimit = $batchLimit;
        $this->output     = ($output) ? $output : new NullOutput();

        $this->logger->debug('CAMPAIGN: Triggering inaction events');

        return $this->execute();
    }

    public function executeForContact(Campaign $campaign, $contactId, OutputInterface $output = null)
    {
        $this->campaign   = $campaign;
        $this->contactId  = $contactId;
        $this->output     = ($output) ? $output : new NullOutput();
        $this->batchLimit = null;

        return $this->execute();
    }

    /**
     * @return Counter
     *
     * @throws Dispatcher\Exception\LogNotProcessedException
     * @throws Dispatcher\Exception\LogPassedAndFailedException
     * @throws Scheduler\Exception\NotSchedulableException
     * @throws \Doctrine\ORM\Query\QueryException
     */
    private function execute()
    {
        $this->counter = new Counter();

        try {
            $this->prepareForExecution();
            $this->executeOrRecheduleEvent();
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
     * @throws NoEventsFound
     */
    private function prepareForExecution()
    {
        // Get counts by event
        $scheduledEvents       = $this->repo->getScheduledCounts($this->campaign->getId());
        $totalScheduledCount   = array_sum($scheduledEvents);
        $this->scheduledEvents = array_keys($scheduledEvents);
        $this->logger->debug('CAMPAIGN: '.$totalScheduledCount.' events scheduled to execute.');

        $this->output->writeln(
            $this->translator->trans(
                'mautic.campaign.trigger.event_count',
                [
                    '%events%' => $totalScheduledCount,
                    '%batch%'  => $this->batchLimit,
                ]
            )
        );

        $this->progressBar = ProgressBarHelper::init($this->output, $totalScheduledCount);
        $this->progressBar->start();

        if (!$totalScheduledCount) {
            throw new NoEventsFound();
        }
    }
}
