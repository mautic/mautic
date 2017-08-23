<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PageBundle\Helper;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\LeadModel;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;

/**
 * Class TrackinHelper.
 */
class TrackingHelper
{
    /**
     * @var LeadModel
     */
    protected $leadModel;

    /**
     * @var Session
     */
    protected $session;

    /**
     * @var CoreParametersHelper
     */
    protected $coreParametersHelper;

    /**
     * @var RequestStack
     */
    protected $request;

    /**
     * BuildJsSubscriber constructor.
     *
     * @param LeadModel            $leadModel
     * @param Session              $session
     * @param CoreParametersHelper $coreParametersHelper
     * @param RequestStack         $request
     */
    public function __construct(LeadModel $leadModel, Session $session, CoreParametersHelper $coreParametersHelper, RequestStack $request)
    {
        $this->leadModel            = $leadModel;
        $this->session              = $session;
        $this->coreParametersHelper = $coreParametersHelper;
        $this->request              = $request;
    }

    public function getEnabledServices()
    {
        $keys = [
            'google_analytics' => 'Google Analytics',
            'facebook_pixel'   => 'Facebook Pixel',
        ];
        $result = [];
        foreach ($keys as $key => $service) {
            if (($id = $this->coreParametersHelper->getParameter($key.'_id'))) {
                $result[$key] = $service;
            }
        }

        return $result;
    }

    public function getSessionName()
    {
        $lead = $this->leadModel->getCurrentLead();
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
        $sesionValue = unserialize($this->session->get($sessionName));
        if ($remove) {
            $this->session->remove($sessionName);
        }

        return (array) $sesionValue;
    }

    /**
     * @param $service
     *
     * @return bool|mixed
     */
    public function displayInitCode($service)
    {
        $pixelId = $this->coreParametersHelper->getParameter($service.'_id');

        if ($pixelId && $this->coreParametersHelper->getParameter($service.'_landingpage_enabled') && $this->isLandingPage()) {
            return $pixelId;
        }
        if ($pixelId && $this->coreParametersHelper->getParameter($service.'_trackingpage_enabled') && !$this->isLandingPage()) {
            return $pixelId;
        }

        return false;
    }

    /**
     * @return array|Lead|null
     */
    public function getLead()
    {
        return $this->leadModel->getCurrentLead();
    }

    /**
     * @return bool
     */
    protected function isLandingPage()
    {
        $server = $this->request->getCurrentRequest()->server;
        if (strpos($server->get('HTTP_REFERER'), $this->coreParametersHelper->getParameter('site_url')) === false) {
            return false;
        }

        return true;
    }
}
