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
     * @param string $number
     * @param string $content
     *
     * @return mixed
     */
    abstract public function sendSms($number, $content);
}