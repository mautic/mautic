<?php

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
     * @param LeadModel $leadModel
     */
    public function __construct(LeadModel $leadModel)
    {
        $this->leadModel = $leadModel;
    }

    /**
     * @see HandlerAdapterInterface::loadSettings()
     * {@inheritdoc}
     */
    public function loadSettings(Request $request)
    {
        $leadContactChannels = $request->get('lead_contact_channels', []);

        $this->subscribedChannels = (isset($leadContactChannels['subscribed_channels']) ? $leadContactChannels['subscribed_channels'] : []);
        $this->frequencyNumberEmail = (isset($leadContactChannels['frequency_number_email']) ? $leadContactChannels['frequency_number_email'] : null);
        $this->frequencyTimeEmail = (isset($leadContactChannels['frequency_time_email']) ? $leadContactChannels['frequency_time_email'] : null);

        $this->contactPauseStartDateEmail = (isset($leadContactChannels['contact_pause_start_date_email']) ? new \DateTime($leadContactChannels['contact_pause_start_date_email']) : null);
        $this->contactPauseEndDateEmail = (isset($leadContactChannels['contact_pause_end_date_email']) ? new \DateTime($leadContactChannels['contact_pause_end_date_email']) : null);
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