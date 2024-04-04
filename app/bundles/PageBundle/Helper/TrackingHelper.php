<?php

namespace Mautic\PageBundle\Helper;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\Serializer;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Tracker\ContactTracker;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;

class TrackingHelper
{
    public function __construct(
        protected Session $session,
        protected CoreParametersHelper $coreParametersHelper,
        protected RequestStack $requestStack,
        protected ContactTracker $contactTracker
    ) {
    }

    /**
     * @return array<string, 'facebook_pixel'|'google_analytics'>
     */
    public function getEnabledServices(): array
    {
        $keys = [
            'google_analytics' => 'Google Analytics',
            'facebook_pixel'   => 'Facebook Pixel',
        ];
        $result = [];
        foreach ($keys as $key => $service) {
            if ($id = $this->coreParametersHelper->get($key.'_id')) {
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
     *
     * @return array
     */
    public function updateSession($values)
    {
        $sessionName = $this->getSessionName();
        $this->session->set($sessionName, serialize(array_merge($values, $this->getSession())));

        return (array) $values;
    }

    /**
     * @return array
     */
    public function getSession($remove = false)
    {
        $sessionName = $this->getSessionName();
        $sesionValue = Serializer::decode($this->session->get($sessionName));
        if ($remove) {
            $this->session->remove($sessionName);
        }

        return (array) $sesionValue;
    }

    /**
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

    protected function isLandingPage(): bool
    {
        $server = $this->requestStack->getCurrentRequest()->server;
        if (!str_contains((string) $server->get('HTTP_REFERER'), $this->coreParametersHelper->get('site_url'))) {
            return false;
        }

        return true;
    }
}
