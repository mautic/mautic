<?php

namespace Mautic\SmsBundle\Api;

use Mautic\LeadBundle\Entity\Lead;
use Mautic\PageBundle\Model\TrackableModel;
use Mautic\SmsBundle\Sms\TransportInterface;

/**
 * @deprecated use TransportInterface instead
 */
abstract class AbstractSmsApi implements TransportInterface
{
    public function __construct(
        protected TrackableModel $pageTrackableModel,
    ) {
    }

    /**
     * @param string $content
     *
     * @return mixed
     */
    abstract public function sendSms(Lead $lead, $content);

    /**
     * Convert a non-tracked url to a tracked url.
     *
     * @param string $url
     *
     * @return string
     */
    public function convertToTrackedUrl($url, array $clickthrough = [])
    {
        /* @var \Mautic\PageBundle\Entity\Redirect $redirect */
        $trackable = $this->pageTrackableModel->getTrackableByUrl($url, 'sms', $clickthrough['sms']);

        return $this->pageTrackableModel->generateTrackableUrl($trackable, $clickthrough, true);
    }
}
