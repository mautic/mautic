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
     * BuildJsSubscriber constructor.
     *
     * @param LeadModel            $leadModel
     * @param Session              $session
     * @param CoreParametersHelper $coreParametersHelper
     */
    public function __construct(LeadModel $leadModel, Session $session, CoreParametersHelper $coreParametersHelper)
    {
        $this->leadModel            = $leadModel;
        $this->session              = $session;
        $this->coreParametersHelper = $coreParametersHelper;
    }

    public function isEnabledCampaignAction()
    {
        return (bool) $this->coreParametersHelper->getParameter('pixel_in_campaign_enabled');
    }

    public function getEnabledServices()
    {
        $keys = [
            'google_analytics_id' => 'Google Analytics',
            'google_adwords_id'   => 'Google Adwords',
            'facebook_pixel_id'   => 'Facebook Pixel',
        ];
        $result = [];
        foreach ($keys as $key => $service) {
            if ($id = $this->coreParametersHelper->getParameter($key)) {
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
     * @param $key
     * @param $action
     * @param $label
     *
     * @return array
     */
    public function setSession($key, $action, $label)
    {
        $sessionName = $this->getSessionName();
        $session     = unserialize($this->session->get($sessionName));
        if (!is_array($session)) {
            $session = [$session];
        }
        $session[$key][] = ['action' => $action, 'label' => $label];
        $this->session->set($sessionName, serialize($session));

        return (array) $session;
    }

    /**
     * @return array
     */
    public function getSession()
    {
        $sessionName = $this->getSessionName();

        return (array) unserialize($this->session->get($sessionName));
    }
}
