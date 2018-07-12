<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\SmsBundle\Api;

use Joomla\Http\Http;
use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\PageBundle\Model\TrackableModel;

abstract class AbstractSmsApi
{
    /**
     * @var MauticFactory
     */
    protected $pageTrackableModel;

    /**
     * AbstractSmsApi constructor.
     *
     * @param TrackableModel $pageTrackableModel
     */
    public function __construct(TrackableModel $pageTrackableModel)
    {
        $this->pageTrackableModel = $pageTrackableModel;
    }

    /**
     * @param Lead   $lead
     * @param string $content
     *
     * @return mixed
     */
    abstract public function sendSms(Lead $lead, $content);

    /**
     * Convert a non-tracked url to a tracked url.
     *
     * @param string $url
     * @param array  $clickthrough
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
