<?php

namespace Mautic\CampaignBundle\Executioner;

use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Executioner\ContactFinder\Limiter\ContactLimiter;
use Symfony\Component\Console\Output\OutputInterface;

interface ExecutionerInterface
{
    /**
     * @return mixed
     */
    public function execute(Campaign $campaign, ContactLimiter $limiter, OutputInterface $output = null);
}
