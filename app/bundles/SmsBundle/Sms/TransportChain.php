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

use Mautic\LeadBundle\Entity\Lead;
use Mautic\PluginBundle\Helper\IntegrationHelper;
use Mautic\SmsBundle\Api\AbstractSmsApi;

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
     * @param string $primaryTransport
     */
    public function __construct($primaryTransport, IntegrationHelper $integrationHelper)
    {
        $this->primaryTransport  = $primaryTransport;
        $this->transports        = [];
        $this->integrationHelper = $integrationHelper;
    }

    /**
     * @param $alias
     * @param $translatableAlias
     * @param $integrationAlias
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

        // If there no primary transport selected and there is just one available we will use it as primary
        if (1 === count($enabled)) {
            return array_shift($enabled);
        }

        if (!array_key_exists($this->primaryTransport, $enabled)) {
            throw new \Exception('Primary SMS transport is not enabled. '.$this->primaryTransport);
        }

        return $enabled[$this->primaryTransport];
    }

    /**
     * @param string $content
     *
     * @return mixed
     *
     * @throws \Exception
     */
    public function sendSms(Lead $lead, $content)
    {
        $response = $this->getPrimaryTransport()->sendSms($lead, $content);

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
        foreach ($this->transports as $alias => $transport) {
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
