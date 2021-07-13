<?php

declare(strict_types=1);

/*
 * @copyright   2021 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\Event;

use Mautic\CampaignBundle\Entity\Campaign;

final class DeleteCampaign extends \Symfony\Component\EventDispatcher\Event
{
    /**
     * @var Campaign
     */
    private $campaign;

    public function __construct(Campaign $campaign)
    {
        $this->campaign = $campaign;
    }

    public function getCampaign(): Campaign
    {
        return $this->campaign;
    }
}
