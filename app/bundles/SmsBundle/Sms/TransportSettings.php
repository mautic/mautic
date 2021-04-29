<?php
/*
 * @copyright   2020 Mautic Contributors. All rights reserved
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
     * @return array
     */
    private function enabledSettings()
    {
        if (method_exists($this->transport, 'enabledSettings')) {
            return $this->transport->enabledSettings();
        }

        return [];
    }

    /**
     * @param $setting
     *
     * @return bool
     */
    public function hasSetting($setting)
    {
        return in_array($setting, $this->enabledSettings());
    }
}
