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
use Mautic\SmsBundle\Exception\PrimaryTransportNotEnabledException;
use Monolog\Logger;

class TransportChain
{
    /**
     * @var TransportInterface[]
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
     * @param string $primaryTransport
     */
    public function __construct($primaryTransport, IntegrationHelper $integrationHelper, Logger $logger)
    {
        $this->primaryTransport  = $primaryTransport;
        $this->transports        = [];
        $this->integrationHelper = $integrationHelper;
        $this->logger            = $logger;
    }

    /**
     * @param string $alias
     * @param string $translatableAlias
     * @param string $integrationAlias
     *
     * @return $this
     */
    public function addTransport($alias, TransportInterface $transport, $translatableAlias, $integrationAlias)
    {
        $this->transports[$alias]['alias']            = $translatableAlias;
        $this->transports[$alias]['integrationAlias'] = $integrationAlias;
        $this->transports[$alias]['service']          = $transport;

        return $this;
    }

    /**
     * Return the transport defined in parameters.
     *
     * @return TransportInterface
     *
     * @throws PrimaryTransportNotEnabledException
     */
    public function getPrimaryTransport()
    {
        $enabled = $this->getEnabledTransports();

        // If there no primary transport selected and there is just one available we will use it as primary
        if (1 === count($enabled)) {
            return array_shift($enabled);
        }

        if (0 === count($enabled)) {
            throw new PrimaryTransportNotEnabledException('Primary SMS transport is not enabled');
        }

        if (!array_key_exists($this->primaryTransport, $enabled)) {
            throw new PrimaryTransportNotEnabledException('Primary SMS transport is not enabled. '.$this->primaryTransport);
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
     * @return TransportInterface[]
     */
    public function getTransports()
    {
        return $this->transports;
    }

    /**
     * @param string $transport
     *
     * @return TransportInterface
     *
     * @throws PrimaryTransportNotEnabledException
     */
    public function getTransport($transport)
    {
        $enabled = $this->getEnabledTransports();

        if (!array_key_exists($transport, $enabled)) {
            throw new PrimaryTransportNotEnabledException($transport.' SMS transport is not enabled or does not exist');
        }

        return $enabled[$transport];
    }

    /**
     * Get published transports.
     *
     * @return TransportInterface[]
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
