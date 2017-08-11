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

    public function getEnabledServices()
    {
        $keys = [
            'google_analytics_event' => 'Google Analytics',
            'facebook_pixel_event'   => 'Facebook Pixel',
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
     * @param array $values
     *
     * @return array
     */
    public function updateSession($values)
    {
        $sessionName = $this->getSessionName();
        $this->session->set($sessionName, serialize(array_merge($this->getSession($key), $values)));

        return (array) $values;
    }

    /**
     * @return array
     */
    public function getSession($remove = false)
    {
        $sessionName = $this->getSessionName();
        $sesionValue = (array) unserialize($this->session->get($sessionName));
        if ($remove) {
            $this->session->remove($sessionName);
        }

        return $sesionValue;
    }
}
