<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Model;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\LeadBundle\Model\CompanyModel;
use Mautic\LeadBundle\Model\LeadModel;

/**
 * Class MessageQueueModel.
 *
 * @deprecated 2.4; to be removed in 3.0
 */
class MessageQueueModel extends \Mautic\ChannelBundle\Model\MessageQueueModel
{
    /**
     * MessageQueueModel constructor.
     *
     * @param LeadModel            $leadModel
     * @param CompanyModel         $companyModel
     * @param CoreParametersHelper $coreParametersHelper
     */
    public function __construct(LeadModel $leadModel, CompanyModel $companyModel, CoreParametersHelper $coreParametersHelper)
    {
        @trigger_error('Mautic\CoreBundle\Model\MessageQueueModel was deprecated in 2.4 and to be removed in 3.0 Use \Mautic\ChannelBundle\Model\MessageQueueModel instead', E_USER_DEPRECATED);

        $this->leadModel            = $leadModel;
        $this->companyModel         = $companyModel;
        $this->coreParametersHelper = $coreParametersHelper;
    }
}
