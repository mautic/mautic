<?php

declare(strict_types=1);

namespace MauticPlugin\MauticCrmBundle\Integration;

use Mautic\IntegrationsBundle\Exception\PushContactException;
use Mautic\IntegrationsBundle\Integration\BasicIntegration;
use Mautic\IntegrationsBundle\Integration\Interfaces\BasicInterface;
use Mautic\IntegrationsBundle\Integration\Interfaces\PushContactInterface;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\InputOptionsDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\ObjectIdsDAO;
use Mautic\IntegrationsBundle\Sync\SyncDataExchange\Internal\Object\Contact;
use Mautic\IntegrationsBundle\Sync\SyncService\SyncServiceInterface;
use Mautic\LeadBundle\Entity\Lead;
use MauticPlugin\MauticCrmBundle\Hubspot\Client\HubspotClient;
use MauticPlugin\MauticCrmBundle\Hubspot\Config;
use Symfony\Component\DependencyInjection\Attribute\AutowireServiceClosure;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Contracts\Translation\TranslatorInterface;

class HubspotIntegration extends BasicIntegration implements BasicInterface, PushContactInterface
{
    public const string NAME = 'Hubspot';

    /**
     * Sync option that lets the sync create HubSpot records whatever the "create records" setting is.
     */
    public const string OPTION_CREATE_RECORDS = 'create-records';

    /**
     * @param \Closure(): SyncServiceInterface $syncService a closure, as the sync service needs the HubSpot sync integration
     */
    public function __construct(
        #[AutowireServiceClosure(SyncServiceInterface::class)]
        private readonly \Closure $syncService,
        private readonly Config $config,
        private readonly TranslatorInterface $translator,
    ) {
    }

    public function getName(): string
    {
        return self::NAME;
    }

    public function getDisplayName(): string
    {
        return 'Hubspot';
    }

    public function getIcon(): string
    {
        return 'plugins/MauticCrmBundle/Assets/img/hubspot.png';
    }

    /**
     * The push uses the field mapping of the sync, so the action has no settings of its own.
     */
    public function getPushContactConfigFormName(): string
    {
        return FormType::class;
    }

    /**
     * Pushes the contact with a sync towards HubSpot, which creates it there if needed.
     */
    public function pushContact(Lead $contact, array $config): void
    {
        if (!$contact->getId()) {
            throw new PushContactException($this->translator->trans('mautic.hubspot.push.not_saved'));
        }

        if (!in_array(HubspotClient::CONTACTS, $this->config->getEnabledObjects(), true)) {
            throw new PushContactException($this->translator->trans('mautic.hubspot.push.contacts_not_synced'));
        }

        $contactIds = new ObjectIdsDAO();
        $contactIds->addObjectId(Contact::NAME, (string) $contact->getId());

        try {
            ($this->syncService)()->processIntegrationSync(new InputOptionsDAO([
                'integration'      => self::NAME,
                'disable-pull'     => true,
                'mautic-object-id' => $contactIds,
                'options'          => [self::OPTION_CREATE_RECORDS => true],
            ]));
        } catch (\Exception $e) {
            throw new PushContactException($e->getMessage(), 0, $e);
        }
    }
}
