<?php

/*
 * @copyright   2015 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Templating\Helper;

use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\CoreBundle\Helper\CookieHelper;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\LeadModel;
use Symfony\Component\Templating\Helper\Helper;

class AnalyticsHelper extends Helper
{
    /**
     * @var string
     */
    private $code;

    /**
     * @var CookieHelper
     */
    private $cookieHelper;

    /**
     * @var LeadModel
     */
    private $leadModel;

    /**
     * AnalyticsHelper constructor.
     *
     * @param CoreParametersHelper $parametersHelper
     * @param CookieHelper         $cookieHelper
     * @param LeadModel            $leadModel
     */
    public function __construct(CoreParametersHelper $parametersHelper, CookieHelper $cookieHelper, LeadModel $leadModel)
    {
        $this->code         = htmlspecialchars_decode($parametersHelper->getParameter('google_analytics', ''));
        $this->cookieHelper = $cookieHelper;
        $this->leadModel    = $leadModel;
    }

    /**
     * @return string
     */
    public function getCode()
    {
        list($lead, $trackingId, $ignored) = $this->leadModel->getCurrentLead(true);

        if ($lead instanceof Lead) {
            $this->cookieHelper->setCookie('mtc_id', $lead->getId(), null);
            $this->cookieHelper->setCookie('mtc_sid', $trackingId, null);
        } else {
            $this->cookieHelper->deleteCookie('mtc_id');
            $this->cookieHelper->deleteCookie('mtc_sid');
        }

        return $this->code;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'analytics';
    }
}
