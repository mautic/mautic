<?php

declare(strict_types=1);

/*
 * @copyright   2021 Mautic. All rights reserved
 * @author      Mautic
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\Helper;

use Mautic\CoreBundle\Helper\CoreParametersHelper;

final class CampaignConfig
{
    /**
     * @var CoreParametersHelper
     */
    private $coreParametersHelper;

    public function __construct(CoreParametersHelper $coreParametersHelper)
    {
        $this->coreParametersHelper = $coreParametersHelper;
    }

    public function shouldDeleteEventLogInBackground(): bool
    {
        return (bool) $this->coreParametersHelper->get('delete_campaign_event_log_in_background', false);
    }
}
