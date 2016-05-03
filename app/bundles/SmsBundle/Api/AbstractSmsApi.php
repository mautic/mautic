<?php
/**
 * @package     Mautic
 * @copyright   2016 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\SmsBundle\Api;

use Joomla\Http\Http;
use Joomla\Http\Response;
use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\LeadBundle\Entity\Lead;

abstract class AbstractSmsApi
{
    /**
     * @var MauticFactory
     */
    protected $factory;

    /**
     * @param MauticFactory $factory
     */
    public function __construct(MauticFactory $factory)
    {
        $this->factory = $factory;
    }

    /**
     * @param string $number
     * @param string $content
     *
     * @return mixed
     */
    abstract public function sendSms($number, $content);

    /**
     * Convert a non-tracked url to a tracked url
     *
     * @param string $url
     * @param array $clickthrough
     *
     * @return string
     */
    public function convertToTrackedUrl($url, array $clickthrough = array())
    {
        /** @var \Mautic\PageBundle\Model\TrackableModel $trackableModel */
        $trackableModel = $this->factory->getModel('page.trackable');

        /** @var \Mautic\PageBundle\Entity\Redirect $redirect */
        $trackable = $trackableModel->getTrackableByUrl($url, 'sms', $clickthrough['sms']);

        return $trackableModel->generateTrackableUrl($trackable, $clickthrough, true);
    }
}