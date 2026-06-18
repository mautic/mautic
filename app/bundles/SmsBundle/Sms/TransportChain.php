<?php

namespace Mautic\SmsBundle\Sms;

use Mautic\LeadBundle\Entity\Lead;
use Mautic\PluginBundle\Helper\IntegrationHelper;
use Mautic\SmsBundle\Collection\RecipientCollection;
use Mautic\SmsBundle\Entity\Stat;
use Mautic\SmsBundle\Exception\PrimaryTransportNotEnabledException;
use Mautic\SmsBundle\Helper\DTO\SmsRecipientDTO;

class TransportChain
{
    /**
     * @var array<string, array{alias: string, integrationAlias: string, service: TransportInterface, published?: bool}>
     */
    private array $transports;

    /**
     * @param string $primaryTransport
     */
    public function __construct(
        private $primaryTransport,
        private IntegrationHelper $integrationHelper,
    ) {
        $this->transports        = [];
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
     * @param RecipientCollection<SmsRecipientDTO> $collection
     *
     * @return RecipientCollection<SmsRecipientDTO>
     *
     * @throws PrimaryTransportNotEnabledException
     */
    public function sendBatchSms(RecipientCollection $collection, string $template): RecipientCollection
    {
        $primaryTransport = $this->getPrimaryTransport();

        // If the transport support sending of bulk sms
        if ($primaryTransport instanceof BulkTransportInterface) {
            return $primaryTransport->sendBatchSms($collection, $template);
        }

        return $this->sendMessage($collection);
    }

    /**
     * @param RecipientCollection<SmsRecipientDTO> $collection
     * @param array<mixed>                         $media
     *
     * @return RecipientCollection<SmsRecipientDTO>
     */
    public function sendMMS(RecipientCollection $collection, array $media = []): RecipientCollection
    {
        return $this->sendMessage($collection, $media);
    }

    /**
     * @param RecipientCollection<SmsRecipientDTO> $collection
     * @param array<mixed>                         $media
     *
     * @return RecipientCollection<SmsRecipientDTO>
     */
    private function sendMessage(RecipientCollection $collection, array $media = []): RecipientCollection
    {
        // loops through contacts
        foreach ($collection as $recipient) {
            $content          = $recipient->getFinalMessage();
            $primaryTransport = $this->getPrimaryTransport();

            // As of now media is only supported by twilio
            if ($media && $primaryTransport instanceof MMSTransportInterface) {
                $status = $primaryTransport->sendMms($recipient->getLead(), $content, $media);
            } else {
                $status  = $this->sendSms($recipient->getLead(), $content);
            }
            $recipient->setResult($status);
        }

        return $collection;
    }

    /**
     * @param string $content
     *
     * @return mixed
     *
     * @throws \Exception
     */
    public function sendSms(Lead $lead, $content, ?Stat $stat = null)
    {
        return $this->getPrimaryTransport()->sendSms($lead, $content, $stat);
    }

    /**
     * Get all transports registered in service container.
     *
     * @return array<string, array{alias: string, integrationAlias: string, service: TransportInterface, published?: bool}>
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
    public function getEnabledTransports(): array
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
