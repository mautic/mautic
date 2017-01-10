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
use Symfony\Component\Templating\Helper\Helper;

class AnalyticsHelper extends Helper
{
    /**
     * @var string
     */
    private $code;

    public function __construct(MauticFactory $factory)
    {
        $this->code = htmlspecialchars_decode($factory->getParameter('google_analytics', ''));
    }

    /**
     * @return string
     */
    public function getCode()
    {
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
