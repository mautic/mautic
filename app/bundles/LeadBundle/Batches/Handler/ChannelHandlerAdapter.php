<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Batches\Handler;

use Mautic\CoreBundle\Batches\Adapter\HandlerAdapterInterface;
use Mautic\CoreBundle\Batches\Exception\BatchActionFailException;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\LeadModel;
use Symfony\Component\HttpFoundation\Request;

class ChannelHandlerAdapter implements HandlerAdapterInterface
{
    /**
     * @var LeadModel
     */
    private $leadModel;

    /**
     * @var string[]
     */
    private $subscribedChannels = [];

    /**
     * @var int
     */
    private $frequencyNumberEmail;

    /**
     * @var string
     */
    private $frequencyTimeEmail;

    /**
     * @var \DateTime
     */
    private $contactPauseStartDateEmail;

    /**
     * @var \DateTime
     */
    private $contactPauseEndDateEmail;

    /**
     * ChannelHandlerAdapter constructor.
     *
     * @param LeadModel $leadModel
     */
    public function __construct(LeadModel $leadModel)
    {
        $this->leadModel = $leadModel;
    }

    /**
     * @see HandlerAdapterInterface::getParameters()
     * {@inheritdoc}
     */
    public function getParameters(Request $request)
    {
        return $request->get('lead_contact_channels', []);
    }

    /**
     * @see HandlerAdapterInterface::loadSettings()
     * {@inheritdoc}
     */
    public function loadSettings(array $settings)
    {
        $this->subscribedChannels = (isset($settings['subscribed_channels']) ? $settings['subscribed_channels'] : []);
        $this->frequencyNumberEmail = (isset($settings['frequency_number_email']) ? $settings['frequency_number_email'] : null);
        $this->frequencyTimeEmail = (isset($settings['frequency_time_email']) ? $settings['frequency_time_email'] : null);

        $this->contactPauseStartDateEmail = (isset($settings['contact_pause_start_date_email']) ? new \DateTime($settings['contact_pause_start_date_email']) : null);
        $this->contactPauseEndDateEmail = (isset($settings['contact_pause_end_date_email']) ? new \DateTime($settings['contact_pause_end_date_email']) : null);
    }

    /**
     * @see HandlerAdapterInterface::update()
     * {@inheritdoc}
     */
    public function update($object)
    {
        if ($object instanceof Lead) {
            return $this->updateLead($object);
        }

        throw BatchActionFailException::sourceInHandlerNotImplementedYet($object, $this);
    }

    /**
     * @see HandlerAdapterInterface::store()
     * {@inheritdoc}
     */
    public function store(array $objects)
    {
    }

    private function updateLead(Lead $lead)
    {
        dump($lead);
    }
}