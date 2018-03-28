<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Batches\Lead\ChangeChannelsAction;

use Mautic\LeadBundle\Model\LeadModel;

final class ChangeChannelsActionFactory implements ChangeChannelsActionFactoryInterface
{
    /**
     * @var LeadModel
     */
    private $leadModel;

    /**
     * ChangeChannelsActionFactory constructor.
     *
     * @param LeadModel $leadModel
     */
    public function __construct(LeadModel $leadModel)
    {
        $this->leadModel = $leadModel;
    }

    /**
     * @see ChangeChannelsActionFactoryInterface::create()
     * {@inheritdoc}
     */
    public function create(
        array $leadsIds,
        array $subscribedChannels,
        array $frequencyNumberEmail,
        array $frequencyTimeEmail,
        \DateTime $contactPauseStartDateEmail,
        \DateTime $contactPauseEndDateEmail
    ) {
        return new ChangeChannelsAction($leadsIds, $this->leadModel, $subscribedChannels, $frequencyNumberEmail, $frequencyTimeEmail, $contactPauseStartDateEmail, $contactPauseEndDateEmail);
    }
}
