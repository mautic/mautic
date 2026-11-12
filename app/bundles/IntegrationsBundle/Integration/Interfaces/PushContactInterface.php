<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Integration\Interfaces;

use Mautic\IntegrationsBundle\Exception\PushContactException;
use Mautic\LeadBundle\Entity\Lead;
use Symfony\Component\Form\FormTypeInterface;

/**
 * Lets the "Push contact to integration" form, campaign and point actions push contacts to this integration.
 * The integration is offered by those actions once its "push_lead" feature is enabled.
 */
interface PushContactInterface extends IntegrationInterface
{
    /**
     * Form type for the integration's settings on a push action. Its data is stored as the action's "config".
     *
     * @return class-string<FormTypeInterface<array<string, mixed>>>
     */
    public function getPushContactConfigFormName(): string;

    /**
     * @param array<string, mixed> $config the action's "config", as submitted through getPushContactConfigFormName()
     *
     * @throws PushContactException when the contact was not pushed
     */
    public function pushContact(Lead $contact, array $config): void;
}
