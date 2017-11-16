<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Tests\MonitoredEmail\Transport;

use Mautic\EmailBundle\MonitoredEmail\Message;
use Mautic\EmailBundle\MonitoredEmail\Processor\Bounce\BouncedEmail;
use Mautic\EmailBundle\Swiftmailer\Transport\BounceProcessorInterface;

class BounceTransport extends \Swift_Transport_NullTransport implements BounceProcessorInterface
{
    public function processBounce(Message $message)
    {
        return new BouncedEmail();
    }
}
