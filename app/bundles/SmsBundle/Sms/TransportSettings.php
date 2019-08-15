<?php
/*
 * @copyright   2019 Mautic Contributors. All rights reserved
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
     * @var TransportInterface|TransportSettingsInterface
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
        return method_exists($this->transport, 'hasDelivered') && $this->transport->hasDelivered();
    }

    /**
     * @return bool
     */
    public function hasRead()
    {
        return method_exists($this->transport, 'hasRead') && $this->transport->hasRead();
    }

    /**
     * @return bool
     */
    public function hasFailed()
    {
        return method_exists($this->transport, 'hasFailed') && $this->transport->hasFailed();
    }
}
