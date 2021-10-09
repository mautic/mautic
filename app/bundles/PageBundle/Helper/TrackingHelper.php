<?php

namespace Mautic\PageBundle\Helper;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Tracker\ContactTracker;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class TrackinHelper.
 */
class TrackingHelper
{
    /**
     * @var CoreParametersHelper
     */
    protected $coreParametersHelper;

    /**
     * @var RequestStack
     */
    protected $requestStack;

    /**
     * @var ContactTracker
     */
    protected $contactTracker;

    /** @var array */
    private $localCache = [];

    /**
     * BuildJsSubscriber constructor.
     */
    public function __construct(
        CoreParametersHelper $coreParametersHelper,
        RequestStack $requestStack,
        ContactTracker $contactTracker
    ) {
        $this->coreParametersHelper = $coreParametersHelper;
        $this->requestStack         = $requestStack;
        $this->contactTracker       = $contactTracker;
    }

    public function getEnabledServices()
    {
        $keys = [
            'google_analytics' => 'Google Analytics',
            'facebook_pixel'   => 'Facebook Pixel',
        ];
        $result = [];
        foreach ($keys as $key => $service) {
            if (($id = $this->coreParametersHelper->get($key.'_id'))) {
                $result[$service] = $key;
            }
        }

        return $result;
    }

    public function getSessionName()
    {
        $lead = $this->contactTracker->getContact();
        if ($lead instanceof Lead) {
            return 'mtc-tracking-pixel-events-'.$lead->getId();
        }
    }

    /**
     * @param array $values
     */
    public function updateSession($values)
    {
        $sessionName                      = $this->getSessionName();
        $this->localCache[$sessionName]   = array_merge($values, $this->getSession());
    }

    /**
     * @return array
     */
    public function getSession($remove = false)
    {
        $sessionName = $this->getSessionName();
        $output      = $this->localCache[$sessionName] ?? [];

        if ($remove) {
            unset($this->localCache[$sessionName]);
        }

        return (array) $output;
    }

    /**
     * @param $service
     *
     * @return bool|mixed
     */
    public function displayInitCode($service)
    {
        $pixelId = $this->coreParametersHelper->get($service.'_id');

        if ($pixelId && $this->coreParametersHelper->get($service.'_landingpage_enabled') && $this->isLandingPage()) {
            return $pixelId;
        }
        if ($pixelId && $this->coreParametersHelper->get($service.'_trackingpage_enabled') && !$this->isLandingPage()) {
            return $pixelId;
        }

        return false;
    }

    /**
     * @return array|Lead|null
     */
    public function getLead()
    {
        return $this->contactTracker->getContact();
    }

    public function getAnonymizeIp()
    {
        return $this->coreParametersHelper->get('google_analytics_anonymize_ip');
    }

    /**
     * @return bool
     */
    protected function isLandingPage()
    {
        $server = $this->requestStack->getCurrentRequest()->server;
        if (false === strpos($server->get('HTTP_REFERER'), $this->coreParametersHelper->get('site_url'))) {
            return false;
        }

        return true;
    }
}
