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

use Symfony\Component\HttpFoundation\Session\Session;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\LeadBundle\Entity\Lead;

/**
 * Class TrackinHelper
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
     * BuildJsSubscriber constructor.
     *
     * @param LeadModel $leadModel
     * @param Session $session
     */
    public function __construct(LeadModel $leadModel, Session $session)
    {
        $this->leadModel = $leadModel;
        $this->session = $session;
    }

    public static $prefix = 'mtc-tracking-pixel-events-';

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
     * @return array
     */
    public function setSession($key, $action, $label)
    {
        $sessionName = $this->getSessionName();
        $session = unserialize($this->session->get($sessionName));
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
    public function getSession(){
        $sessionName = $this->getSessionName();
        return (array) unserialize($this->session->get($sessionName));
    }

}
