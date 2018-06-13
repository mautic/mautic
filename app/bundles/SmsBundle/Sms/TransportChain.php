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

use Mautic\PluginBundle\Helper\IntegrationHelper;
use Mautic\SmsBundle\Api\AbstractSmsApi;
use Monolog\Logger;

class TransportChain
{
    /**
     * @var AbstractSmsApi[]
     */
    private $transports;

    /**
     * @var string
     */
    private $primaryTransport;

    /**
     * @var IntegrationHelper
     */
    private $integrationHelper;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * TransportChain constructor.
     *
     * @param $primaryTransport
     * @param $integrationHelper
     * @param $logger
     */
    public function __construct($primaryTransport, IntegrationHelper $integrationHelper, Logger $logger)
    {
        $this->primaryTransport  = $primaryTransport;
        $this->transports        = [];
        $this->integrationHelper = $integrationHelper;
        $this->logger            = $logger;
    }

    /**
     * @param                $alias
     * @param AbstractSmsApi $transport
     * @param                $translatableAlias
     * @param                $integrationAlias
     *
     * @return $this
     */
    public function addTransport($alias, AbstractSmsApi $transport, $translatableAlias, $integrationAlias)
    {
        $this->transports[$alias]['alias']            = $translatableAlias;
        $this->transports[$alias]['integrationAlias'] = $integrationAlias;
        $this->transports[$alias]['service']          = $transport;

        return $this;
    }

    /**
     * Return the transport defined in parameters.
     *
     * @return AbstractSmsApi
     *
     * @throws \Exception
     */
    private function getPrimaryTransport()
    {
        $enabled = $this->getEnabledTransports();

        if (!array_key_exists($this->primaryTransport, $enabled)) {
            throw new \Exception('Primary SMS transport is not enabled. '.$this->primaryTransport);
        }

        return $enabled[$this->primaryTransport];
    }

    /**
     * @param $number
     * @param $content
     *
     * @return mixed
     *
     * @throws \Exception
     */
    public function sendSms($number, $content)
    {
        $this->logger->addInfo('Sending an SMS message using '
                            .$this->transports[$this->primaryTransport]['integrationAlias'].' to '
                            .(is_array($number) ? join(',', $number) : $number));
        $response = $this->getPrimaryTransport()->sendSms($number, $content);

        return $response;
    }

    /**
     * Get all transports registered in service container.
     *
     * @return AbstractSmsApi[][]
     */
    public function getTransports()
    {
        return $this->transports;
    }

    /**
     * Get published transports.
     *
     * @return AbstractSmsApi[]
     */
    public function getEnabledTransports()
    {
        $enabled = [];
        foreach ($this->transports as $alias=>$transport) {
            if (!isset($transport['published'])) {
                $integration = $this->integrationHelper->getIntegrationObject($transport['integrationAlias']);
                if (!$integration) {
                    continue;
                }
                $transport['published']   = $integration->getIntegrationSettings()->getIsPublished();
                $this->transports[$alias] = $transport;
            }
            if ($transport['published']) {
                $enabled[$alias] = $transport['service'];
            }
        }

        return $enabled;
    }
}
