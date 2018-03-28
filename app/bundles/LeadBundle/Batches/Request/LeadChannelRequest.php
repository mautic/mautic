<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Batches\Request;

use Mautic\CoreBundle\Batches\Request\BatchRequestInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class LeadChannelRequest implements BatchRequestInterface
{
    /**
     * @var array
     */
    private $parameters;

    /**
     * @see BatchRequestInterface::__construct()
     * {@inheritdoc}
     */
    public function __construct(RequestStack $requestStack)
    {
        $this->parameters = $requestStack->getCurrentRequest()->get('lead_contact_channels', [], true);
    }

    /**
     * @see BatchRequestInterface::getSourceIdList()
     * {@inheritdoc}
     */
    public function getSourceIdList()
    {
        return json_decode($this->parameters['ids'], true);
    }

    public function getSubscribedChannels()
    {
        return isset($this->parameters['subscribed_channels']) ? $this->parameters['subscribed_channels'] : [];
    }

    public function getFrequencyNumberEmail()
    {
        return isset($this->parameters['frequency_number_email']) ? $this->parameters['frequency_number_email'] : null;
    }

    public function getFrequencyTimeEmail()
    {
        return isset($this->parameters['frequency_time_email']) ? $this->parameters['frequency_time_email'] : null;
    }

    public function getContactPauseStartDateEmail()
    {
        return isset($this->parameters['contact_pause_start_date_email']) ? new \DateTime($this->parameters['contact_pause_start_date_email']) : null;
    }

    public function getContactPauseEndDateEmail()
    {
        return isset($this->parameters['contact_pause_end_date_email']) ? new \DateTime($this->parameters['contact_pause_end_date_email']) : null;
    }
}
