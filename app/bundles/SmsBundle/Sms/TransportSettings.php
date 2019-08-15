<?php
/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\SmsBundle\Sms;

class TransportSettings
{
    /**
     * @var TransportInterface
     */
    private $transport;

    public function __construct(TransportInterface $transport)
    {
        $this->transport = $transport;
    }

    /**
     * @return bool
     */
    public function hasDelivered()
    {
        return true;
    }

    /**
     * @return bool
     */
    public function hasRead()
    {
        return true;
    }

    /**
     * @return bool
     */
    public function hasFailed()
    {
        return true;
    }
}
