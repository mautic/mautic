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
use Symfony\Component\Console\Output\OutputInterface;

interface ExecutionerInterface
{
    /**
     * @param Campaign             $campaign
     * @param                      $contactId
     * @param OutputInterface|null $output
     *
     * @return mixed
     */
    public function executeForContact(Campaign $campaign, $contactId, OutputInterface $output = null);

    /**
     * @param Campaign             $campaign
     * @param int                  $batchLimit
     * @param OutputInterface|null $output
     *
     * @return mixed
     */
    public function executeForCampaign(Campaign $campaign, $batchLimit = 100, OutputInterface $output = null);
}
